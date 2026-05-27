<?php

declare(strict_types=1);

/**
 * Per-user product wishlists.
 */

function tm_wishlist_table_exists(PDO $pdo): bool
{
    $st = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'wishlist_items'"
    );
    return $st !== false && (int) $st->fetchColumn() > 0;
}

/**
 * @return list<array<string, mixed>>
 */
function tm_wishlist_products(PDO $pdo, int $userId): array
{
    if (!tm_wishlist_table_exists($pdo)) {
        return [];
    }

    $sql = <<<'SQL'
        SELECT p.*
        FROM wishlist_items w
        INNER JOIN products p ON p.id = w.product_id
        WHERE w.user_id = :uid AND p.is_active = 1
        ORDER BY w.created_at DESC
        SQL;

    $st = $pdo->prepare($sql);
    $st->execute([':uid' => $userId]);

    return tm_products_public_rows($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

/**
 * @return list<string>
 */
function tm_wishlist_product_ids(PDO $pdo, int $userId): array
{
    if (!tm_wishlist_table_exists($pdo)) {
        return [];
    }

    $st = $pdo->prepare('SELECT product_id FROM wishlist_items WHERE user_id = :uid ORDER BY created_at DESC');
    $st->execute([':uid' => $userId]);
    $ids = $st->fetchAll(PDO::FETCH_COLUMN);

    return array_map(static fn ($id) => (string) $id, $ids ?: []);
}

function tm_wishlist_add(PDO $pdo, int $userId, string $productId): array
{
    if (!tm_wishlist_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Wishlist is not available yet. Run backend/sql/wishlist.sql on your database.'];
    }

    $productId = trim($productId);
    if ($productId === '') {
        return ['ok' => false, 'message' => 'Product id is required.'];
    }

    $check = $pdo->prepare('SELECT id FROM products WHERE id = :id AND is_active = 1 LIMIT 1');
    $check->execute([':id' => $productId]);
    if ($check->fetchColumn() === false) {
        return ['ok' => false, 'message' => 'Product not found.'];
    }

    $st = $pdo->prepare(
        'INSERT IGNORE INTO wishlist_items (user_id, product_id) VALUES (:uid, :pid)'
    );
    $st->execute([':uid' => $userId, ':pid' => $productId]);

    return ['ok' => true, 'productIds' => tm_wishlist_product_ids($pdo, $userId)];
}

function tm_wishlist_remove(PDO $pdo, int $userId, string $productId): array
{
    if (!tm_wishlist_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Wishlist is not available yet.'];
    }

    $st = $pdo->prepare('DELETE FROM wishlist_items WHERE user_id = :uid AND product_id = :pid');
    $st->execute([':uid' => $userId, ':pid' => trim($productId)]);

    return ['ok' => true, 'productIds' => tm_wishlist_product_ids($pdo, $userId)];
}

/**
 * @param list<string> $productIds
 */
function tm_wishlist_sync(PDO $pdo, int $userId, array $productIds): array
{
    if (!tm_wishlist_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Wishlist is not available yet.'];
    }

    $unique = [];
    foreach ($productIds as $id) {
        $id = trim((string) $id);
        if ($id !== '') {
            $unique[$id] = true;
        }
    }
    $ids = array_keys($unique);

    if ($ids === []) {
        $pdo->prepare('DELETE FROM wishlist_items WHERE user_id = :uid')->execute([':uid' => $userId]);
        return ['ok' => true, 'productIds' => []];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $validSt = $pdo->prepare("SELECT id FROM products WHERE is_active = 1 AND id IN ($placeholders)");
    $validSt->execute($ids);
    $valid = $validSt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $valid = array_map(static fn ($id) => (string) $id, $valid);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM wishlist_items WHERE user_id = :uid')->execute([':uid' => $userId]);
        $ins = $pdo->prepare('INSERT INTO wishlist_items (user_id, product_id) VALUES (:uid, :pid)');
        foreach ($valid as $pid) {
            $ins->execute([':uid' => $userId, ':pid' => $pid]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['ok' => true, 'productIds' => tm_wishlist_product_ids($pdo, $userId)];
}
