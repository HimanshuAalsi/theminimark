<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_categories.php';
require_once __DIR__ . '/product_images_store.php';

function tm_admin_bulk_template_path(): string
{
    $candidates = [
        dirname(__DIR__) . '/data/products_bulk_import_TEMPLATE.csv',
        dirname(__DIR__, 2) . '/data/products_bulk_import_TEMPLATE.csv',
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }

    return $candidates[0];
}

function tm_admin_bulk_yn(string $v): int
{
    $v = strtolower(trim($v));
    return ($v === '' || $v === '0' || $v === 'no' || $v === 'n') ? 0 : 1;
}

/** @return list<string> */
function tm_products_table_columns(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query('SHOW COLUMNS FROM products');
        $cache = $st ? array_column($st->fetchAll(PDO::FETCH_ASSOC), 'Field') : [];
    } catch (PDOException) {
        $cache = [];
    }

    return $cache;
}

/** @return list<array<string, string>> */
function tm_admin_bulk_read_csv(string $path): array
{
    if ($path === '' || !is_readable($path)) {
        throw new RuntimeException('CSV file is missing or not readable. Check upload size limits on the server.');
    }
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        throw new RuntimeException('Cannot open CSV file.');
    }
    $first = fgets($fh);
    if ($first === false) {
        fclose($fh);
        throw new RuntimeException('Empty CSV');
    }
    $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
    $headers = array_map(
        static fn ($h) => strtolower(trim((string) $h)),
        str_getcsv($first),
    );
    $rows = [];
    while (($data = fgetcsv($fh)) !== false) {
        if (count($data) === 1 && trim((string) $data[0]) === '') {
            continue;
        }
        $row = [];
        foreach ($headers as $i => $key) {
            $row[$key] = isset($data[$i]) ? trim((string) $data[$i]) : '';
        }
        if (($row['id'] ?? '') === '' && ($row['name'] ?? '') === '') {
            continue;
        }
        $rows[] = $row;
    }
    fclose($fh);
    return $rows;
}

/** @return list<string> */
function tm_admin_bulk_split_images(string $raw): array
{
    if ($raw === '') {
        return [];
    }
    $parts = preg_split('/[|,]/', $raw) ?: [];
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') {
            $out[] = $p;
        }
    }
    return $out;
}

function tm_admin_bulk_resolve_image(string $ref, string $categorySlug, ?string $zipDir): string
{
    $ref = trim($ref);
    if ($ref === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $ref) || str_starts_with($ref, '/uploads/')) {
        return $ref;
    }
    if (str_starts_with($ref, '/')) {
        return $ref;
    }
    if ($zipDir !== null && is_dir($zipDir)) {
        $safe = basename($ref);
        $candidate = $zipDir . DIRECTORY_SEPARATOR . $safe;
        if (is_file($candidate)) {
            $destRoot = tm_uploads_base_dir() . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $categorySlug;
            if (!is_dir($destRoot) && !mkdir($destRoot, 0755, true) && !is_dir($destRoot)) {
                return '';
            }
            $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($safe, PATHINFO_FILENAME)) ?: 'img';
            $tempName = bin2hex(random_bytes(6)) . '-' . $safeBase . '.' . (pathinfo($safe, PATHINFO_EXTENSION) ?: 'jpg');
            $tempDest = $destRoot . DIRECTORY_SEPARATOR . $tempName;
            if (!copy($candidate, $tempDest)) {
                return '';
            }
            require_once __DIR__ . '/image_optimize.php';
            $optimized = tm_image_optimize_saved_file($tempDest, 'products/' . $categorySlug);
            if (!$optimized['ok'] || empty($optimized['path'])) {
                @unlink($tempDest);

                return '';
            }

            return (string) $optimized['path'];
        }
    }
    return '/uploads/products/' . $categorySlug . '/' . basename($ref);
}

/**
 * @param list<array<string, string>> $rows
 * @return array<string, array<string, mixed>>
 */
function tm_admin_bulk_group_products(array $rows): array
{
    $products = [];
    foreach ($rows as $row) {
        $id = $row['id'] ?? '';
        if ($id === '') {
            continue;
        }
        $imgExtra = tm_admin_bulk_split_images($row['image_urls'] ?? $row['image_url'] ?? '');
        $isImageOnly = ($row['name'] ?? '') === '' && ($row['slug'] ?? '') === '' && $imgExtra !== [];

        if ($isImageOnly && isset($products[$id])) {
            $products[$id]['_images'] = array_merge($products[$id]['_images'], $imgExtra);
            continue;
        }

        if (!isset($products[$id])) {
            $products[$id] = $row;
            $products[$id]['_images'] = $imgExtra;
        } else {
            $products[$id]['_images'] = array_merge($products[$id]['_images'], $imgExtra);
            if (($row['name'] ?? '') !== '') {
                $products[$id] = array_merge($products[$id], $row);
                $products[$id]['_images'] = array_merge(
                    $products[$id]['_images'],
                    tm_admin_bulk_split_images($row['image_urls'] ?? $row['image_url'] ?? ''),
                );
            }
        }
    }
    return $products;
}

