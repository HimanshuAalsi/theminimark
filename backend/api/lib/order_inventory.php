<?php

declare(strict_types=1);

/** Decrement stock when order is paid. Skips custom / missing products. */
function tm_order_inventory_decrement(PDO $pdo, int $orderId): void
{
    $st = $pdo->prepare(
        'SELECT product_id, quantity FROM order_lines WHERE order_id = ? AND product_id IS NOT NULL'
    );
    $st->execute([$orderId]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($lines as $line) {
        $pid = (string) $line['product_id'];
        if ($pid === '' || str_starts_with($pid, 'custom-')) {
            continue;
        }
        $qty = max(1, (int) $line['quantity']);
        $pdo->prepare(
            'UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?)
             WHERE id = ? AND stock_quantity IS NOT NULL'
        )->execute([$qty, $pid]);
    }
}

/** Restore stock when order cancelled/refunded after payment. */
function tm_order_inventory_restore(PDO $pdo, int $orderId): void
{
    $st = $pdo->prepare(
        'SELECT product_id, quantity FROM order_lines WHERE order_id = ? AND product_id IS NOT NULL'
    );
    $st->execute([$orderId]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($lines as $line) {
        $pid = (string) $line['product_id'];
        if ($pid === '' || str_starts_with($pid, 'custom-')) {
            continue;
        }
        $qty = max(1, (int) $line['quantity']);
        $pdo->prepare(
            'UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ? AND stock_quantity IS NOT NULL'
        )->execute([$qty, $pid]);
    }
}
