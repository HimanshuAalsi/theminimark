<?php

declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

/** @var list<string> */
function tm_product_image_pool(): array
{
    static $pool = null;
    if ($pool !== null) {
        return $pool;
    }

    $path = dirname(__DIR__, 2) . '/data/product_image_pool.json';
    if (!is_readable($path)) {
        $pool = [];
        return $pool;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $pool = [];
        return $pool;
    }

    $pool = array_values(array_filter($decoded, static fn ($url) => is_string($url) && $url !== ''));
    return $pool;
}

/** @var array<string, string> */
function tm_legacy_wp_image_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $path = dirname(__DIR__, 2) . '/data/product_image_map.json';
    if (!is_readable($path)) {
        $map = [];
        return $map;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $map = [];
        return $map;
    }

    $map = [];
    foreach ($decoded as $old => $new) {
        if (is_string($old) && is_string($new) && $new !== '') {
            $map[$old] = $new;
        }
    }

    return $map;
}

/** @var array<string, list<string>> */
function tm_images_by_category(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $path = dirname(__DIR__, 2) . '/data/product_images_by_category.json';
    if (!is_readable($path)) {
        $map = [];
        return $map;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $map = [];
        return $map;
    }

    $map = [];
    foreach ($decoded as $cat => $urls) {
        if (!is_string($cat) || !is_array($urls)) {
            continue;
        }
        $map[$cat] = array_values(array_filter($urls, static fn ($u) => is_string($u) && $u !== ''));
    }

    return $map;
}

function tm_hash_string(string $key): int
{
    $hash = 0;
    $len = strlen($key);
    for ($i = 0; $i < $len; $i++) {
        $hash = (int) (((($hash * 31) + ord($key[$i])) & 0xFFFFFFFF));
        if ($hash > 0x7FFFFFFF) {
            $hash -= 0x100000000;
        }
    }

    return abs($hash);
}

function tm_pick_product_image_from_pool(string $key): string
{
    $pool = tm_product_image_pool();
    if ($pool === []) {
        return '';
    }

    return $pool[tm_hash_string($key) % count($pool)];
}

function tm_pick_product_image_for_category(string $category, string $key): string
{
    $byCat = tm_images_by_category();
    if (isset($byCat[$category]) && $byCat[$category] !== []) {
        $pool = $byCat[$category];
        return $pool[tm_hash_string($key) % count($pool)];
    }

    return tm_pick_product_image_from_pool($key);
}

function tm_rewrite_legacy_product_image_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return $url;
    }

    $map = tm_legacy_wp_image_map();
    if (isset($map[$url])) {
        return $map[$url];
    }

    if (stripos($url, 'wp-content/uploads') !== false) {
        return tm_pick_product_image_from_pool($url);
    }

    return $url;
}

function tm_resolve_product_image_url(string $url, string $slug = '', string $category = ''): string
{
    $url = tm_rewrite_legacy_product_image_url(trim($url));
    $key = $slug !== '' ? $slug : 'product';

    if ($url === '') {
        return tm_pick_product_image_for_category($category, $key);
    }

    if (str_starts_with($url, '/uploads/')) {
        return tm_upload_resolve_url($url);
    }

    if (stripos($url, 'picsum.photos') !== false) {
        return tm_pick_product_image_for_category($category, $key);
    }

    return $url;
}