/** @param array<string, mixed> $row */
function tm_admin_bulk_upsert_product(PDO $pdo, string $id, array $row, string $primaryImage): void
{
    $tableCols = tm_products_table_columns($pdo);
    if ($tableCols === []) {
        throw new RuntimeException('Products table not found.');
    }

    $compareRaw = $row['compare_at_inr'] ?? $row['compare_at'] ?? '';
    $compareAt = $compareRaw !== '' ? (float) $compareRaw : null;
    $keywords = trim((string) ($row['keywords'] ?? $row['tags'] ?? ''));
    $sku = trim((string) ($row['sku'] ?? ''));
    $stock = ($row['stock_quantity'] ?? '') !== '' ? (int) $row['stock_quantity'] : null;
    $seoTitle = trim((string) ($row['seo_title'] ?? ''));
    $seoDesc = trim((string) ($row['seo_description'] ?? ''));

    $values = [
        'id' => substr($id, 0, 32),
        'slug' => substr((string) ($row['slug'] ?? ''), 0, 191),
        'name' => (string) ($row['name'] ?? ''),
        'description' => trim((string) ($row['description'] ?? '')) ?: null,
        'keywords' => $keywords !== '' ? $keywords : null,
        'price' => (float) ($row['price_inr'] ?? $row['price'] ?? 0),
        'compare_at' => $compareAt,
        'category' => strtolower((string) ($row['category_slug'] ?? $row['category'] ?? '')),
        'image_url' => substr($primaryImage, 0, 2048),
        'home_bestseller' => tm_admin_bulk_yn((string) ($row['home_bestseller'] ?? '0')),
        'home_secondary' => tm_admin_bulk_yn((string) ($row['home_secondary'] ?? $row['home_magnetic_carousel'] ?? '0')),
        'sort_order' => (int) ($row['sort_order'] ?? 0),
        'is_active' => tm_admin_bulk_yn((string) ($row['is_active'] ?? '1')),
        'sku' => $sku !== '' ? substr($sku, 0, 64) : null,
        'stock_quantity' => $stock,
        'seo_title' => $seoTitle !== '' ? substr($seoTitle, 0, 255) : null,
        'seo_description' => $seoDesc !== '' ? substr($seoDesc, 0, 512) : null,
    ];

    $insertCols = [];
    $insertVals = [];
    foreach ($values as $col => $val) {
        if (in_array($col, $tableCols, true)) {
            $insertCols[] = $col;
            $insertVals[] = $val;
        }
    }
    if ($insertCols === []) {
        throw new RuntimeException('No matching product columns in database.');
    }

    $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
    $updates = [];
    foreach ($insertCols as $col) {
        if ($col === 'id') {
            continue;
        }
        $updates[] = "{$col} = VALUES({$col})";
    }

    $sql = 'INSERT INTO products (' . implode(', ', $insertCols) . ') VALUES (' . $placeholders . ')';
    if ($updates !== []) {
        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    $pdo->prepare($sql)->execute($insertVals);
}

/**
 * @return array{ok: bool, message?: string, imported?: int, errors?: list<string>, warnings?: list<string>}
 */
function tm_admin_bulk_import(PDO $pdo, string $csvPath, ?string $zipPath, bool $dryRun = false): array
{
    $allowedCats = tm_category_slugs($pdo);
    $zipDir = null;
    if ($zipPath !== null && is_file($zipPath)) {
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'PHP ZipArchive extension is not enabled. Remove the ZIP or ask Hostinger to enable zip.'];
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zipDir = tm_uploads_base_dir() . '/bulk/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
            if (!is_dir($zipDir) && !mkdir($zipDir, 0755, true) && !is_dir($zipDir)) {
                $zip->close();
                return [
                    'ok' => false,
                    'message' => 'Cannot create upload folder. Make public_html/api/uploads/ writable (chmod 755 or 775).',
                ];
            }
            if (!$zip->extractTo($zipDir)) {
                $zip->close();
                return ['ok' => false, 'message' => 'Could not extract images ZIP.'];
            }
            $zip->close();
        }
    }

    try {
        $rawRows = tm_admin_bulk_read_csv($csvPath);
        $grouped = tm_admin_bulk_group_products($rawRows);
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }

    if ($grouped === []) {
        return ['ok' => false, 'message' => 'No product rows found in CSV. Check id, slug, and name columns.'];
    }

    $errors = [];
    $warnings = [];
    $imported = 0;

    foreach ($grouped as $id => $row) {
        $slug = $row['slug'] ?? '';
        $name = $row['name'] ?? '';
        $category = strtolower($row['category_slug'] ?? $row['category'] ?? '');
        if ($slug === '' || $name === '') {
            $errors[] = "Product {$id}: slug and name required.";
            continue;
        }
        if (!in_array($category, $allowedCats, true)) {
            $errors[] = "Product {$id}: unknown category “{$category}”.";
            continue;
        }
        $price = (float) ($row['price_inr'] ?? $row['price'] ?? 0);
        if ($price <= 0) {
            $errors[] = "Product {$id}: price_inr must be > 0.";
            continue;
        }

        $imageRefs = $row['_images'] ?? [];
        if ($imageRefs === []) {
            $errors[] = "Product {$id}: at least one image (image_urls) required.";
            continue;
        }

        $resolvedImages = [];
        foreach ($imageRefs as $i => $ref) {
            $path = tm_admin_bulk_resolve_image($ref, $category, $zipDir);
            if ($path === '') {
                $warnings[] = "Product {$id}: skipped image “{$ref}”.";
                continue;
            }
            $resolvedImages[] = [
                'path' => $path,
                'sortOrder' => $i,
                'isPrimary' => $i === 0,
            ];
        }
        if ($resolvedImages === []) {
            $errors[] = "Product {$id}: no valid images.";
            continue;
        }

        if ($dryRun) {
            $imported++;
            continue;
        }

        try {
            tm_admin_bulk_upsert_product($pdo, (string) $id, $row, $resolvedImages[0]['path']);
            try {
                tm_product_images_sync($pdo, (string) $id, $resolvedImages);
            } catch (PDOException) {
                /* product_images table optional until migration */
            }
            $imported++;
        } catch (PDOException $e) {
            $errors[] = "Product {$id}: database error — " . tm_admin_bulk_db_hint($e);
        } catch (Throwable $e) {
            $errors[] = "Product {$id}: " . $e->getMessage();
        }
    }

    return [
        'ok' => $errors === [],
        'imported' => $imported,
        'errors' => $errors,
        'warnings' => $warnings,
        'message' => $errors === []
            ? ($dryRun ? "Validated {$imported} product(s)." : "Imported {$imported} product(s).")
            : 'Import finished with errors.',
    ];
}

