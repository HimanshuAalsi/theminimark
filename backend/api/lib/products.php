<?php

declare(strict_types=1);

require_once __DIR__ . '/product_images.php';
require_once __DIR__ . '/product_images_store.php';

/**
 * @return list<string>
 */
function tm_product_features_decode(mixed $raw): array
{
    if ($raw === null || $raw === '') {
        return [];
    }
    if (is_array($raw)) {
        $decoded = $raw;
    } else {
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }
    }
    $out = [];
    foreach ($decoded as $item) {
        $s = trim((string) $item);
        if ($s !== '') {
            $out[] = $s;
        }
    }
    return $out;
}

/**
 * @param list<string> $features
 */
function tm_product_features_encode(array $features): ?string
{
    $clean = [];
    foreach ($features as $item) {
        $s = trim((string) $item);
        if ($s !== '') {
            $clean[] = $s;
        }
    }
    if ($clean === []) {
        return null;
    }
    return json_encode(array_values($clean), JSON_UNESCAPED_UNICODE);
}

/**
 * @return list<array<string, mixed>>
 */
function tm_products_public_rows(array $rows, ?PDO $pdo = null): array
{
    $out = [];
    foreach ($rows as $row) {
        $out[] = tm_product_public($row, $pdo);
    }
    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_product_public(array $row, ?PDO $pdo = null): array
{
    $price = (float) $row['price'];
    $compare = $row['compare_at'] !== null ? (float) $row['compare_at'] : $price;
    $id = (string) $row['id'];
    $slug = (string) $row['slug'];
    $category = (string) $row['category'];
    $fallback = (string) $row['image_url'];

    $images = $pdo !== null
        ? tm_product_images_public_urls($pdo, $id, $fallback, $slug, $category)
        : [];
    $image = $images[0] ?? tm_resolve_product_image_url($fallback, $slug, $category);
    if ($images === [] && $image !== '') {
        $images = [$image];
    }

    return [
        'id' => $id,
        'slug' => $slug,
        'name' => (string) $row['name'],
        'description' => $row['description'] !== null ? (string) $row['description'] : null,
        'features' => tm_product_features_decode($row['features'] ?? null),
        'image' => $image,
        'imageUrl' => $image,
        'images' => $images,
        'price' => $price,
        'compareAt' => $compare,
        'category' => $category,
        'subcategory' => isset($row['subcategory']) && $row['subcategory'] !== null
            ? (string) $row['subcategory'] : null,
        'homeBestseller' => (bool) (int) $row['home_bestseller'],
        'homeSecondary' => (bool) (int) $row['home_secondary'],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function tm_products_query(PDO $pdo, array $filters): array
{
    $where = ['is_active = 1'];
    $params = [];

    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $where[] = 'category = :category';
        $params[':category'] = (string) $filters['category'];
    }

    if (!empty($filters['subcategory'])) {
        $where[] = 'subcategory = :subcategory';
        $params[':subcategory'] = (string) $filters['subcategory'];
    }

    if (!empty($filters['q'])) {
        $where[] = '(name LIKE :q OR slug LIKE :q OR keywords LIKE :q OR description LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['q']) . '%';
    }

    if (!empty($filters['home_bestseller'])) {
        $where[] = 'home_bestseller = 1';
    }

    if (!empty($filters['home_secondary'])) {
        $where[] = 'home_secondary = 1';
    }

    $order = 'sort_order ASC, name ASC';
    $sort = (string) ($filters['sort'] ?? 'featured');
    if ($sort === 'price-asc') {
        $order = 'price ASC, name ASC';
    } elseif ($sort === 'price-desc') {
        $order = 'price DESC, name ASC';
    } elseif ($sort === 'name') {
        $order = 'name ASC';
    }

    $sql = 'SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    /** @var list<array<string, mixed>> */
    return $stmt->fetchAll();
}

function tm_product_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug AND is_active = 1 LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}
