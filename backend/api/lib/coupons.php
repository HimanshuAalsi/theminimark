<?php

declare(strict_types=1);

function tm_coupons_table_exists(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'coupons'"
    );
    $cache = $st !== false && (int) $st->fetchColumn() > 0;

    return $cache;
}

/**
 * @return array{ok: bool, message?: string, coupon?: array<string, mixed>, discountInr?: float}
 */
function tm_coupon_validate(PDO $pdo, string $code, string $customerEmail, float $itemsSubtotal): array
{
    if (!tm_coupons_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Coupons are not available yet'];
    }

    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['ok' => false, 'message' => 'Enter a coupon code'];
    }

    $st = $pdo->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return ['ok' => false, 'message' => 'Invalid coupon code'];
    }

    if (!(int) $row['is_active']) {
        return ['ok' => false, 'message' => 'This coupon is no longer active'];
    }

    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    if (!empty($row['starts_at'])) {
        $starts = new DateTimeImmutable((string) $row['starts_at'], new DateTimeZone('UTC'));
        if ($now < $starts) {
            return ['ok' => false, 'message' => 'This coupon is not valid yet'];
        }
    }
    if (!empty($row['ends_at'])) {
        $ends = new DateTimeImmutable((string) $row['ends_at'], new DateTimeZone('UTC'));
        if ($now > $ends) {
            return ['ok' => false, 'message' => 'This coupon has expired'];
        }
    }

    $minOrder = (float) $row['min_order_inr'];
    if ($itemsSubtotal < $minOrder) {
        return ['ok' => false, 'message' => 'Minimum order ₹' . (int) $minOrder . ' required for this coupon'];
    }

    $maxUses = $row['max_uses'] !== null ? (int) $row['max_uses'] : null;
    $used = (int) $row['used_count'];
    if ($maxUses !== null && $used >= $maxUses) {
        return ['ok' => false, 'message' => 'This coupon has reached its usage limit'];
    }

    if ((int) $row['first_order_only']) {
        $email = strtolower(trim($customerEmail));
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM orders
             WHERE LOWER(customer_email) = ? AND status IN ('paid','processing','shipped','delivered')"
        );
        $chk->execute([$email]);
        if ((int) $chk->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'This coupon is for first orders only'];
        }
    }

    $type = (string) $row['discount_type'];
    $value = (float) $row['discount_value'];
    $discount = $type === 'fixed'
        ? min($itemsSubtotal, $value)
        : round($itemsSubtotal * $value / 100, 2);

    if ($discount <= 0) {
        return ['ok' => false, 'message' => 'Coupon does not apply to this order'];
    }

    return [
        'ok' => true,
        'coupon' => [
            'code' => (string) $row['code'],
            'description' => (string) ($row['description'] ?? ''),
            'discountType' => $type,
            'discountValue' => $value,
        ],
        'discountInr' => round($discount, 2),
    ];
}

function tm_coupon_increment_usage(PDO $pdo, string $code): void
{
    if (!tm_coupons_table_exists($pdo) || trim($code) === '') {
        return;
    }
    $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?')
        ->execute([strtoupper(trim($code))]);
}