function tm_admin_bulk_db_hint(PDOException $e): string
{
    $msg = $e->getMessage();
    if (str_contains($msg, 'Unknown column')) {
        return 'run migration_admin_advanced.sql in phpMyAdmin (missing product columns).';
    }

    return getenv('TM_DEBUG') === '1' ? $msg : 'check database schema and try again.';
}

function tm_admin_bulk_export_csv(PDO $pdo): string
{
    $st = $pdo->query('SELECT * FROM products ORDER BY sort_order ASC, name ASC');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = fopen('php://temp', 'r+');
    fputcsv($out, [
        'id', 'slug', 'name', 'description', 'price_inr', 'compare_at_inr', 'category_slug', 'keywords',
        'image_urls', 'home_bestseller', 'home_secondary', 'sort_order', 'is_active', 'sku', 'stock_quantity',
        'seo_title', 'seo_description',
    ]);
    foreach ($rows as $p) {
        $images = [];
        try {
            foreach (tm_product_images_list($pdo, (string) $p['id']) as $img) {
                $images[] = $img['path'];
            }
        } catch (PDOException) {
            $images = [(string) $p['image_url']];
        }
        if ($images === []) {
            $images = [(string) $p['image_url']];
        }
        fputcsv($out, [
            $p['id'],
            $p['slug'],
            $p['name'],
            $p['description'] ?? '',
            $p['price'],
            $p['compare_at'] ?? '',
            $p['category'],
            $p['keywords'] ?? '',
            implode('|', $images),
            (int) $p['home_bestseller'],
            (int) $p['home_secondary'],
            (int) $p['sort_order'],
            (int) $p['is_active'],
            $p['sku'] ?? '',
            $p['stock_quantity'] ?? '',
            $p['seo_title'] ?? '',
            $p['seo_description'] ?? '',
        ]);
    }
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return $csv !== false ? $csv : '';
}
