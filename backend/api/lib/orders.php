<?php

declare(strict_types=1);

/**
 * Accepts cart-style lines from the Vue app. Payment gateway hooks belong here later.
 *
 * @param list<array{productId?: string|int, name: string, unitPrice: float|int, quantity: int}> $lines
 * @return array{ok: bool, orderId?: int, message: string}
 */
function tm_order_create(PDO $pdo, string $customerEmail, ?string $customerName, string $currency, array $lines, ?string $notes): array
{
    $customerEmail = strtolower(trim($customerEmail));
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid customer email'];
    }

    if ($lines === []) {
        return ['ok' => false, 'message' => 'Cart is empty'];
    }

    $currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $currency) ?: 'USD', 0, 3));
    if (strlen($currency) !== 3) {
        $currency = 'USD';
    }

    $subtotal = 0.0;
    foreach ($lines as $line) {
        $qty = max(1, (int) ($line['quantity'] ?? 1));
        $price = (float) ($line['unitPrice'] ?? 0);
        $subtotal += $price * $qty;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO orders (status, customer_email, customer_name, currency, subtotal, notes)
             VALUES (\'pending\', :email, :name, :currency, :subtotal, :notes)'
        );
        $stmt->execute([
            ':email' => $customerEmail,
            ':name' => $customerName !== null && $customerName !== '' ? substr($customerName, 0, 255) : null,
            ':currency' => $currency,
            ':subtotal' => round($subtotal, 2),
            ':notes' => $notes !== null && $notes !== '' ? substr($notes, 0, 5000) : null,
        ]);
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
        }

        $pdo->commit();
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

    return ['ok' => true, 'message' => 'Payment recorded'];
}
