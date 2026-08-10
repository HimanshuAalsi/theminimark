<?php

declare(strict_types=1);

require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/product_images.php';

/**
 * @return list<array{id: int, path: string, url: string, sortOrder: int, isPrimary: bool}>
 */
function tm_product_images_list(PDO $pdo, string $productId): array
{
    $st = $pdo->prepare(
        'SELECT id, image_path, sort_order, is_primary FROM product_images
         WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC'
    );
    $st->execute([$productId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $row) {
        $path = (string) $row['image_path'];
        $out[] = [
            'id' => (int) $row['id'],
            'path' => $path,
            'url' => tm_product_image_url_from_path($path),
            'sortOrder' => (int) $row['sort_order'],
            'isPrimary' => (bool) (int) $row['is_primary'],
        ];
    }
    return $out;
}

function tm_product_image_url_from_path(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (str_starts_with($path, '/uploads/')) {
        return tm_upload_resolve_url($path);
    }
    return $path;
}

/**
 * @param list<array{path?: string, imagePath?: string, sortOrder?: int, isPrimary?: bool}> $images
 */
function tm_product_images_sync(PDO $pdo, string $productId, array $images): void
{
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
    if ($images === []) {
        return;
    }

    $st = $pdo->prepare(
        'INSERT INTO product_images (product_id, image_path, sort_order, is_primary) VALUES (?, ?, ?, ?)'
    );
    $primaryPath = null;
    $sort = 0;
    foreach ($images as $img) {
        $path = trim((string) ($img['path'] ?? $img['imagePath'] ?? ''));
        if ($path === '') {
            continue;
        }
        $isPrimary = !empty($img['isPrimary']) ? 1 : 0;
        $order = isset($img['sortOrder']) ? (int) $img['sortOrder'] : $sort;
        $st->execute([$productId, substr($path, 0, 2048), $order, $isPrimary]);
        if ($isPrimary || $primaryPath === null) {
            $primaryPath = $path;
        }
        $sort++;
    }

    if ($primaryPath !== null) {
        $pdo->prepare('UPDATE products SET image_url = ? WHERE id = ?')
            ->execute([substr($primaryPath, 0, 2048), $productId]);
    }
}

/** @return list<string> */
function tm_product_images_public_urls(PDO $pdo, string $productId, string $fallbackPath, string $slug, string $category): array
{
    try {
        $rows = tm_product_images_list($pdo, $productId);
        if ($rows !== []) {
            return array_values(array_filter(array_map(static fn ($r) => (string) $r['url'], $rows)));
        }
    } catch (PDOException) {
        /* table may not exist yet */
    }
    $one = tm_resolve_product_image_url($fallbackPath, $slug, $category);
    return $one !== '' ? [$one] : [];
}
