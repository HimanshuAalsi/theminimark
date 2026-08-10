<?php

declare(strict_types=1);

require_once __DIR__ . '/products.php';

function tm_free_gifts_defaults_path(): string
{
    return __DIR__ . '/../data/free-gifts.default.json';
}

function tm_free_gifts_save_path(): string
{
    return __DIR__ . '/../data/free-gifts.json';
}

/**
 * @return array{productIds: list<string>}
 */
function tm_free_gifts_load(): array
{
    $path = tm_free_gifts_save_path();
    if (is_readable($path)) {
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data) && isset($data['productIds']) && is_array($data['productIds'])) {
            return ['productIds' => tm_free_gifts_normalize_ids($data['productIds'])];
        }
    }
    $defPath = tm_free_gifts_defaults_path();
    if (is_readable($defPath)) {
        $raw = file_get_contents($defPath);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data) && isset($data['productIds']) && is_array($data['productIds'])) {
            return ['productIds' => tm_free_gifts_normalize_ids($data['productIds'])];
        }
    }
    return ['productIds' => []];
}

/**
 * @param list<mixed> $ids
 * @return list<string>
 */
function tm_free_gifts_normalize_ids(array $ids): array
{
    $out = [];
    foreach ($ids as $id) {
        $s = trim((string) $id);
        if ($s !== '') {
            $out[] = substr($s, 0, 32);
        }
    }
    return array_slice(array_values(array_unique($out)), 0, 4);
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, freeGifts?: array{productIds: list<string>}}
 */
function tm_admin_free_gifts_save(array $body): array
{
    $ids = isset($body['productIds']) && is_array($body['productIds'])
        ? tm_free_gifts_normalize_ids($body['productIds'])
        : [];

    $path = tm_free_gifts_save_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create data directory'];
    }

    $payload = ['productIds' => $ids];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($path, $json) === false) {
        return ['ok' => false, 'message' => 'Could not save free gift config'];
    }

    return ['ok' => true, 'freeGifts' => $payload];
}

/**
 * @return array{productIds: list<string>, options: list<array<string, mixed>>}
 */
function tm_free_gifts_public(PDO $pdo): array
{
    $ids = tm_free_gifts_load()['productIds'];
    $options = [];
    foreach ($ids as $id) {
        $st = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            continue;
        }
        $pub = tm_product_public($row, $pdo);
        $options[] = [
            'id' => (string) $pub['id'],
            'name' => (string) $pub['name'],
            'image' => (string) ($pub['image'] ?? ''),
            'slug' => (string) $pub['slug'],
        ];
    }
    return ['productIds' => $ids, 'options' => $options];
}
