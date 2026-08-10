<?php

declare(strict_types=1);

function tm_home_page_defaults_path(): string
{
    return __DIR__ . '/../data/home-page.default.json';
}

function tm_home_page_save_path(): string
{
    return __DIR__ . '/../data/home-page.json';
}

/**
 * @return array<string, mixed>
 */
function tm_home_page_defaults(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    $path = tm_home_page_defaults_path();
    if (!is_readable($path)) {
        $cached = ['announcement' => ''];
        return $cached;
    }
    $raw = file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    $cached = is_array($data) ? $data : ['announcement' => ''];
    return $cached;
}

/**
 * @return array<string, mixed>
 */
function tm_home_page_load(): array
{
    $defaults = tm_home_page_defaults();
    $path = tm_home_page_save_path();
    if (!is_readable($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return $defaults;
    }
    $saved = json_decode($raw, true);
    if (!is_array($saved)) {
        return $defaults;
    }
    return tm_home_page_merge($defaults, $saved);
}

/**
 * @param array<string, mixed> $defaults
 * @param array<string, mixed> $saved
 * @return array<string, mixed>
 */
function tm_home_page_merge(array $defaults, array $saved): array
{
    $out = $defaults;
    if (isset($saved['announcement']) && is_string($saved['announcement'])) {
        $out['announcement'] = $saved['announcement'];
    }
    if (isset($saved['logoImage']) && is_string($saved['logoImage'])) {
        $out['logoImage'] = $saved['logoImage'];
    }
    foreach (['heroSlides', 'categoryStrip', 'personaliseCards', 'trustItems', 'howItWorksSteps'] as $key) {
        if (isset($saved[$key]) && is_array($saved[$key]) && $saved[$key] !== []) {
            $out[$key] = $saved[$key];
        }
    }
    if (isset($saved['howItWorksIntro']) && is_array($saved['howItWorksIntro'])) {
        $out['howItWorksIntro'] = $saved['howItWorksIntro'];
    }
    if (isset($saved['newsletter']) && is_array($saved['newsletter'])) {
        $out['newsletter'] = $saved['newsletter'];
    }
    if (isset($saved['layout']) && is_array($saved['layout'])) {
        $out['layout'] = tm_home_page_normalize_layout($saved['layout']);
    } elseif (!isset($out['layout']) || !is_array($out['layout'])) {
        $out['layout'] = tm_home_page_default_layout();
    }
    return $out;
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, homePage?: array<string, mixed>}
 */
function tm_admin_home_page_save(array $body): array
{
    $defaults = tm_home_page_defaults();
    $current = tm_home_page_load();

    $next = $current;
    if (isset($body['announcement']) && is_string($body['announcement'])) {
        $next['announcement'] = substr(trim($body['announcement']), 0, 500);
    }
    if (array_key_exists('logoImage', $body) && is_string($body['logoImage'])) {
        $next['logoImage'] = trim($body['logoImage']);
    }

    if (isset($body['heroSlides']) && is_array($body['heroSlides'])) {
        $slides = tm_home_page_normalize_hero_slides($body['heroSlides'], $defaults['heroSlides'] ?? []);
        if ($slides !== []) {
            $next['heroSlides'] = $slides;
        }
    }
    if (isset($body['categoryStrip']) && is_array($body['categoryStrip'])) {
        $strip = tm_home_page_normalize_category_strip($body['categoryStrip'], $defaults['categoryStrip'] ?? []);
        if ($strip !== []) {
            $next['categoryStrip'] = $strip;
        }
    }
    if (isset($body['personaliseCards']) && is_array($body['personaliseCards'])) {
        $cards = tm_home_page_normalize_personalise_cards($body['personaliseCards'], $defaults['personaliseCards'] ?? []);
        if ($cards !== []) {
            $next['personaliseCards'] = $cards;
        }
    }
    if (isset($body['trustItems']) && is_array($body['trustItems'])) {
        $trust = tm_home_page_normalize_trust_items($body['trustItems'], $defaults['trustItems'] ?? []);
        if ($trust !== []) {
            $next['trustItems'] = $trust;
        }
    }
    if (isset($body['howItWorksIntro']) && is_array($body['howItWorksIntro'])) {
        $next['howItWorksIntro'] = tm_home_page_normalize_section_intro($body['howItWorksIntro'], $defaults['howItWorksIntro'] ?? []);
    }
    if (isset($body['howItWorksSteps']) && is_array($body['howItWorksSteps'])) {
        $steps = tm_home_page_normalize_how_it_works($body['howItWorksSteps'], $defaults['howItWorksSteps'] ?? []);
        if ($steps !== []) {
            $next['howItWorksSteps'] = $steps;
        }
    }
    if (isset($body['newsletter']) && is_array($body['newsletter'])) {
        $next['newsletter'] = tm_home_page_normalize_newsletter($body['newsletter'], $defaults['newsletter'] ?? []);
    }
    if (isset($body['layout']) && is_array($body['layout'])) {
        $next['layout'] = tm_home_page_normalize_layout($body['layout']);
    }

    $path = tm_home_page_save_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create data directory'];
    }

    $json = json_encode($next, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($path, $json) === false) {
        return ['ok' => false, 'message' => 'Could not save home page config'];
    }

    $sitePath = __DIR__ . '/../data/site-default.json';
    $siteData = ['announcement' => (string) ($next['announcement'] ?? '')];
    file_put_contents($sitePath, json_encode($siteData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return ['ok' => true, 'homePage' => $next];
}

/**
 * @param list<mixed> $input
 * @param list<mixed> $fallback
 * @return list<array<string, mixed>>
 */
function tm_home_page_normalize_hero_slides(array $input, array $fallback): array
{
    $out = [];
    foreach ($input as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $fb = is_array($fallback[$i] ?? null) ? $fallback[$i] : [];
        $ctaP = is_array($row['ctaPrimary'] ?? null) ? $row['ctaPrimary'] : [];
        $ctaS = is_array($row['ctaSecondary'] ?? null) ? $row['ctaSecondary'] : [];
        $fbP = is_array($fb['ctaPrimary'] ?? null) ? $fb['ctaPrimary'] : [];
        $fbS = is_array($fb['ctaSecondary'] ?? null) ? $fb['ctaSecondary'] : [];
        $out[] = [
            'eyebrow' => substr(trim((string) ($row['eyebrow'] ?? $fb['eyebrow'] ?? '')), 0, 80),
            'tabLabel' => substr(trim((string) ($row['tabLabel'] ?? $fb['tabLabel'] ?? '')), 0, 40),
            'title' => substr(trim((string) ($row['title'] ?? $fb['title'] ?? '')), 0, 200),
            'text' => substr(trim((string) ($row['text'] ?? $fb['text'] ?? '')), 0, 500),
            'image' => tm_home_page_normalize_image((string) ($row['image'] ?? $fb['image'] ?? '')),
            'ctaPrimary' => [
                'label' => substr(trim((string) ($ctaP['label'] ?? $fbP['label'] ?? 'Shop')), 0, 60),
                'to' => substr(trim((string) ($ctaP['to'] ?? $fbP['to'] ?? '/shop')), 0, 200),
            ],
            'ctaSecondary' => [
                'label' => substr(trim((string) ($ctaS['label'] ?? $fbS['label'] ?? 'Learn more')), 0, 60),
                'to' => substr(trim((string) ($ctaS['to'] ?? $fbS['to'] ?? '/shop')), 0, 200),
            ],
        ];
    }
    return $out;
}

/**
 * @param list<mixed> $input
 * @param list<mixed> $fallback
 * @return list<array<string, mixed>>
 */
function tm_home_page_normalize_category_strip(array $input, array $fallback): array
{
    $out = [];
    foreach ($input as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $fb = is_array($fallback[$i] ?? null) ? $fallback[$i] : [];
        $out[] = [
            'title' => substr(trim((string) ($row['title'] ?? $fb['title'] ?? '')), 0, 80),
            'blurb' => substr(trim((string) ($row['blurb'] ?? $fb['blurb'] ?? '')), 0, 120),
            'href' => substr(trim((string) ($row['href'] ?? $fb['href'] ?? '/shop')), 0, 200),
            'image' => tm_home_page_normalize_image((string) ($row['image'] ?? $fb['image'] ?? '')),
        ];
    }
    return $out;
}

/**
 * @param list<mixed> $input
 * @param list<mixed> $fallback
 * @return list<array<string, mixed>>
 */
function tm_home_page_normalize_personalise_cards(array $input, array $fallback): array
{
    $allowed = ['bookmark', 'calendar', 'card', 'magnet'];
    $out = [];
    foreach ($input as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $fb = is_array($fallback[$i] ?? null) ? $fallback[$i] : [];
        $id = (string) ($row['id'] ?? $fb['id'] ?? '');
        if (!in_array($id, $allowed, true)) {
            continue;
        }
        $out[] = [
            'id' => $id,
            'shortLabel' => substr(trim((string) ($row['shortLabel'] ?? $fb['shortLabel'] ?? '')), 0, 60),
            'blurb' => substr(trim((string) ($row['blurb'] ?? $fb['blurb'] ?? '')), 0, 200),
            'image' => tm_home_page_normalize_image((string) ($row['image'] ?? $fb['image'] ?? '')),
        ];
    }
    return $out;
}

/**
 * @param list<mixed> $input
 * @param list<mixed> $fallback
 * @return list<array<string, mixed>>
 */
function tm_home_page_normalize_trust_items(array $input, array $fallback): array
{
    $allowed = ['truck', 'return', 'payment', 'offer', 'lock', 'chat'];
    $out = [];
    foreach ($input as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $fb = is_array($fallback[$i] ?? null) ? $fallback[$i] : [];
        $icon = (string) ($row['icon'] ?? $fb['icon'] ?? 'chat');
        if (!in_array($icon, $allowed, true)) {
            $icon = 'chat';
        }
        $out[] = [
            'title' => substr(trim((string) ($row['title'] ?? $fb['title'] ?? '')), 0, 80),
            'text' => substr(trim((string) ($row['text'] ?? $fb['text'] ?? '')), 0, 200),
            'icon' => $icon,
        ];
    }
    return $out;
}

/**
 * @param array<string, mixed> $input
 * @param array<string, mixed> $fallback
 * @return array<string, string>
 */
function tm_home_page_normalize_section_intro(array $input, array $fallback): array
{
    return [
        'eyebrow' => substr(trim((string) ($input['eyebrow'] ?? $fallback['eyebrow'] ?? '')), 0, 80),
        'title' => substr(trim((string) ($input['title'] ?? $fallback['title'] ?? '')), 0, 200),
        'description' => substr(trim((string) ($input['description'] ?? $fallback['description'] ?? '')), 0, 500),
    ];
}

/**
 * @param list<mixed> $input
 * @param list<mixed> $fallback
 * @return list<array<string, mixed>>
 */
function tm_home_page_normalize_how_it_works(array $input, array $fallback): array
{
    $out = [];
    foreach ($input as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $fb = is_array($fallback[$i] ?? null) ? $fallback[$i] : [];
        $out[] = [
            'step' => substr(trim((string) ($row['step'] ?? $fb['step'] ?? (string) ($i + 1))), 0, 4),
            'title' => substr(trim((string) ($row['title'] ?? $fb['title'] ?? '')), 0, 120),
            'text' => substr(trim((string) ($row['text'] ?? $fb['text'] ?? '')), 0, 400),
            'ctaLabel' => substr(trim((string) ($row['ctaLabel'] ?? $fb['ctaLabel'] ?? 'Learn more')), 0, 60),
            'ctaTo' => substr(trim((string) ($row['ctaTo'] ?? $fb['ctaTo'] ?? '/shop')), 0, 200),
        ];
    }
    return $out;
}

/**
 * @param array<string, mixed> $input
 * @param array<string, mixed> $fallback
 * @return array<string, string>
 */
function tm_home_page_normalize_newsletter(array $input, array $fallback): array
{
    return [
        'eyebrow' => substr(trim((string) ($input['eyebrow'] ?? $fallback['eyebrow'] ?? '')), 0, 80),
        'title' => substr(trim((string) ($input['title'] ?? $fallback['title'] ?? '')), 0, 200),
        'description' => substr(trim((string) ($input['description'] ?? $fallback['description'] ?? '')), 0, 500),
        'placeholder' => substr(trim((string) ($input['placeholder'] ?? $fallback['placeholder'] ?? 'Your email')), 0, 80),
        'buttonLabel' => substr(trim((string) ($input['buttonLabel'] ?? $fallback['buttonLabel'] ?? 'Subscribe')), 0, 40),
        'finePrint' => substr(trim((string) ($input['finePrint'] ?? $fallback['finePrint'] ?? '')), 0, 200),
    ];
}

function tm_home_page_normalize_image(string $image): string
{
    $image = trim($image);
    if ($image === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $image)) {
        return substr($image, 0, 512);
    }
    if (str_starts_with($image, '/uploads/') || str_starts_with($image, '/products/')) {
        return substr($image, 0, 512);
    }
    if (str_starts_with($image, '/')) {
        return substr($image, 0, 512);
    }
    return substr($image, 0, 512);
}

/**
 * @return array<string, mixed>
 */
function tm_home_page_public(): array
{
    $page = tm_home_page_load();
    if (!isset($page['layout']) || !is_array($page['layout'])) {
        $page['layout'] = tm_home_page_default_layout();
    }
    return $page;
}

/**
 * @return array<string, mixed>
 */
function tm_home_page_default_layout(): array
{
    return [
        'version' => 1,
        'sections' => [],
    ];
}

/**
 * @param array<string, mixed>|null $s
 * @return array<string, int>|null
 */
function tm_home_page_normalize_spacing(?array $s): ?array
{
    if (!is_array($s)) {
        return null;
    }
    $top = min(200, max(0, (int) ($s['top'] ?? 0)));
    $right = min(200, max(0, (int) ($s['right'] ?? $top)));
    $bottom = min(200, max(0, (int) ($s['bottom'] ?? $top)));
    $left = min(200, max(0, (int) ($s['left'] ?? $right)));
    if ($top === 0 && $right === 0 && $bottom === 0 && $left === 0) {
        return null;
    }

    return ['top' => $top, 'right' => $right, 'bottom' => $bottom, 'left' => $left];
}

/**
 * @param array<string, mixed>|null $s
 * @return array<string, mixed>|null
 */
function tm_home_page_normalize_style(?array $s): ?array
{
    if (!is_array($s)) {
        return null;
    }
    $out = [];
    $pad = tm_home_page_normalize_spacing(is_array($s['padding'] ?? null) ? $s['padding'] : null);
    $mar = tm_home_page_normalize_spacing(is_array($s['margin'] ?? null) ? $s['margin'] : null);
    if ($pad !== null) {
        $out['padding'] = $pad;
    }
    if ($mar !== null) {
        $out['margin'] = $mar;
    }
    if (!empty($s['backgroundColor']) && is_string($s['backgroundColor'])) {
        $out['backgroundColor'] = substr($s['backgroundColor'], 0, 32);
    }
    if (!empty($s['backgroundImage']) && is_string($s['backgroundImage'])) {
        $out['backgroundImage'] = substr($s['backgroundImage'], 0, 300);
    }
    if (isset($s['borderRadius'])) {
        $out['borderRadius'] = min(64, max(0, (int) $s['borderRadius']));
    }
    if (isset($s['borderWidth'])) {
        $out['borderWidth'] = min(8, max(0, (int) $s['borderWidth']));
    }
    if (!empty($s['borderColor']) && is_string($s['borderColor'])) {
        $out['borderColor'] = substr($s['borderColor'], 0, 32);
    }
    $shadow = (string) ($s['boxShadow'] ?? '');
    if (in_array($shadow, ['sm', 'md', 'lg'], true)) {
        $out['boxShadow'] = $shadow;
    }
    if (isset($s['minHeight'])) {
        $out['minHeight'] = min(800, max(0, (int) $s['minHeight']));
    }
    if (isset($s['gap'])) {
        $out['gap'] = min(80, max(0, (int) $s['gap']));
    }
    $align = (string) ($s['textAlign'] ?? '');
    if (in_array($align, ['center', 'right'], true)) {
        $out['textAlign'] = $align;
    }
    if (!empty($s['customClass']) && is_string($s['customClass'])) {
        $out['customClass'] = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $s['customClass']) ?: '', 0, 80);
    }
    if (($s['hideOnMobile'] ?? false) === true) {
        $out['hideOnMobile'] = true;
    }
    if (($s['hideOnDesktop'] ?? false) === true) {
        $out['hideOnDesktop'] = true;
    }

    return $out !== [] ? $out : null;
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function tm_home_page_normalize_layout(array $input): array
{
    $allowedSpan = [3, 4, 6, 8, 12];
    $allowedTheme = ['default', 'cream', 'dark', 'custom'];
    $allowedContainer = ['full', 'wide', 'normal', 'narrow'];
    $allowedTypes = [
        'hero', 'trust', 'section-header', 'category-grid', 'personalise-grid', 'create-set-promo',
        'product-grid', 'product-carousel', 'how-it-works', 'sale-countdown',
        'newsletter', 'blog-teaser', 'banner', 'spacer', 'html',
    ];
    $allowedSources = ['bestsellers', 'magnetic', 'secondary', 'category', 'custom', 'sale'];

    $sections = [];
    $rawSections = $input['sections'] ?? [];
    if (!is_array($rawSections)) {
        return tm_home_page_default_layout();
    }

    foreach (array_slice($rawSections, 0, 24) as $sec) {
        if (!is_array($sec)) {
            continue;
        }
        $rows = [];
        foreach (array_slice($sec['rows'] ?? [], 0, 8) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $columns = [];
            foreach (array_slice($row['columns'] ?? [], 0, 4) as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $span = (int) ($col['span'] ?? 12);
                if (!in_array($span, $allowedSpan, true)) {
                    $span = 12;
                }
                $segments = [];
                foreach (array_slice($col['segments'] ?? [], 0, 8) as $seg) {
                    if (!is_array($seg)) {
                        continue;
                    }
                    $type = (string) ($seg['type'] ?? '');
                    if (!in_array($type, $allowedTypes, true)) {
                        continue;
                    }
                    $norm = [
                        'id' => substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($seg['id'] ?? 'seg')) ?: 'seg', 0, 48),
                        'type' => $type,
                    ];
                    if ($type === 'section-header') {
                        $norm['eyebrow'] = substr(trim((string) ($seg['eyebrow'] ?? '')), 0, 80);
                        $norm['title'] = substr(trim((string) ($seg['title'] ?? '')), 0, 200);
                        $norm['description'] = substr(trim((string) ($seg['description'] ?? '')), 0, 500);
                        $norm['align'] = ($seg['align'] ?? '') === 'center' ? 'center' : 'left';
                        $ts = (string) ($seg['titleSize'] ?? 'md');
                        if (in_array($ts, ['sm', 'md', 'lg', 'xl'], true)) {
                            $norm['titleSize'] = $ts;
                        }
                        if (is_array($seg['cta'] ?? null)) {
                            $norm['cta'] = [
                                'label' => substr(trim((string) ($seg['cta']['label'] ?? '')), 0, 60),
                                'to' => substr(trim((string) ($seg['cta']['to'] ?? '/shop')), 0, 200),
                            ];
                        }
                    } elseif ($type === 'product-grid' || $type === 'product-carousel') {
                        $source = (string) ($seg['source'] ?? 'bestsellers');
                        if (!in_array($source, $allowedSources, true)) {
                            $source = 'bestsellers';
                        }
                        $norm['source'] = $source;
                        $norm['category'] = substr(trim((string) ($seg['category'] ?? '')), 0, 64);
                        $norm['subcategory'] = substr(trim((string) ($seg['subcategory'] ?? '')), 0, 64);
                        $norm['limit'] = min(24, max(1, (int) ($seg['limit'] ?? 8)));
                        if ($type === 'product-grid') {
                            $cols = (int) ($seg['columns'] ?? 5);
                            $norm['columns'] = in_array($cols, [3, 4, 5], true) ? $cols : 5;
                        }
                        if (is_array($seg['productIds'] ?? null)) {
                            $norm['productIds'] = array_values(array_slice(array_map(
                                static fn ($id) => substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id) ?: '', 0, 32),
                                $seg['productIds']
                            ), 0, 24));
                        }
                        $norm['viewAllLabel'] = substr(trim((string) ($seg['viewAllLabel'] ?? '')), 0, 80);
                        $norm['viewAllTo'] = substr(trim((string) ($seg['viewAllTo'] ?? '')), 0, 200);
                    } else {
                        if (isset($seg['limit'])) {
                            $norm['limit'] = min(12, max(1, (int) $seg['limit']));
                        }
                        if (isset($seg['endAt']) && is_string($seg['endAt'])) {
                            $norm['endAt'] = substr($seg['endAt'], 0, 40);
                        }
                        if (isset($seg['headline'])) {
                            $norm['headline'] = substr(trim((string) $seg['headline']), 0, 120);
                        }
                        if (isset($seg['subheadline'])) {
                            $norm['subheadline'] = substr(trim((string) $seg['subheadline']), 0, 200);
                        }
                        if (isset($seg['image'])) {
                            $norm['image'] = tm_home_page_normalize_image((string) $seg['image']);
                        }
                        if (isset($seg['href'])) {
                            $norm['href'] = substr(trim((string) $seg['href']), 0, 200);
                        }
                        if (isset($seg['alt'])) {
                            $norm['alt'] = substr(trim((string) $seg['alt']), 0, 120);
                        }
                        $h = (string) ($seg['height'] ?? 'md');
                        $norm['height'] = in_array($h, ['sm', 'md', 'lg'], true) ? $h : 'md';
                        if ($type === 'html' && isset($seg['html']) && is_string($seg['html'])) {
                            $norm['html'] = substr($seg['html'], 0, 12000);
                        }
                    }
                    $style = tm_home_page_normalize_style(is_array($seg['style'] ?? null) ? $seg['style'] : null);
                    if ($style !== null) {
                        $norm['style'] = $style;
                    }
                    $segments[] = $norm;
                }
                if ($segments !== []) {
                    $colNorm = [
                        'id' => substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($col['id'] ?? 'col')) ?: 'col', 0, 48),
                        'span' => $span,
                        'segments' => $segments,
                    ];
                    $valign = (string) ($col['valign'] ?? '');
                    if (in_array($valign, ['center', 'bottom'], true)) {
                        $colNorm['valign'] = $valign;
                    }
                    $colStyle = tm_home_page_normalize_style(is_array($col['style'] ?? null) ? $col['style'] : null);
                    if ($colStyle !== null) {
                        $colNorm['style'] = $colStyle;
                    }
                    $columns[] = $colNorm;
                }
            }
            if ($columns !== []) {
                $rowNorm = [
                    'id' => substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($row['id'] ?? 'row')) ?: 'row', 0, 48),
                    'columns' => $columns,
                ];
                $rowStyle = tm_home_page_normalize_style(is_array($row['style'] ?? null) ? $row['style'] : null);
                if ($rowStyle !== null) {
                    $rowNorm['style'] = $rowStyle;
                }
                $rows[] = $rowNorm;
            }
        }
        if ($rows !== []) {
            $theme = (string) ($sec['theme'] ?? 'default');
            $container = (string) ($sec['container'] ?? '');
            $secNorm = [
                'id' => substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($sec['id'] ?? 'sec')) ?: 'sec', 0, 48),
                'label' => substr(trim((string) ($sec['label'] ?? 'Section')), 0, 80),
                'enabled' => ($sec['enabled'] ?? true) !== false,
                'theme' => in_array($theme, $allowedTheme, true) ? $theme : 'default',
                'rows' => $rows,
            ];
            if (in_array($container, $allowedContainer, true)) {
                $secNorm['container'] = $container;
            }
            $secStyle = tm_home_page_normalize_style(is_array($sec['style'] ?? null) ? $sec['style'] : null);
            if ($secStyle !== null) {
                $secNorm['style'] = $secStyle;
            }
            $sections[] = $secNorm;
        }
    }

    $presets = [];
    foreach (array_slice($input['presets'] ?? [], 0, 32) as $preset) {
        if (!is_array($preset) || empty($preset['name']) || empty($preset['kind'])) {
            continue;
        }
        $kind = (string) $preset['kind'];
        if (!in_array($kind, ['section', 'row', 'segment'], true)) {
            continue;
        }
        $presets[] = [
            'id' => substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($preset['id'] ?? 'preset')) ?: 'preset', 0, 48),
            'name' => substr(trim((string) $preset['name']), 0, 80),
            'kind' => $kind,
            'payload' => $preset['payload'] ?? [],
        ];
    }

    $out = [
        'version' => 1,
        'sections' => $sections,
    ];
    if ($presets !== []) {
        $out['presets'] = $presets;
    }

    return $out;
}
