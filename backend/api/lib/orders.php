<?php

declare(strict_types=1);

/**
 * Accepts cart-style lines from the Vue app. Payment gateway hooks belong here later.
 *
 * @param list<array{productId?: string|int, name: string, unitPrice: float|int, quantity: int}> $lines
 * @return array{ok: bool, orderId?: int, message: string}
 */
/**
 * @param array{productId?: string|int, name?: string}|null $freeGift
 */
function tm_order_create(
    PDO $pdo,
    string $customerEmail,
    ?string $customerName,
    string $currency,
    array $lines,
    ?string $notes,
    ?array $freeGift = null,
    ?string $couponCode = null,
    ?array $shipping = null
): array {
    $customerEmail = strtolower(trim($customerEmail));
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid customer email'];
    }

    $customerName = $customerName !== null ? trim($customerName) : '';
    if ($customerName === '' || strlen($customerName) < 2) {
        return ['ok' => false, 'message' => 'Customer name is required'];
    }

    if ($shipping === null) {
        return ['ok' => false, 'message' => 'Shipping details are required'];
    }
    $shippingCheck = tm_order_validate_shipping($shipping);
    if (!$shippingCheck['ok']) {
        return ['ok' => false, 'message' => $shippingCheck['message'] ?? 'Invalid shipping details'];
    }

    if ($lines === []) {
        return ['ok' => false, 'message' => 'Cart is empty'];
    }

    $currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $currency) ?: 'USD', 0, 3));
    if (strlen($currency) !== 3) {
        $currency = 'USD';
    }

    $itemsSubtotal = 0.0;
    foreach ($lines as $line) {
        $qty = max(1, (int) ($line['quantity'] ?? 1));
        $price = (float) ($line['unitPrice'] ?? 0);
        $itemsSubtotal += $price * $qty;
    }
    $itemsSubtotal = round($itemsSubtotal, 2);

    $couponDiscount = 0.0;
    $appliedCoupon = null;
    if ($couponCode !== null && trim($couponCode) !== '') {
        require_once __DIR__ . '/coupons.php';
        $validated = tm_coupon_validate($pdo, $couponCode, $customerEmail, $itemsSubtotal);
        if (!$validated['ok']) {
            return ['ok' => false, 'message' => $validated['message'] ?? 'Invalid coupon'];
        }
        $couponDiscount = (float) ($validated['discountInr'] ?? 0);
        $appliedCoupon = strtoupper(trim($couponCode));
    }

    $chargeTotal = tm_order_charge_total_inr($itemsSubtotal, $couponDiscount);

    $noteBody = $notes !== null && $notes !== '' ? trim($notes) : '';
    $rewardNotes = tm_order_reward_notes($itemsSubtotal, $freeGift, $appliedCoupon, $couponDiscount);
    $combinedNotes = trim($noteBody . ($noteBody !== '' ? "\n" : '') . implode("\n", $rewardNotes));

    $pdo->beginTransaction();
    try {
        $hasCouponCols = tm_orders_has_coupon_columns($pdo);
        if ($hasCouponCols) {
            $stmt = $pdo->prepare(
                'INSERT INTO orders (status, customer_email, customer_name, currency, subtotal, items_subtotal, coupon_code, coupon_discount, notes)
                 VALUES (\'pending\', :email, :name, :currency, :subtotal, :items_subtotal, :coupon_code, :coupon_discount, :notes)'
            );
            $stmt->execute([
                ':email' => $customerEmail,
                ':name' => $customerName !== null && $customerName !== '' ? substr($customerName, 0, 255) : null,
                ':currency' => $currency,
                ':subtotal' => $chargeTotal,
                ':items_subtotal' => $itemsSubtotal,
                ':coupon_code' => $appliedCoupon,
                ':coupon_discount' => $couponDiscount > 0 ? $couponDiscount : null,
                ':notes' => $combinedNotes !== '' ? substr($combinedNotes, 0, 5000) : null,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO orders (status, customer_email, customer_name, currency, subtotal, items_subtotal, notes)
                 VALUES (\'pending\', :email, :name, :currency, :subtotal, :items_subtotal, :notes)'
            );
            $stmt->execute([
                ':email' => $customerEmail,
                ':name' => $customerName !== null && $customerName !== '' ? substr($customerName, 0, 255) : null,
                ':currency' => $currency,
                ':subtotal' => $chargeTotal,
                ':items_subtotal' => $itemsSubtotal,
                ':notes' => $combinedNotes !== '' ? substr($combinedNotes, 0, 5000) : null,
            ]);
        }
        $orderId = (int) $pdo->lastInsertId();

        $lineStmt = $pdo->prepare(
            'INSERT INTO order_lines (order_id, product_id, product_name, unit_price, quantity)
             VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity)'
        );

        foreach ($lines as $line) {
            $pid = $line['productId'] ?? null;
            $pidStr = $pid === null || $pid === '' ? null : substr((string) $pid, 0, 32);
            $name = isset($line['name']) ? substr((string) $line['name'], 0, 255) : 'Item';
            $qty = max(1, (int) ($line['quantity'] ?? 1));
            $price = round((float) ($line['unitPrice'] ?? 0), 2);
            $lineStmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $pidStr,
                ':product_name' => $name,
                ':unit_price' => $price,
                ':quantity' => $qty,
            ]);
            $lineId = (int) $pdo->lastInsertId();
            if (isset($line['personalization']) && is_array($line['personalization'])) {
                tm_personalisation_save_for_line($pdo, $lineId, $line['personalization']);
            }
        }

        tm_order_save_shipping($pdo, $orderId, $shipping);

        $pdo->commit();

        require_once __DIR__ . '/order_lifecycle.php';
        tm_order_after_create($pdo, $orderId);

        return ['ok' => true, 'orderId' => $orderId, 'message' => 'Order received'];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * @return array<string, mixed>|null
 */
function tm_order_get(PDO $pdo, int $orderId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, status, razorpay_order_id, payment_id, customer_email, customer_name, currency, subtotal
         FROM orders WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row !== false ? $row : null;
}

function tm_order_set_razorpay_order_id(PDO $pdo, int $orderId, string $razorpayOrderId): void
{
    $stmt = $pdo->prepare(
        'UPDATE orders SET razorpay_order_id = :rzp_id WHERE id = :id AND status = \'pending\''
    );
    $stmt->execute([
        ':rzp_id' => substr($razorpayOrderId, 0, 64),
        ':id' => $orderId,
    ]);
}

/**
 * @return array{ok: bool, message: string}
 */
function tm_order_mark_paid(PDO $pdo, int $orderId, string $paymentId): array
{
    $order = tm_order_get($pdo, $orderId);
    if ($order === null) {
        return ['ok' => false, 'message' => 'Order not found'];
    }
    if (($order['status'] ?? '') === 'paid') {
        return ['ok' => true, 'message' => 'Already paid'];
    }
    if (($order['status'] ?? '') !== 'pending') {
        return ['ok' => false, 'message' => 'Order is not awaiting payment'];
    }

    $stmt = $pdo->prepare(
        'UPDATE orders
         SET status = \'paid\', payment_id = :payment_id, paid_at = UTC_TIMESTAMP()
         WHERE id = :id AND status = \'pending\''
    );
    $stmt->execute([
        ':payment_id' => substr($paymentId, 0, 64),
        ':id' => $orderId,
    ]);

    require_once __DIR__ . '/order_lifecycle.php';
    tm_order_on_status_change($pdo, $orderId, 'pending', 'paid');

    return ['ok' => true, 'message' => 'Payment recorded'];
}

function tm_orders_has_coupon_columns(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'coupon_code'"
    );
    $cache = $st !== false && (int) $st->fetchColumn() > 0;

    return $cache;
}

