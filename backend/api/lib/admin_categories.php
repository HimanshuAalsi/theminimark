<?php

declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

function tm_categories_table_exists(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'categories'"
    );
    $cache = $st !== false && (int) $st->fetchColumn() > 0;
    return $cache;
}

/** @return list<string> */
function tm_category_slugs(PDO $pdo): array
{
    if (!tm_categories_table_exists($pdo)) {
        return ['bookmarks', 'cards', 'calendars', 'magnets', 'hampers'];
    }
    $st = $pdo->query('SELECT slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
    $slugs = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $slugs[] = (string) $row['slug'];
    }
    return $slugs !== [] ? $slugs : ['bookmarks', 'cards', 'calendars', 'magnets', 'hampers'];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_admin_category_row(array $row): array
{
    $img = (string) ($row['image_path'] ?? '');
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'description' => $row['description'] !== null ? (string) $row['description'] : '',
        'keywords' => $row['keywords'] !== null ? (string) $row['keywords'] : '',
        'imagePath' => $img,
        'imageUrl' => $img !== '' ? tm_upload_resolve_url($img) : '',
        'sortOrder' => (int) $row['sort_order'],
        'isActive' => (bool) (int) $row['is_active'],
        'productCount' => (int) ($row['product_count'] ?? 0),
    ];
}

/** @return array{items: list<array<string, mixed>>} */
function tm_admin_categories_list(PDO $pdo): array
{
    if (!tm_categories_table_exists($pdo)) {
        $items = [];
        foreach (['bookmarks', 'cards', 'calendars', 'magnets', 'hampers'] as $i => $slug) {
            $items[] = [
                'id' => $i + 1,
                'slug' => $slug,
                'name' => ucfirst($slug),
                'description' => '',
                'keywords' => '',
                'imagePath' => '',
                'imageUrl' => '',
                'sortOrder' => ($i + 1) * 10,
                'isActive' => true,
                'productCount' => 0,
            ];
        }
        return ['items' => $items];
    }

    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category = c.slug) AS product_count
            FROM categories c ORDER BY c.sort_order ASC, c.name ASC';
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $items = [];
    foreach ($rows as $row) {
        $items[] = tm_admin_category_row($row);
    }
    return ['items' => $items];
}

function tm_admin_category_by_id(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : tm_admin_category_row($row);
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, category?: array}
 */
function tm_admin_category_create(PDO $pdo, array $body): array
{
    if (!tm_categories_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Run migration_admin_advanced.sql first'];
    }
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        return ['ok' => false, 'message' => 'Name is required'];
    }
    $slug = trim((string) ($body['slug'] ?? ''));
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name) ?? 'cat');
    }
    $slug = substr(trim($slug, '-'), 0, 64);

    try {
        $st = $pdo->prepare(
            'INSERT INTO categories (slug, name, description, keywords, image_path, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $slug,
            $name,
            trim((string) ($body['description'] ?? '')) ?: null,
            trim((string) ($body['keywords'] ?? '')) ?: null,
            trim((string) ($body['imagePath'] ?? '')) ?: null,
            (int) ($body['sortOrder'] ?? 0),
            !empty($body['isActive']) ? 1 : 1,
        ]);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Category slug already exists'];
        }
        throw $e;
    }
    $id = (int) $pdo->lastInsertId();
    return ['ok' => true, 'category' => tm_admin_category_by_id($pdo, $id)];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, category?: array}
 */
function tm_admin_category_update(PDO $pdo, int $id, array $body): array
{
    $existing = tm_admin_category_by_id($pdo, $id);
    if ($existing === null) {
        return ['ok' => false, 'message' => 'Category not found'];
    }
    $oldSlug = (string) $existing['slug'];
    $slug = array_key_exists('slug', $body) ? trim((string) $body['slug']) : $oldSlug;
    $name = array_key_exists('name', $body) ? trim((string) $body['name']) : $existing['name'];

    try {
        $st = $pdo->prepare(
            'UPDATE categories SET slug = ?, name = ?, description = ?, keywords = ?, image_path = ?,
             sort_order = ?, is_active = ? WHERE id = ?'
        );
        $st->execute([
            substr($slug, 0, 64),
            $name,
            trim((string) ($body['description'] ?? $existing['description'])) ?: null,
            trim((string) ($body['keywords'] ?? $existing['keywords'])) ?: null,
            trim((string) ($body['imagePath'] ?? $existing['imagePath'])) ?: null,
            (int) ($body['sortOrder'] ?? $existing['sortOrder']),
            array_key_exists('isActive', $body) ? (!empty($body['isActive']) ? 1 : 0) : ($existing['isActive'] ? 1 : 0),
            $id,
        ]);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Slug already in use'];
        }
        throw $e;
    }

    if ($slug !== $oldSlug) {
        $pdo->prepare('UPDATE products SET category = ? WHERE category = ?')->execute([$slug, $oldSlug]);
    }

    return ['ok' => true, 'category' => tm_admin_category_by_id($pdo, $id)];
}

/** @return array{ok: bool, message?: string} */
function tm_admin_category_delete(PDO $pdo, int $id): array
{
    $cat = tm_admin_category_by_id($pdo, $id);
    if ($cat === null) {
        return ['ok' => false, 'message' => 'Category not found'];
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category = ?');
    $st->execute([$cat['slug']]);
    if ((int) $st->fetchColumn() > 0) {
        return ['ok' => false, 'message' => 'Cannot delete: products still use this category'];
    }
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    return ['ok' => true];
}
