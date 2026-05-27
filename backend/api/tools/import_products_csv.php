<?php

declare(strict_types=1);

/**
 * Import products from Excel-exported CSV into MySQL.
 *
 * Usage (repo root):
 *   php backend/api/tools/import_products_csv.php backend/data/products_catalog.csv
 *   php backend/api/tools/import_products_csv.php backend/data/products_catalog.csv --dry-run
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$file = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);
$deactivateMissing = in_array('--deactivate-missing', $argv, true);

if ($file === '' || !is_readable($file)) {
    fwrite(STDERR, "Usage: php import_products_csv.php <path-to.csv> [--dry-run] [--deactivate-missing]\n");
    exit(1);
}

require dirname(__DIR__) . '/bootstrap.php';

$allowedCategories = ['bookmarks', 'cards', 'calendars', 'magnets', 'hampers'];

function yn_to_int(string $v): int
{
    $v = strtolower(trim($v));
    if ($v === '' || $v === '0' || $v === 'no' || $v === 'n') {
        return 0;
    }
    return 1;
}

function read_csv_rows(string $path): array
{
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        throw new RuntimeException('Cannot open file.');
    }
    $first = fgets($fh);
    if ($first === false) {
        fclose($fh);
        throw new RuntimeException('Empty file.');
    }
    $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
    $headers = str_getcsv($first);
    $headers = array_map(static fn ($h) => strtolower(trim((string) $h)), $headers);
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

try {
    $pdo = tm_db();
    $rows = read_csv_rows($file);
    $seenIds = [];
    $errors = [];
    $upserted = 0;

    foreach ($rows as $line => $row) {
        $n = $line + 2;
        $id = $row['id'] ?? '';
        $slug = $row['slug'] ?? '';
        $name = $row['name'] ?? '';
        $category = strtolower($row['category'] ?? '');

        if ($id === '' || $slug === '' || $name === '') {
            $errors[] = "Row {$n}: id, slug, and name are required.";
            continue;
        }
        if (isset($seenIds[$id])) {
            $errors[] = "Row {$n}: duplicate id {$id}.";
            continue;
        }
        $seenIds[$id] = true;

        if (!in_array($category, $allowedCategories, true)) {
            $errors[] = "Row {$n}: invalid category “{$category}”.";
            continue;
        }

        $price = (float) ($row['price_inr'] ?? $row['price'] ?? 0);
        if ($price <= 0) {
            $errors[] = "Row {$n}: price_inr must be greater than 0.";
            continue;
        }

        $compareRaw = $row['compare_at_inr'] ?? $row['compare_at'] ?? '';
        $compareAt = $compareRaw !== '' ? (float) $compareRaw : null;

        $description = $row['description'] ?? '';
        $description = $description === '' ? null : $description;

        $imageUrl = $row['image_url'] ?? '';
        if ($imageUrl === '') {
            $errors[] = "Row {$n}: image_url is required.";
            continue;
        }

        $homeBestseller = yn_to_int($row['home_bestseller'] ?? '0');
        $homeSecondary = yn_to_int($row['home_magnetic_carousel'] ?? $row['home_secondary'] ?? '0');
        $sortOrder = (int) ($row['sort_order'] ?? '0');
        $isActive = yn_to_int($row['is_active'] ?? '1');

        if ($dryRun) {
            $upserted++;
            continue;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO products (id, slug, name, description, price, compare_at, category, image_url,
                home_bestseller, home_secondary, sort_order, is_active)
             VALUES (:id, :slug, :name, :description, :price, :compare_at, :category, :image_url,
                :home_bestseller, :home_secondary, :sort_order, :is_active)
             ON DUPLICATE KEY UPDATE
                slug = VALUES(slug),
                name = VALUES(name),
                description = VALUES(description),
                price = VALUES(price),
                compare_at = VALUES(compare_at),
                category = VALUES(category),
                image_url = VALUES(image_url),
                home_bestseller = VALUES(home_bestseller),
                home_secondary = VALUES(home_secondary),
                sort_order = VALUES(sort_order),
                is_active = VALUES(is_active)'
        );
        $stmt->execute([
            ':id' => $id,
            ':slug' => $slug,
            ':name' => $name,
            ':description' => $description,
            ':price' => $price,
            ':compare_at' => $compareAt,
            ':category' => $category,
            ':image_url' => $imageUrl,
            ':home_bestseller' => $homeBestseller,
            ':home_secondary' => $homeSecondary,
            ':sort_order' => $sortOrder,
            ':is_active' => $isActive,
        ]);
        $upserted++;
    }

    if ($errors !== []) {
        foreach ($errors as $e) {
            fwrite(STDERR, $e . "\n");
        }
        exit(1);
    }

    if ($deactivateMissing && !$dryRun && $seenIds !== []) {
        $placeholders = implode(',', array_fill(0, count($seenIds), '?'));
        $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id NOT IN ({$placeholders})");
        $stmt->execute(array_keys($seenIds));
        $deactivated = $stmt->rowCount();
        echo "Deactivated {$deactivated} products not in CSV.\n";
    }

    $mode = $dryRun ? 'Validated' : 'Imported';
    echo "{$mode} {$upserted} product row(s) from {$file}\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
