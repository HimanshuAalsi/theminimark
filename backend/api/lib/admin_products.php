<?php

declare(strict_types=1);

require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/product_images.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/admin_categories.php';
require_once __DIR__ . '/product_images_store.php';

/** @return list<string> */
function tm_admin_product_categories(PDO $pdo): array
{
    return tm_category_slugs($pdo);
}

function tm_admin_slugify(string $name): string
{
    $s = strtolower(trim($name));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? substr($s, 0, 180) : 'product';
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_admin_product_row(PDO $pdo, array $row): array
{
    $id = (string) $row['id'];
    $image = (string) ($row['image_url'] ?? '');
    if ($image !== '' && !preg_match('#^https?://#i', $image)) {
        $image = tm_upload_resolve_url($image);
    } else {
        $image = tm_resolve_product_image_url($image, (string) $row['slug'], (string) $row['category']);
    }

    $images = [];
    try {
        $images = tm_product_images_list($pdo, $id);
    } catch (PDOException) {
    }
    if ($images === [] && $image !== '') {
        $images = [[
            'id' => 0,
            'path' => (string) $row['image_url'],
            'url' => $image,
            'sortOrder' => 0,
            'isPrimary' => true,
        ]];
    }

    return [
        'id' => $id,
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'description' => $row['description'] !== null ? (string) $row['description'] : '',
        'features' => tm_product_features_decode($row['features'] ?? null),
        'keywords' => $row['keywords'] ?? '',
        'price' => (float) $row['price'],
        'compareAt' => $row['compare_at'] !== null ? (float) $row['compare_at'] : null,
        'category' => (string) $row['category'],
        'subcategory' => isset($row['subcategory']) && $row['subcategory'] !== null && $row['subcategory'] !== ''
            ? (string) $row['subcategory'] : '',
        'imageUrl' => $image,
        'imagePath' => (string) $row['image_url'],
        'images' => $images,
        'sku' => $row['sku'] ?? '',
        'stockQuantity' => $row['stock_quantity'] !== null ? (int) $row['stock_quantity'] : null,
        'seoTitle' => $row['seo_title'] ?? '',
        'seoDescription' => $row['seo_description'] ?? '',
        'homeBestseller' => (bool) (int) $row['home_bestseller'],
        'homeSecondary' => (bool) (int) $row['home_secondary'],
        'isActive' => (bool) (int) $row['is_active'],
        'sortOrder' => (int) $row['sort_order'],
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
    ];
}

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_admin_products_list(PDO $pdo, array $query): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($query['category']) && $query['category'] !== 'all') {
        $where[] = 'category = :category';
        $params[':category'] = (string) $query['category'];
    }
    if (!empty($query['q'])) {
        $where[] = '(name LIKE :q OR slug LIKE :q OR id LIKE :q OR keywords LIKE :q OR sku LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }
    if (isset($query['active']) && $query['active'] !== '' && $query['active'] !== 'all') {
        $where[] = 'is_active = :active';
        $params[':active'] = $query['active'] === '1' || $query['active'] === 'true' ? 1 : 0;
    }
    if (!empty($query['lowStock'])) {
        $where[] = 'stock_quantity IS NOT NULL AND stock_quantity <= 5';
    }
    $stock = (string) ($query['stock'] ?? 'all');
    if ($stock === 'out') {
        $where[] = 'stock_quantity IS NOT NULL AND stock_quantity = 0';
    } elseif ($stock === 'low') {
        $where[] = 'stock_quantity IS NOT NULL AND stock_quantity > 0 AND stock_quantity <= 5';
    } elseif ($stock === 'in') {
        $where[] = 'stock_quantity IS NOT NULL AND stock_quantity > 5';
    } elseif ($stock === 'untracked') {
        $where[] = 'stock_quantity IS NULL';
    }
    $featured = (string) ($query['featured'] ?? 'all');
    if ($featured === 'bestseller') {
        $where[] = 'home_bestseller = 1';
    } elseif ($featured === 'secondary') {
        $where[] = 'home_secondary = 1';
    } elseif ($featured === 'home') {
        $where[] = '(home_bestseller = 1 OR home_secondary = 1)';
    }

    $sortMap = [
        'id' => 'id',
        'sku' => 'sku',
        'slug' => 'slug',
        'name' => 'name',
        'category' => 'category',
        'price' => 'price',
        'stock' => 'stock_quantity',
        'sortOrder' => 'sort_order',
        'created' => 'created_at',
        'updated' => 'updated_at',
    ];
    $sortBy = (string) ($query['sortBy'] ?? 'sortOrder');
    $sortCol = $sortMap[$sortBy] ?? 'sort_order';
    $sortDir = strtolower((string) ($query['sortDir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
    if ($sortCol === 'stock_quantity') {
        $orderSql = 'stock_quantity IS NULL ' . ($sortDir === 'ASC' ? 'ASC' : 'DESC')
            . ', stock_quantity ' . $sortDir . ', name ASC';
    } elseif ($sortCol === 'sku') {
        $orderSql = '(sku IS NULL OR sku = \'\') ' . ($sortDir === 'ASC' ? 'ASC' : 'DESC')
            . ', sku ' . $sortDir . ', name ASC';
    } else {
        $orderSql = $sortCol . ' ' . $sortDir . ', name ASC';
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(100, max(10, (int) ($query['perPage'] ?? 25)));
    $offset = ($page - 1) * $perPage;

    $countSql = 'SELECT COUNT(*) FROM products WHERE ' . implode(' AND ', $where);
    $st = $pdo->prepare($countSql);
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = 'SELECT * FROM products WHERE ' . implode(' AND ', $where)
        . ' ORDER BY ' . $orderSql . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = tm_admin_product_row($pdo, $row);
    }

    return [
        'items' => $items,
        'meta' => ['count' => count($items), 'total' => $total, 'page' => $page, 'perPage' => $perPage],
    ];
}

function tm_admin_product_by_id(PDO $pdo, string $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : tm_admin_product_row($pdo, $row);
}

/** @param array<string, mixed> $body */
function tm_admin_product_extract_images(array $body, string $category): array
{
    if (isset($body['images']) && is_array($body['images'])) {
        return $body['images'];
    }
    $imagePath = trim((string) ($body['imagePath'] ?? $body['imageUrl'] ?? ''));
    if ($imagePath === '') {
        return [];
    }
    if (!preg_match('#^https?://#i', $imagePath) && !str_starts_with($imagePath, '/uploads/')) {
        $imagePath = '/uploads/products/' . $category . '/' . basename($imagePath);
    }
    return [['path' => $imagePath, 'sortOrder' => 0, 'isPrimary' => true]];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, product?: array}
 */
function tm_admin_product_create(PDO $pdo, array $body): array
{
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        return ['ok' => false, 'message' => 'Name is required'];
    }
    $category = (string) ($body['category'] ?? 'bookmarks');
    if (!in_array($category, tm_admin_product_categories($pdo), true)) {
        return ['ok' => false, 'message' => 'Invalid category'];
    }

    $images = tm_admin_product_extract_images($body, $category);
    if ($images === []) {
        return ['ok' => false, 'message' => 'At least one product image is required'];
    }

    $id = trim((string) ($body['id'] ?? ''));
    if ($id === '') {
        $id = substr(tm_admin_slugify($name), 0, 28) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
    $id = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: 'p1', 0, 32);

    $slug = trim((string) ($body['slug'] ?? ''));
    if ($slug === '') {
        $slug = tm_admin_slugify($name);
    }
    $slug = substr($slug, 0, 191);

    $price = round((float) ($body['price'] ?? 0), 2);
    if ($price < 0) {
        return ['ok' => false, 'message' => 'Invalid price'];
    }
    $compare = isset($body['compareAt']) && $body['compareAt'] !== null && $body['compareAt'] !== ''
        ? round((float) $body['compareAt'], 2) : null;

    $primary = (string) ($images[0]['path'] ?? $images[0]['imagePath'] ?? '');
    $subcategory = trim((string) ($body['subcategory'] ?? ''));
    $subcategory = $subcategory !== '' ? substr($subcategory, 0, 64) : null;
    $features = tm_product_features_encode(
        is_array($body['features'] ?? null) ? $body['features'] : []
    );

    try {
        $pdo->prepare(
            'INSERT INTO products (id, slug, name, description, features, keywords, price, compare_at, category, subcategory, image_url,
             home_bestseller, home_secondary, is_active, sort_order, sku, stock_quantity, seo_title, seo_description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $id, $slug, $name,
            trim((string) ($body['description'] ?? '')) ?: null,
            $features,
            trim((string) ($body['keywords'] ?? '')) ?: null,
            $price, $compare, $category, $subcategory, substr($primary, 0, 2048),
            !empty($body['homeBestseller']) ? 1 : 0,
            !empty($body['homeSecondary']) ? 1 : 0,
            array_key_exists('isActive', $body) ? (!empty($body['isActive']) ? 1 : 0) : 1,
            (int) ($body['sortOrder'] ?? 0),
            trim((string) ($body['sku'] ?? '')) ?: null,
            isset($body['stockQuantity']) && $body['stockQuantity'] !== '' ? (int) $body['stockQuantity'] : null,
            trim((string) ($body['seoTitle'] ?? '')) ?: null,
            trim((string) ($body['seoDescription'] ?? '')) ?: null,
        ]);
        tm_product_images_sync($pdo, $id, $images);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Product id or slug already exists'];
        }
        throw $e;
    }

    return ['ok' => true, 'product' => tm_admin_product_by_id($pdo, $id)];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, product?: array}
 */
