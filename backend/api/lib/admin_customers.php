<?php

declare(strict_types=1);

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_admin_customers_list(PDO $pdo, array $query): array
{
    $where = ['1=1'];
    $params = [];
    if (!empty($query['q'])) {
        $where[] = '(u.email LIKE :q OR u.full_name LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(100, max(10, (int) ($query['perPage'] ?? 25)));
    $offset = ($page - 1) * $perPage;
    $whereSql = implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) FROM users u WHERE ' . $whereSql;
    $st = $pdo->prepare($countSql);
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = 'SELECT u.id, u.email, u.full_name, u.role, u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE LOWER(o.customer_email) = LOWER(u.email)) AS order_count,
            (SELECT COALESCE(SUM(o.subtotal), 0) FROM orders o
             WHERE LOWER(o.customer_email) = LOWER(u.email)
             AND o.status IN (\'paid\',\'processing\',\'shipped\',\'delivered\')) AS order_revenue
            FROM users u WHERE ' . $whereSql . '
            ORDER BY u.created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'fullName' => (string) $row['full_name'],
            'role' => (string) ($row['role'] ?? 'customer'),
            'orderCount' => (int) $row['order_count'],
            'orderRevenue' => round((float) $row['order_revenue'], 2),
            'createdAt' => (string) $row['created_at'],
        ];
    }

    return [
        'items' => $items,
        'meta' => ['count' => count($items), 'total' => $total, 'page' => $page, 'perPage' => $perPage],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function tm_admin_customer_detail(PDO $pdo, int $userId): ?array
{
    $st = $pdo->prepare('SELECT id, email, full_name, role, created_at FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    $email = (string) $row['email'];
    $st = $pdo->prepare(
        'SELECT id, status, subtotal, currency, created_at
         FROM orders WHERE LOWER(customer_email) = LOWER(?)
         ORDER BY created_at DESC LIMIT 20'
    );
    $st->execute([$email]);
    $orders = [];
    while ($o = $st->fetch(PDO::FETCH_ASSOC)) {
        $orders[] = [
            'id' => (int) $o['id'],
            'status' => (string) $o['status'],
            'subtotal' => (float) $o['subtotal'],
            'currency' => (string) $o['currency'],
            'createdAt' => (string) $o['created_at'],
        ];
    }

    $st = $pdo->prepare(
        "SELECT COUNT(*) AS c, COALESCE(SUM(subtotal), 0) AS revenue
         FROM orders WHERE LOWER(customer_email) = LOWER(?)
         AND status IN ('paid','processing','shipped','delivered')"
    );
    $st->execute([$email]);
    $agg = $st->fetch(PDO::FETCH_ASSOC) ?: ['c' => 0, 'revenue' => 0];

    return [
        'id' => (int) $row['id'],
        'email' => $email,
        'fullName' => (string) $row['full_name'],
        'role' => (string) ($row['role'] ?? 'customer'),
        'orderCount' => (int) $agg['c'],
        'orderRevenue' => round((float) $agg['revenue'], 2),
        'createdAt' => (string) $row['created_at'],
        'recentOrders' => $orders,
    ];
}
