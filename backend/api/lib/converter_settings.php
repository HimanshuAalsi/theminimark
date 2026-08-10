<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function tm_converter_settings_defaults(): array
{
    $cfg = tm_config()['uploads'] ?? [];

    return [
        'webpQuality' => min(100, max(80, (int) ($cfg['webp_quality'] ?? 92))),
        'maxDimension' => min(4096, max(800, (int) ($cfg['max_dimension'] ?? 2560))),
        'scopes' => [
            'products' => true,
            'site' => true,
            'personalise' => false,
        ],
        'reoptimizeExistingWebp' => false,
        'updateDatabasePaths' => true,
    ];
}

function tm_converter_settings_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'converter-settings.json';
}

/** @return array<string, mixed> */
function tm_converter_load_settings(): array
{
    $defaults = tm_converter_settings_defaults();
    $path = tm_converter_settings_path();
    if (!is_file($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $defaults;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    $scopes = is_array($decoded['scopes'] ?? null) ? $decoded['scopes'] : [];
    $mergedScopes = array_merge($defaults['scopes'], $scopes);

    return [
        'webpQuality' => min(100, max(80, (int) ($decoded['webpQuality'] ?? $defaults['webpQuality']))),
        'maxDimension' => min(4096, max(800, (int) ($decoded['maxDimension'] ?? $defaults['maxDimension']))),
        'scopes' => [
            'products' => !empty($mergedScopes['products']),
            'site' => !empty($mergedScopes['site']),
            'personalise' => !empty($mergedScopes['personalise']),
        ],
        'reoptimizeExistingWebp' => !empty($decoded['reoptimizeExistingWebp']),
        'updateDatabasePaths' => !isset($decoded['updateDatabasePaths']) || !empty($decoded['updateDatabasePaths']),
    ];
}

/** @return array{quality: int, max_dimension: int, reoptimizeExistingWebp: bool} */
function tm_converter_upload_options(): array
{
    $s = tm_converter_load_settings();

    return [
        'quality' => (int) $s['webpQuality'],
        'max_dimension' => (int) $s['maxDimension'],
        'reoptimizeExistingWebp' => (bool) $s['reoptimizeExistingWebp'],
    ];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, settings?: array}
 */
function tm_converter_save_settings(array $body): array
{
    $current = tm_converter_load_settings();

    if (isset($body['webpQuality'])) {
        $current['webpQuality'] = min(100, max(80, (int) $body['webpQuality']));
    }
    if (isset($body['maxDimension'])) {
        $current['maxDimension'] = min(4096, max(800, (int) $body['maxDimension']));
    }
    if (isset($body['scopes']) && is_array($body['scopes'])) {
        foreach (['products', 'site', 'personalise'] as $key) {
            if (array_key_exists($key, $body['scopes'])) {
                $current['scopes'][$key] = !empty($body['scopes'][$key]);
            }
        }
    }
    if (array_key_exists('reoptimizeExistingWebp', $body)) {
        $current['reoptimizeExistingWebp'] = !empty($body['reoptimizeExistingWebp']);
    }
    if (array_key_exists('updateDatabasePaths', $body)) {
        $current['updateDatabasePaths'] = !empty($body['updateDatabasePaths']);
    }

    $path = tm_converter_settings_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create data directory'];
    }

    $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($path, $json) === false) {
        return ['ok' => false, 'message' => 'Could not save converter settings'];
    }

    return ['ok' => true, 'settings' => $current];
}