function tm_admin_product_update(PDO $pdo, string $id, array $body): array
{
    $existing = tm_admin_product_by_id($pdo, $id);
    if ($existing === null) {
        return ['ok' => false, 'message' => 'Product not found'];
    }

    $name = array_key_exists('name', $body) ? trim((string) $body['name']) : $existing['name'];
    $category = array_key_exists('category', $body) ? (string) $body['category'] : $existing['category'];
    if (!in_array($category, tm_admin_product_categories($pdo), true)) {
        return ['ok' => false, 'message' => 'Invalid category'];
    }

    $imagePath = $existing['imagePath'];
    if (isset($body['images']) && is_array($body['images'])) {
        tm_product_images_sync($pdo, $id, $body['images']);
        $refreshed = tm_admin_product_by_id($pdo, $id);
        $imagePath = $refreshed['imagePath'] ?? $imagePath;
    } elseif (array_key_exists('imagePath', $body) || array_key_exists('imageUrl', $body)) {
        $imagePath = trim((string) ($body['imagePath'] ?? $body['imageUrl'] ?? ''));
        tm_product_images_sync($pdo, $id, [['path' => $imagePath, 'isPrimary' => true, 'sortOrder' => 0]]);
    }

    try {
        $subcategory = array_key_exists('subcategory', $body)
            ? (trim((string) $body['subcategory']) !== '' ? substr(trim((string) $body['subcategory']), 0, 64) : null)
            : ($existing['subcategory'] !== '' ? $existing['subcategory'] : null);
        $features = array_key_exists('features', $body) && is_array($body['features'])
            ? tm_product_features_encode($body['features'])
            : tm_product_features_encode($existing['features'] ?? []);

        $pdo->prepare(
            'UPDATE products SET slug = ?, name = ?, description = ?, features = ?, keywords = ?, price = ?, compare_at = ?,
             category = ?, subcategory = ?, image_url = ?, home_bestseller = ?, home_secondary = ?, is_active = ?, sort_order = ?,
             sku = ?, stock_quantity = ?, seo_title = ?, seo_description = ? WHERE id = ?'
        )->execute([
            substr(array_key_exists('slug', $body) ? (string) $body['slug'] : $existing['slug'], 0, 191),
            $name,
            trim((string) ($body['description'] ?? $existing['description'])) ?: null,
            $features,
            trim((string) ($body['keywords'] ?? $existing['keywords'])) ?: null,
            array_key_exists('price', $body) ? round((float) $body['price'], 2) : $existing['price'],
            array_key_exists('compareAt', $body)
                ? ($body['compareAt'] === null || $body['compareAt'] === '' ? null : round((float) $body['compareAt'], 2))
                : $existing['compareAt'],
            $category,
            $subcategory,
            substr($imagePath, 0, 2048),
            array_key_exists('homeBestseller', $body) ? (!empty($body['homeBestseller']) ? 1 : 0) : ($existing['homeBestseller'] ? 1 : 0),
            array_key_exists('homeSecondary', $body) ? (!empty($body['homeSecondary']) ? 1 : 0) : ($existing['homeSecondary'] ? 1 : 0),
            array_key_exists('isActive', $body) ? (!empty($body['isActive']) ? 1 : 0) : ($existing['isActive'] ? 1 : 0),
            array_key_exists('sortOrder', $body) ? (int) $body['sortOrder'] : $existing['sortOrder'],
            trim((string) ($body['sku'] ?? $existing['sku'])) ?: null,
            array_key_exists('stockQuantity', $body)
                ? ($body['stockQuantity'] === '' || $body['stockQuantity'] === null ? null : (int) $body['stockQuantity'])
                : $existing['stockQuantity'],
            trim((string) ($body['seoTitle'] ?? $existing['seoTitle'])) ?: null,
            trim((string) ($body['seoDescription'] ?? $existing['seoDescription'])) ?: null,
            $id,
        ]);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Slug already in use'];
        }
        throw $e;
    }

    return ['ok' => true, 'product' => tm_admin_product_by_id($pdo, $id)];
}

