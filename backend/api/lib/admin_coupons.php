<?php

declare(strict_types=1);

/**
 * @return array{items: list<array<string, mixed>>}
 */
function tm_admin_coupons_list(PDO $pdo): array
{
    if (!tm_coupons_table_exists($pdo)) {
        return ['items' => []];
    }
    $st = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC');
    $rows = $st !== false ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    $items = [];
    foreach ($rows as $row) {
        $items[] = tm_admin_coupon_row($row);
    }

    return ['items' => $items];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_admin_coupon_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'code' => (string) $row['code'],
        'description' => $row['description'] !== null ? (string) $row['description'] : null,
        'discountType' => (string) $row['discount_type'],
        'discountValue' => (float) $row['discount_value'],
        'minOrderInr' => (float) $row['min_order_inr'],
        'maxUses' => $row['max_uses'] !== null ? (int) $row['max_uses'] : null,
        'usedCount' => (int) $row['used_count'],
        'firstOrderOnly' => (bool) (int) $row['first_order_only'],
        'isActive' => (bool) (int) $row['is_active'],
        'startsAt' => $row['starts_at'] ?? null,
        'endsAt' => $row['ends_at'] ?? null,
        'createdAt' => (string) ($row['created_at'] ?? ''),
    ];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, coupon?: array}
 */
function tm_admin_coupon_create(PDO $pdo, array $body): array
{
    if (!tm_coupons_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Run migration_phase2_ecommerce.sql first'];
    }
    $code = strtoupper(trim((string) ($body['code'] ?? '')));
    if ($code === '' || strlen($code) > 32) {
        return ['ok' => false, 'message' => 'Valid coupon code required'];
    }
    $type = (string) ($body['discountType'] ?? 'percent');
    if (!in_array($type, ['percent', 'fixed'], true)) {
        return ['ok' => false, 'message' => 'Invalid discount type'];
    }
    $value = (float) ($body['discountValue'] ?? 0);
    if ($value <= 0) {
        return ['ok' => false, 'message' => 'Discount value must be positive'];
    }

    try {
        $st = $pdo->prepare(
            'INSERT INTO coupons (code, description, discount_type, discount_value, min_order_inr, max_uses, first_order_only, is_active, starts_at, ends_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $code,
            substr(trim((string) ($body['description'] ?? '')), 0, 255) ?: null,
            $type,
            $value,
            max(0, (float) ($body['minOrderInr'] ?? 0)),
            isset($body['maxUses']) && $body['maxUses'] !== '' ? max(1, (int) $body['maxUses']) : null,
            !empty($body['firstOrderOnly']) ? 1 : 0,
            !isset($body['isActive']) || $body['isActive'] ? 1 : 0,
            !empty($body['startsAt']) ? (string) $body['startsAt'] : null,
            !empty($body['endsAt']) ? (string) $body['endsAt'] : null,
        ]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            return ['ok' => false, 'message' => 'Coupon code already exists'];
        }
        throw $e;
    }

    $id = (int) $pdo->lastInsertId();
    $st = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return ['ok' => true, 'coupon' => $row ? tm_admin_coupon_row($row) : null];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, coupon?: array}
 */
function tm_admin_coupon_update(PDO $pdo, int $id, array $body): array
{
    if (!tm_coupons_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Coupons table missing'];
    }
    $st = $pdo->prepare('SELECT id FROM coupons WHERE id = ?');
    $st->execute([$id]);
    if ($st->fetchColumn() === false) {
        return ['ok' => false, 'message' => 'Coupon not found'];
    }

    $sets = [];
    $params = [];
    if (isset($body['description'])) {
        $sets[] = 'description = ?';
        $params[] = substr(trim((string) $body['description']), 0, 255) ?: null;
    }
    if (isset($body['discountType']) && in_array($body['discountType'], ['percent', 'fixed'], true)) {
        $sets[] = 'discount_type = ?';
        $params[] = (string) $body['discountType'];
    }
    if (isset($body['discountValue'])) {
        $sets[] = 'discount_value = ?';
        $params[] = max(0.01, (float) $body['discountValue']);
    }
    if (isset($body['minOrderInr'])) {
        $sets[] = 'min_order_inr = ?';
        $params[] = max(0, (float) $body['minOrderInr']);
    }
    if (array_key_exists('maxUses', $body)) {
        $sets[] = 'max_uses = ?';
        $params[] = $body['maxUses'] === null || $body['maxUses'] === '' ? null : max(1, (int) $body['maxUses']);
    }
    if (isset($body['firstOrderOnly'])) {
        $sets[] = 'first_order_only = ?';
        $params[] = !empty($body['firstOrderOnly']) ? 1 : 0;
    }
    if (isset($body['isActive'])) {
        $sets[] = 'is_active = ?';
        $params[] = !empty($body['isActive']) ? 1 : 0;
    }
    if (array_key_exists('startsAt', $body)) {
        $sets[] = 'starts_at = ?';
        $params[] = $body['startsAt'] ?: null;
    }
    if (array_key_exists('endsAt', $body)) {
        $sets[] = 'ends_at = ?';
        $params[] = $body['endsAt'] ?: null;
    }

    if ($sets === []) {
        return ['ok' => false, 'message' => 'Nothing to update'];
    }

    $params[] = $id;
    $pdo->prepare('UPDATE coupons SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    $st = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return ['ok' => true, 'coupon' => $row ? tm_admin_coupon_row($row) : null];
}

/**
 * @return array{ok: bool, message?: string}
 */
function tm_admin_coupon_delete(PDO $pdo, int $id): array
{
    $st = $pdo->prepare('DELETE FROM coupons WHERE id = ?');
    $st->execute([$id]);
    if ($st->rowCount() === 0) {
        return ['ok' => false, 'message' => 'Coupon not found'];
    }

    return ['ok' => true];
}
