<?php

declare(strict_types=1);

function tm_subcategories_table_exists(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'subcategories'"
    );
    $cache = $st !== false && (int) $st->fetchColumn() > 0;
    return $cache;
}

/**
 * @return list<array{id: int, categorySlug: string, slug: string, name: string, sortOrder: int, isActive: bool}>
 */
function tm_subcategories_static_defaults(): array
{
    $rows = [
        ['bookmarks', 'magnetic', 'Magnetic bookmarks', 10],
        ['bookmarks', 'classic', 'Classic bookmarks', 20],
        ['cards', 'birthday', 'Birthday cards', 10],
        ['cards', 'thank-you', 'Thank you cards', 20],
        ['cards', 'love', 'Love cards', 30],
        ['cards', 'sorry', 'Sorry cards', 40],
        ['calendars', 'desk', 'Desk calendars', 10],
        ['calendars', 'wall', 'Wall calendars', 20],
        ['magnets', 'photo', 'Photo magnets', 10],
        ['magnets', 'quote', 'Quote magnets', 20],
        ['magnets', 'couple', 'Couple magnets', 30],
        ['hampers', 'mini', 'Mini hampers', 10],
        ['hampers', 'premium', 'Premium hampers', 20],
        ['hampers', 'gift-sets', 'Gift sets', 30],
    ];
    $out = [];
    $i = 1;
    foreach ($rows as [$cat, $slug, $name, $sort]) {
        $out[] = [
            'id' => $i++,
            'categorySlug' => $cat,
            'slug' => $slug,
            'name' => $name,
            'sortOrder' => $sort,
            'isActive' => true,
        ];
    }
    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_subcategory_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'categorySlug' => (string) $row['category_slug'],
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'sortOrder' => (int) $row['sort_order'],
        'isActive' => (bool) (int) $row['is_active'],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function tm_subcategories_list(PDO $pdo, ?string $categorySlug = null, bool $activeOnly = false): array
{
    if (!tm_subcategories_table_exists($pdo)) {
        $all = tm_subcategories_static_defaults();
        if ($categorySlug !== null && $categorySlug !== '') {
            $all = array_values(array_filter($all, static fn ($s) => $s['categorySlug'] === $categorySlug));
        }
        if ($activeOnly) {
            $all = array_values(array_filter($all, static fn ($s) => $s['isActive']));
        }
        return $all;
    }

    $where = ['1=1'];
    $params = [];
    if ($categorySlug !== null && $categorySlug !== '') {
        $where[] = 'category_slug = :cat';
        $params[':cat'] = $categorySlug;
    }
    if ($activeOnly) {
        $where[] = 'is_active = 1';
    }
    $sql = 'SELECT * FROM subcategories WHERE ' . implode(' AND ', $where)
        . ' ORDER BY category_slug ASC, sort_order ASC, name ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $row) {
        $out[] = tm_subcategory_row($row);
    }
    return $out;
}

/**
 * Categories with nested subcategories for storefront.
 *
 * @param list<array<string, mixed>> $categories
 * @return list<array<string, mixed>>
 */
function tm_categories_with_subcategories(PDO $pdo, array $categories): array
{
    $subs = tm_subcategories_list($pdo, null, true);
    $byCat = [];
    foreach ($subs as $sub) {
        $cat = (string) $sub['categorySlug'];
        if (!isset($byCat[$cat])) {
            $byCat[$cat] = [];
        }
        $byCat[$cat][] = $sub;
    }
    $out = [];
    foreach ($categories as $cat) {
        $slug = (string) ($cat['slug'] ?? '');
        $out[] = array_merge($cat, ['subcategories' => $byCat[$slug] ?? []]);
    }
    return $out;
}

function tm_subcategory_by_id(PDO $pdo, int $id): ?array
{
    if (!tm_subcategories_table_exists($pdo)) {
        foreach (tm_subcategories_static_defaults() as $s) {
            if ($s['id'] === $id) {
                return $s;
            }
        }
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM subcategories WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : tm_subcategory_row($row);
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, subcategory?: array<string, mixed>}
 */
function tm_admin_subcategory_create(PDO $pdo, array $body): array
{
    if (!tm_subcategories_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Run migration_subcategories_free_gifts.sql first'];
    }
    $categorySlug = trim((string) ($body['categorySlug'] ?? ''));
    $name = trim((string) ($body['name'] ?? ''));
    if ($categorySlug === '' || $name === '') {
        return ['ok' => false, 'message' => 'Category and name are required'];
    }
    $slug = trim((string) ($body['slug'] ?? ''));
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name) ?? 'sub');
    }
    $slug = substr(trim($slug, '-'), 0, 64);

    try {
        $st = $pdo->prepare(
            'INSERT INTO subcategories (category_slug, slug, name, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([
            substr($categorySlug, 0, 64),
            $slug,
            substr($name, 0, 128),
            (int) ($body['sortOrder'] ?? 0),
            !array_key_exists('isActive', $body) || !empty($body['isActive']) ? 1 : 0,
        ]);
    } catch (PDOException $e) {
        if (function_exists('tm_auth_pdo_is_duplicate_key') && tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Subcategory slug already exists for this category'];
        }
        throw $e;
    }
    $id = (int) $pdo->lastInsertId();
    return ['ok' => true, 'subcategory' => tm_subcategory_by_id($pdo, $id)];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, subcategory?: array<string, mixed>}
 */
function tm_admin_subcategory_update(PDO $pdo, int $id, array $body): array
{
    $existing = tm_subcategory_by_id($pdo, $id);
    if ($existing === null) {
        return ['ok' => false, 'message' => 'Subcategory not found'];
    }
    if (!tm_subcategories_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Run migration_subcategories_free_gifts.sql first'];
    }

    $st = $pdo->prepare(
        'UPDATE subcategories SET category_slug = ?, slug = ?, name = ?, sort_order = ?, is_active = ? WHERE id = ?'
    );
    $st->execute([
        substr(trim((string) ($body['categorySlug'] ?? $existing['categorySlug'])), 0, 64),
        substr(trim((string) ($body['slug'] ?? $existing['slug'])), 0, 64),
        substr(trim((string) ($body['name'] ?? $existing['name'])), 0, 128),
        (int) ($body['sortOrder'] ?? $existing['sortOrder']),
        array_key_exists('isActive', $body) ? (!empty($body['isActive']) ? 1 : 0) : ($existing['isActive'] ? 1 : 0),
        $id,
    ]);

    return ['ok' => true, 'subcategory' => tm_subcategory_by_id($pdo, $id)];
}

/** @return array{ok: bool, message?: string} */
function tm_admin_subcategory_delete(PDO $pdo, int $id): array
{
    if (!tm_subcategories_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Subcategory not found'];
    }
    $sub = tm_subcategory_by_id($pdo, $id);
    if ($sub === null) {
        return ['ok' => false, 'message' => 'Subcategory not found'];
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category = ? AND subcategory = ?');
    $st->execute([$sub['categorySlug'], $sub['slug']]);
    if ((int) $st->fetchColumn() > 0) {
        return ['ok' => false, 'message' => 'Cannot delete: products still use this subcategory'];
    }
    $pdo->prepare('DELETE FROM subcategories WHERE id = ?')->execute([$id]);
    return ['ok' => true];
}