/** @return array{ok: bool, message?: string} */
function tm_admin_product_delete(PDO $pdo, string $id): array
{
    $st = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $st->execute([$id]);
    if ($st->rowCount() === 0) {
        return ['ok' => false, 'message' => 'Product not found'];
    }
    return ['ok' => true];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, updated?: int, deleted?: int}
 */
function tm_admin_products_bulk(PDO $pdo, array $body): array
{
    $rawIds = $body['ids'] ?? [];
    if (!is_array($rawIds) || $rawIds === []) {
        return ['ok' => false, 'message' => 'No products selected'];
    }

    $ids = [];
    foreach ($rawIds as $rawId) {
        $id = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $rawId) ?: '', 0, 32);
        if ($id !== '') {
            $ids[$id] = $id;
        }
    }
    $ids = array_values($ids);
    if ($ids === []) {
        return ['ok' => false, 'message' => 'No valid product ids'];
    }

    $action = (string) ($body['action'] ?? '');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if ($action === 'activate') {
        $st = $pdo->prepare('UPDATE products SET is_active = 1 WHERE id IN (' . $placeholders . ')');
        $st->execute($ids);
        return ['ok' => true, 'updated' => $st->rowCount()];
    }
    if ($action === 'deactivate') {
        $st = $pdo->prepare('UPDATE products SET is_active = 0 WHERE id IN (' . $placeholders . ')');
        $st->execute($ids);
        return ['ok' => true, 'updated' => $st->rowCount()];
    }
    if ($action === 'delete') {
        $st = $pdo->prepare('DELETE FROM products WHERE id IN (' . $placeholders . ')');
        $st->execute($ids);
        return ['ok' => true, 'deleted' => $st->rowCount()];
    }

    return ['ok' => false, 'message' => 'Unknown bulk action'];
}