/**
 * Public order lookup — requires matching email.
 *
 * @return array{ok: bool, message: string, order?: array<string, mixed>}
 */
function tm_order_track_public(PDO $pdo, int $orderId, string $email): array
{
    $email = strtolower(trim($email));
    if ($orderId < 1 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid order number or email'];
    }

    $st = $pdo->prepare(
        'SELECT id, status, customer_email, currency, subtotal, created_at
         FROM orders WHERE id = :id LIMIT 1'
    );
    $st->execute([':id' => $orderId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return ['ok' => false, 'message' => 'Order not found'];
    }
    if (strtolower(trim((string) $row['customer_email'])) !== $email) {
        return ['ok' => false, 'message' => 'Order not found'];
    }

    $linesSt = $pdo->prepare(
        'SELECT id, product_name, quantity FROM order_lines WHERE order_id = :id ORDER BY id ASC'
    );
    $linesSt->execute([':id' => $orderId]);
    $lineRows = $linesSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $lines = [];
    $itemCount = 0;
    foreach ($lineRows as $line) {
        $qty = max(1, (int) ($line['quantity'] ?? 1));
        $itemCount += $qty;
        $lines[] = [
            'id' => (int) $line['id'],
            'name' => (string) ($line['product_name'] ?? 'Item'),
            'quantity' => $qty,
        ];
    }

    return [
        'ok' => true,
        'message' => 'Order found',
        'order' => [
            'id' => (int) $row['id'],
            'status' => (string) $row['status'],
            'createdAt' => (string) $row['created_at'],
            'subtotal' => (float) $row['subtotal'],
            'currency' => (string) $row['currency'],
            'itemCount' => $itemCount,
            'lines' => $lines,
        ],
    ];
}
