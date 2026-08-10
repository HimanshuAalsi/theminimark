<?php

declare(strict_types=1);

require_once __DIR__ . '/products.php';

function tm_feature_collections_defaults_path(): string
{
    return __DIR__ . '/../data/feature-collections.default.json';
}

function tm_feature_collections_save_path(): string
{
    return __DIR__ . '/../data/feature-collections.json';
}

/**
 * @return list<string>
 */
function tm_feature_collection_normalize_features(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $item) {
        $s = trim((string) $item);
        if ($s !== '') {
            $out[] = $s;
        }
    }
    return array_values($out);
}

/**
 * @param array<string, mixed> $row
 * @return array{id: string, name: string, category: string, features: list<string>, updatedAt: string|null}|null
 */
function tm_feature_collection_normalize(array $row): ?array
{
    $id = trim((string) ($row['id'] ?? ''));
    $name = trim((string) ($row['name'] ?? ''));
    if ($id === '' || $name === '') {
        return null;
    }
    $category = trim((string) ($row['category'] ?? ''));
    $features = tm_feature_collection_normalize_features($row['features'] ?? []);
    $updatedAt = isset($row['updatedAt']) ? trim((string) $row['updatedAt']) : null;

    return [
        'id' => substr($id, 0, 64),
        'name' => substr($name, 0, 120),
        'category' => substr($category, 0, 32),
        'features' => $features,
        'updatedAt' => $updatedAt !== '' ? $updatedAt : null,
    ];
}

/**
 * @return array{collections: list<array{id: string, name: string, category: string, features: list<string>, updatedAt: string|null}>}
 */
function tm_feature_collections_load(): array
{
    $path = tm_feature_collections_save_path();
    if (is_readable($path)) {
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data) && isset($data['collections']) && is_array($data['collections'])) {
            return ['collections' => tm_feature_collections_normalize_list($data['collections'])];
        }
    }

    $defPath = tm_feature_collections_defaults_path();
    if (is_readable($defPath)) {
        $raw = file_get_contents($defPath);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data) && isset($data['collections']) && is_array($data['collections'])) {
            return ['collections' => tm_feature_collections_normalize_list($data['collections'])];
        }
    }

    return ['collections' => []];
}

/**
 * @param list<mixed> $rows
 * @return list<array{id: string, name: string, category: string, features: list<string>, updatedAt: string|null}>
 */
function tm_feature_collections_normalize_list(array $rows): array
{
    $out = [];
    $seen = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $norm = tm_feature_collection_normalize($row);
        if ($norm === null || isset($seen[$norm['id']])) {
            continue;
        }
        $seen[$norm['id']] = true;
        $out[] = $norm;
    }
    return $out;
}

/**
 * @param array{collections: list<array<string, mixed>>} $payload
 * @return array{ok: bool, message?: string, collections?: list<array<string, mixed>>}
 */
function tm_feature_collections_persist(array $payload): array
{
    $collections = tm_feature_collections_normalize_list($payload['collections'] ?? []);
    $path = tm_feature_collections_save_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create data directory'];
    }

    $json = json_encode(['collections' => $collections], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($path, $json) === false) {
        return ['ok' => false, 'message' => 'Could not save feature collections'];
    }

    return ['ok' => true, 'collections' => $collections];
}

function tm_feature_collection_new_id(): string
{
    return 'fc_' . bin2hex(random_bytes(6));
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, collection?: array<string, mixed>, collections?: list<array<string, mixed>>}
 */
function tm_admin_feature_collection_create(array $body): array
{
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        return ['ok' => false, 'message' => 'Collection name is required'];
    }

    $data = tm_feature_collections_load();
    $collections = $data['collections'];

    $collection = [
        'id' => tm_feature_collection_new_id(),
        'name' => substr($name, 0, 120),
        'category' => substr(trim((string) ($body['category'] ?? '')), 0, 32),
        'features' => tm_feature_collection_normalize_features($body['features'] ?? []),
        'updatedAt' => gmdate('c'),
    ];

    $collections[] = $collection;
    $saved = tm_feature_collections_persist(['collections' => $collections]);
    if (!$saved['ok']) {
        return $saved;
    }

    return ['ok' => true, 'collection' => $collection, 'collections' => $saved['collections'] ?? $collections];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, collection?: array<string, mixed>, collections?: list<array<string, mixed>>}
 */
function tm_admin_feature_collection_update(string $id, array $body): array
{
    $data = tm_feature_collections_load();
    $collections = $data['collections'];
    $found = false;
    $updated = null;

    foreach ($collections as $i => $col) {
        if ($col['id'] !== $id) {
            continue;
        }
        $found = true;
        if (array_key_exists('name', $body)) {
            $name = trim((string) $body['name']);
            if ($name === '') {
                return ['ok' => false, 'message' => 'Collection name cannot be empty'];
            }
            $collections[$i]['name'] = substr($name, 0, 120);
        }
        if (array_key_exists('category', $body)) {
            $collections[$i]['category'] = substr(trim((string) $body['category']), 0, 32);
        }
        if (array_key_exists('features', $body)) {
            $collections[$i]['features'] = tm_feature_collection_normalize_features($body['features']);
        }
        $collections[$i]['updatedAt'] = gmdate('c');
        $updated = $collections[$i];
        break;
    }

    if (!$found) {
        return ['ok' => false, 'message' => 'Collection not found'];
    }

    $saved = tm_feature_collections_persist(['collections' => $collections]);
    if (!$saved['ok']) {
        return $saved;
    }

    return ['ok' => true, 'collection' => $updated, 'collections' => $saved['collections'] ?? $collections];
}

/**
 * @return array{ok: bool, message?: string, collections?: list<array<string, mixed>>}
 */
function tm_admin_feature_collection_delete(string $id): array
{
    $data = tm_feature_collections_load();
    $before = count($data['collections']);
    $collections = array_values(array_filter(
        $data['collections'],
        static fn (array $col): bool => $col['id'] !== $id,
    ));

    if (count($collections) === $before) {
        return ['ok' => false, 'message' => 'Collection not found'];
    }

    $saved = tm_feature_collections_persist(['collections' => $collections]);
    if (!$saved['ok']) {
        return $saved;
    }

    return ['ok' => true, 'collections' => $saved['collections'] ?? $collections];
}
