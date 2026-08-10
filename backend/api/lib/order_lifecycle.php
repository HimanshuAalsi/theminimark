<?php

declare(strict_types=1);

require_once __DIR__ . '/order_inventory.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/coupons.php';

/** Statuses that count as "paid" for inventory purposes. */
function tm_order_paid_statuses(): array
{
    return ['paid', 'processing', 'shipped', 'delivered'];
}

function tm_order_on_status_change(PDO $pdo, int $orderId, string $oldStatus, string $newStatus): void
{
    if ($oldStatus === $newStatus) {
        return;
    }

    $wasPaid = in_array($oldStatus, tm_order_paid_statuses(), true);
    $isPaid = in_array($newStatus, tm_order_paid_statuses(), true);

    if (!$wasPaid && $isPaid) {
        tm_order_inventory_decrement($pdo, $orderId);
        tm_mail_order_event($pdo, $orderId, $newStatus === 'paid' ? 'paid' : 'placed');
    }

    if ($newStatus === 'shipped') {
        tm_mail_order_event($pdo, $orderId, 'shipped');
    }
    if ($newStatus === 'delivered') {
        tm_mail_order_event($pdo, $orderId, 'delivered');
    }

    if ($wasPaid && in_array($newStatus, ['cancelled', 'refunded'], true)) {
        tm_order_inventory_restore($pdo, $orderId);
    }

    if ($newStatus === 'refunded') {
        tm_mail_order_event($pdo, $orderId, 'refunded');
    }
}

function tm_order_after_create(PDO $pdo, int $orderId): void
{
    tm_mail_order_event($pdo, $orderId, 'placed');

    $st = $pdo->prepare('SELECT coupon_code FROM orders WHERE id = ?');
    $st->execute([$orderId]);
    $code = (string) ($st->fetchColumn() ?: '');
    if ($code !== '') {
        tm_coupon_increment_usage($pdo, $code);
    }
}
