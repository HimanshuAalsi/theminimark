<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function tm_site_payload(): array
{
    $path = __DIR__ . '/../data/site-default.json';
    if (!is_readable($path)) {
        return ['announcement' => ''];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return ['announcement' => ''];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['announcement' => ''];
}
