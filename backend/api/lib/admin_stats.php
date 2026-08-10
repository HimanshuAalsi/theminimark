<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_orders.php';

/**
 * @return list<array{date: string, revenue: float, orders: int}>
 */
function tm_admin_revenue_by_day(PDO $pdo, int $days = 30): array
{
    $days = min(90, max(7, $days));
    $st = $pdo->prepare(
        "SELECT DATE(created_at) AS d,
                COALESCE(SUM(subtotal), 0) AS revenue,
                COUNT(*) AS orders
         FROM orders
         WHERE status IN ('paid','processing','shipped','delivered')
           AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(created_at)
         ORDER BY d ASC"
    );
    $st->execute([$days - 1]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'date' => (string) $row['d'],
            'revenue' => round((float) $row['revenue'], 2),
            'orders' => (int) $row['orders'],
        ];
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function tm_admin_dashboard_stats(PDO $pdo): array
{
    $productsTotal = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $productsActive = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
    $ordersTotal = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $ordersPending = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $ordersPaid = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','processing','shipped','delivered')")->fetchColumn();
    $subscribers = (int) $pdo->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn();
    $customers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $lowStock = 0;
    $outOfStock = 0;
    try {
        $lowStock = (int) $pdo->query(
            'SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity IS NOT NULL AND stock_quantity > 0 AND stock_quantity <= 5'
        )->fetchColumn();
        $outOfStock = (int) $pdo->query(
            'SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity IS NOT NULL AND stock_quantity = 0'
        )->fetchColumn();
    } catch (PDOException) {
        /* stock_quantity column optional */
    }

    $ordersToFulfill = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE status IN ('paid','processing')"
    )->fetchColumn();

    $revenuePaid = (float) ($pdo->query(
        "SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE status IN ('paid','processing','shipped','delivered')"
    )->fetchColumn() ?: 0);

    $revenueToday = (float) ($pdo->query(
        "SELECT COALESCE(SUM(subtotal), 0) FROM orders
         WHERE status IN ('paid','processing','shipped','delivered') AND DATE(created_at) = CURDATE()"
    )->fetchColumn() ?: 0);

    $st = $pdo->query(
        "SELECT o.id, o.status, o.customer_email, o.customer_name, o.subtotal, o.currency, o.created_at,
                (SELECT COUNT(*) FROM order_lines ol WHERE ol.order_id = o.id) AS line_count,
                (SELECT GROUP_CONCAT(CONCAT(ol.product_name, ' ×', ol.quantity) ORDER BY ol.id SEPARATOR ', ')
                 FROM order_lines ol WHERE ol.order_id = o.id) AS line_summary
         FROM orders o ORDER BY o.created_at DESC LIMIT 8"
    );
    $recent = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $recent[] = tm_admin_order_summary($row);
    }

    $byStatus = [];
    $st2 = $pdo->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status');
    while ($row = $st2->fetch(PDO::FETCH_ASSOC)) {
        $byStatus[(string) $row['status']] = (int) $row['c'];
    }

    $topProducts = [];
    $st3 = $pdo->query(
        'SELECT ol.product_name, SUM(ol.quantity) AS qty, SUM(ol.unit_price * ol.quantity) AS revenue
         FROM order_lines ol
         INNER JOIN orders o ON o.id = ol.order_id
         WHERE o.status IN (\'paid\',\'processing\',\'shipped\',\'delivered\')
         GROUP BY ol.product_name ORDER BY qty DESC LIMIT 6'
    );
    while ($row = $st3->fetch(PDO::FETCH_ASSOC)) {
        $topProducts[] = [
            'name' => (string) $row['product_name'],
            'quantitySold' => (int) $row['qty'],
            'revenue' => round((float) $row['revenue'], 2),
        ];
    }

    return [
        'products' => ['total' => $productsTotal, 'active' => $productsActive],
        'orders' => [
            'total' => $ordersTotal,
            'pending' => $ordersPending,
            'paid' => $ordersPaid,
            'byStatus' => $byStatus,
        ],
        'revenue' => [
            'totalPaid' => round($revenuePaid, 2),
            'today' => round($revenueToday, 2),
            'currency' => 'INR',
        ],
        'newsletterSubscribers' => $subscribers,
        'registeredCustomers' => $customers,
        'inventory' => [
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
        ],
        'ordersToFulfill' => $ordersToFulfill,
        'recentOrders' => $recent,
        'topProducts' => $topProducts,
        'revenueByDay' => tm_admin_revenue_by_day($pdo, 30),
    ];
}
