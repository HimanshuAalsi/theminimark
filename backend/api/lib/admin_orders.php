<?php

declare(strict_types=1);

/** @return list<string> */
function tm_admin_order_statuses(): array
{
    return ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_admin_order_summary(array $row): array
{
    $lineSummary = isset($row['line_summary']) && $row['line_summary'] !== null && $row['line_summary'] !== ''
        ? (string) $row['line_summary']
        : null;

    return [
        'id' => (int) $row['id'],
        'status' => (string) $row['status'],
        'customerEmail' => (string) $row['customer_email'],
        'customerName' => $row['customer_name'] !== null ? (string) $row['customer_name'] : null,
        'currency' => (string) $row['currency'],
        'subtotal' => (float) $row['subtotal'],
        'itemsSubtotal' => $row['items_subtotal'] !== null ? (float) $row['items_subtotal'] : null,
        'couponCode' => $row['coupon_code'] ?? null,
        'couponDiscount' => isset($row['coupon_discount']) ? (float) $row['coupon_discount'] : null,
        'refundId' => $row['refund_id'] ?? null,
        'razorpayOrderId' => $row['razorpay_order_id'] ?? null,
        'paymentId' => $row['payment_id'] ?? null,
        'paidAt' => $row['paid_at'] ?? null,
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'lineCount' => (int) ($row['line_count'] ?? 0),
        'lineSummary' => $lineSummary,
    ];
}

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_admin_orders_list(PDO $pdo, array $query): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($query['status']) && $query['status'] !== 'all') {
        $where[] = 'o.status = :status';
        $params[':status'] = (string) $query['status'];
    }
    if (!empty($query['q'])) {
        $where[] = '(o.customer_email LIKE :q OR o.customer_name LIKE :q OR CAST(o.id AS CHAR) LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }
    if (!empty($query['from'])) {
        $where[] = 'o.created_at >= :from';
        $params[':from'] = (string) $query['from'] . ' 00:00:00';
    }
    if (!empty($query['to'])) {
        $where[] = 'o.created_at <= :to';
        $params[':to'] = (string) $query['to'] . ' 23:59:59';
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(100, max(10, (int) ($query['perPage'] ?? 25)));
    $offset = ($page - 1) * $perPage;

    $whereSql = implode(' AND ', $where);
    $countSql = 'SELECT COUNT(*) FROM orders o WHERE ' . $whereSql;
    $st = $pdo->prepare($countSql);
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = 'SELECT o.*,
            (SELECT COUNT(*) FROM order_lines ol WHERE ol.order_id = o.id) AS line_count,
            (SELECT GROUP_CONCAT(CONCAT(ol.product_name, \' ×\', ol.quantity) ORDER BY ol.id SEPARATOR \', \')
             FROM order_lines ol WHERE ol.order_id = o.id) AS line_summary
            FROM orders o WHERE ' . $whereSql . '
            ORDER BY o.created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = tm_admin_order_summary($row);
    }

    return [
        'items' => $items,
        'meta' => ['count' => count($items), 'total' => $total, 'page' => $page, 'perPage' => $perPage],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function tm_admin_order_detail(PDO $pdo, int $orderId): ?array
{
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $st->execute([$orderId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    $st = $pdo->prepare(
        'SELECT id, product_id, product_name, unit_price, quantity FROM order_lines WHERE order_id = ? ORDER BY id'
    );
    $st->execute([$orderId]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $lineItems = [];
    foreach ($lines as $ln) {
        $lineItems[] = [
            'id' => (int) $ln['id'],
            'productId' => $ln['product_id'],
            'productName' => (string) $ln['product_name'],
            'unitPrice' => (float) $ln['unit_price'],
            'quantity' => (int) $ln['quantity'],
            'lineTotal' => round((float) $ln['unit_price'] * (int) $ln['quantity'], 2),
        ];
    }

    $notes = (string) ($row['notes'] ?? '');
    $parsed = tm_admin_parse_order_notes($notes);

    $personalizations = tm_personalisation_for_order($pdo, $orderId);

    return [
        'id' => (int) $row['id'],
        'status' => (string) $row['status'],
        'customerEmail' => (string) $row['customer_email'],
        'customerName' => $row['customer_name'] !== null ? (string) $row['customer_name'] : null,
        'shippingPhone' => $row['shipping_phone'] ?? null,
        'shippingAddress' => $row['shipping_address'] ?? null,
        'shippingLandmark' => $row['shipping_landmark'] ?? null,
        'shippingCity' => $row['shipping_city'] ?? null,
        'shippingState' => $row['shipping_state'] ?? null,
        'shippingPincode' => $row['shipping_pincode'] ?? null,
        'currency' => (string) $row['currency'],
        'subtotal' => (float) $row['subtotal'],
        'itemsSubtotal' => $row['items_subtotal'] !== null ? (float) $row['items_subtotal'] : null,
        'couponCode' => $row['coupon_code'] ?? null,
        'couponDiscount' => isset($row['coupon_discount']) ? (float) $row['coupon_discount'] : null,
        'refundId' => $row['refund_id'] ?? null,
        'razorpayOrderId' => $row['razorpay_order_id'] ?? null,
        'paymentId' => $row['payment_id'] ?? null,
        'paidAt' => $row['paid_at'] ?? null,
        'notes' => $notes !== '' ? $notes : null,
        'adminNotes' => $row['admin_notes'] ?? null,
        'parsedNotes' => $parsed,
        'lines' => $lineItems,
        'personalizations' => $personalizations,
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'updatedAt' => $row['updated_at'] ?? null,
    ];
}

/**
 * @return array{shipping: ?string, freeGift: ?string, rewards: list<string>, customerNotes: ?string}
 */
function tm_admin_parse_order_notes(string $notes): array
{
    $lines = array_values(array_filter(array_map('trim', explode("\n", $notes))));
    $shipping = null;
    $freeGift = null;
    $rewards = [];
    $customer = [];

    foreach ($lines as $line) {
        if (str_starts_with($line, 'Ship:')) {
            $shipping = trim(substr($line, 5));
        } elseif (str_starts_with($line, 'Free gift:')) {
            $freeGift = trim(substr($line, 10));
        } elseif (
            str_contains($line, 'discount')
            || str_contains($line, 'shipping')
            || str_contains($line, 'Free gift chosen')
        ) {
            $rewards[] = $line;
        } else {
            $customer[] = $line;
        }
    }

    return [
        'shipping' => $shipping,
        'freeGift' => $freeGift,
        'rewards' => $rewards,
        'customerNotes' => $customer !== [] ? implode("\n", $customer) : null,
    ];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, order?: array}
 */
function tm_admin_order_update(PDO $pdo, int $orderId, array $body): array
{
    $existing = tm_admin_order_detail($pdo, $orderId);
    if ($existing === null) {
        return ['ok' => false, 'message' => 'Order not found'];
    }

    $oldStatus = (string) $existing['status'];
    $refundRequested = !empty($body['refundPayment']);

    if ($refundRequested) {
        $paymentId = (string) ($existing['paymentId'] ?? '');
        if ($paymentId === '') {
            return ['ok' => false, 'message' => 'No payment ID — cannot refund via Razorpay'];
        }
        if (!empty($existing['refundId'])) {
            return ['ok' => false, 'message' => 'Refund already processed'];
        }
        $amountPaise = (int) round((float) $existing['subtotal'] * 100);
        $refund = tm_razorpay_refund_payment($paymentId, $amountPaise);
        if (!$refund['ok']) {
            return ['ok' => false, 'message' => $refund['message']];
        }
        $body['status'] = 'refunded';
        if (!empty($refund['refundId'])) {
            $pdo->prepare('UPDATE orders SET refund_id = ? WHERE id = ?')
                ->execute([substr((string) $refund['refundId'], 0, 64), $orderId]);
        }
    }

    $sets = [];
    $params = [];

    if (isset($body['status'])) {
        $status = (string) $body['status'];
        if (!in_array($status, tm_admin_order_statuses(), true)) {
            return ['ok' => false, 'message' => 'Invalid status'];
        }
        $sets[] = 'status = :status';
        $params[':status'] = $status;
        if ($status === 'paid' && empty($existing['paidAt'])) {
            $sets[] = 'paid_at = COALESCE(paid_at, UTC_TIMESTAMP())';
        }
    }
    if (array_key_exists('adminNotes', $body)) {
        $sets[] = 'admin_notes = :admin_notes';
        $params[':admin_notes'] = substr(trim((string) $body['adminNotes']), 0, 5000) ?: null;
    }
    if (array_key_exists('shippingPhone', $body)) {
        $sets[] = 'shipping_phone = :phone';
        $params[':phone'] = substr(trim((string) $body['shippingPhone']), 0, 32) ?: null;
    }
    if (array_key_exists('shippingAddress', $body)) {
        $sets[] = 'shipping_address = :addr';
        $params[':addr'] = trim((string) $body['shippingAddress']) ?: null;
    }
    if (array_key_exists('shippingCity', $body)) {
        $sets[] = 'shipping_city = :city';
        $params[':city'] = substr(trim((string) $body['shippingCity']), 0, 128) ?: null;
    }

    if ($sets === []) {
        return ['ok' => false, 'message' => 'Nothing to update'];
    }

    $params[':id'] = $orderId;
    $sql = 'UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    if (isset($body['status'])) {
        $newStatus = (string) $body['status'];
        require_once __DIR__ . '/order_lifecycle.php';
        tm_order_on_status_change($pdo, $orderId, $oldStatus, $newStatus);
    }

    return ['ok' => true, 'order' => tm_admin_order_detail($pdo, $orderId)];
}
