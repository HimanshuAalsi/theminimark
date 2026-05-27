<?php

declare(strict_types=1);

/**
 * @return list<array<string, mixed>>
 */
function tm_products_public_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $out[] = tm_product_public($row);
    }
    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_product_public(array $row): array
{
    $price = (float) $row['price'];
    $compare = $row['compare_at'] !== null ? (float) $row['compare_at'] : $price;

    return [
        'id' => (string) $row['id'],
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'description' => $row['description'] !== null ? (string) $row['description'] : null,
        'image' => (string) $row['image_url'],
        'imageUrl' => (string) $row['image_url'],
        'price' => $price,
        'compareAt' => $compare,
        'category' => (string) $row['category'],
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

    if (!empty($filters['q'])) {
        $where[] = '(name LIKE :q OR slug LIKE :q)';
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
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}
