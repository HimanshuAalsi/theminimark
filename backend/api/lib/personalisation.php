<?php

declare(strict_types=1);

/** @return list<string> */
function tm_personalise_product_types(): array
{
    return ['bookmark', 'calendar', 'card', 'magnet'];
}

function tm_personalise_is_type(string $type): bool
{
    return in_array($type, tm_personalise_product_types(), true);
}

/** Ensure stored path is under /uploads/personalise/ */
function tm_personalise_valid_photo_path(string $path): bool
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, '/uploads/personalise/')) {
        return false;
    }
    if (str_contains($path, '..')) {
        return false;
    }
    $base = realpath(tm_uploads_base_dir());
    if ($base === false) {
        return false;
    }
    $rel = ltrim(str_replace('/uploads/', '', $path), '/');
    $full = realpath($base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));

    return $full !== false && str_starts_with($full, $base) && is_file($full);
}

/**
 * @param array<string, mixed> $data
 */
function tm_personalisation_save_for_line(PDO $pdo, int $orderLineId, array $data): void
{
    if (!tm_db_table_exists($pdo, 'order_line_personalisation')) {
        return;
    }

    $type = isset($data['type']) ? (string) $data['type'] : '';
    if (!tm_personalise_is_type($type)) {
        return;
    }

    $photoPath = isset($data['photoPath']) ? trim((string) $data['photoPath']) : '';
    if (!tm_personalise_valid_photo_path($photoPath)) {
        return;
    }

    $zoom = round((float) ($data['zoom'] ?? 1), 2);
    $zoom = max(1.0, min(3.0, $zoom));
    $posX = round((float) ($data['posX'] ?? 50), 2);
    $posY = round((float) ($data['posY'] ?? 50), 2);
    $posX = max(0.0, min(100.0, $posX));
    $posY = max(0.0, min(100.0, $posY));

    $options = $data['options'] ?? [];
    if (!is_array($options)) {
        $options = [];
    }
    $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
    if ($optionsJson === false) {
        $optionsJson = '{}';
    }

    $st = $pdo->prepare(
        'INSERT INTO order_line_personalisation (order_line_id, product_type, photo_path, zoom, pos_x, pos_y, options_json)
         VALUES (:line_id, :type, :photo, :zoom, :pos_x, :pos_y, :options)
         ON DUPLICATE KEY UPDATE
           product_type = VALUES(product_type),
           photo_path = VALUES(photo_path),
           zoom = VALUES(zoom),
           pos_x = VALUES(pos_x),
           pos_y = VALUES(pos_y),
           options_json = VALUES(options_json)'
    );
    $st->execute([
        ':line_id' => $orderLineId,
        ':type' => $type,
        ':photo' => substr($photoPath, 0, 512),
        ':zoom' => $zoom,
        ':pos_x' => $posX,
        ':pos_y' => $posY,
        ':options' => $optionsJson,
    ]);
}

function tm_db_table_exists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $st->execute([$table]);

    return (bool) $st->fetchColumn();
}

/**
 * @return list<array<string, mixed>>
 */
function tm_personalisation_for_order(PDO $pdo, int $orderId): array
{
    if (!tm_db_table_exists($pdo, 'order_line_personalisation')) {
        return [];
    }

    $st = $pdo->prepare(
        'SELECT p.*, ol.product_name, ol.quantity, ol.unit_price, ol.product_id
         FROM order_line_personalisation p
         INNER JOIN order_lines ol ON ol.id = p.order_line_id
         WHERE ol.order_id = ?
         ORDER BY p.id'
    );
    $st->execute([$orderId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map('tm_personalisation_row_public', $rows);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_personalisation_row_public(array $row): array
{
    $options = json_decode((string) ($row['options_json'] ?? '{}'), true);
    if (!is_array($options)) {
        $options = [];
    }
    $photoPath = (string) ($row['photo_path'] ?? '');

    return [
        'id' => (int) $row['id'],
        'orderLineId' => (int) $row['order_line_id'],
        'productType' => (string) $row['product_type'],
        'productName' => (string) ($row['product_name'] ?? ''),
        'productId' => $row['product_id'] ?? null,
        'quantity' => (int) ($row['quantity'] ?? 1),
        'unitPrice' => isset($row['unit_price']) ? (float) $row['unit_price'] : null,
        'photoPath' => $photoPath,
        'photoUrl' => tm_upload_resolve_url($photoPath),
        'zoom' => (float) ($row['zoom'] ?? 1),
        'posX' => (float) ($row['pos_x'] ?? 50),
        'posY' => (float) ($row['pos_y'] ?? 50),
        'options' => $options,
        'createdAt' => (string) ($row['created_at'] ?? ''),
    ];
}

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_admin_personalisations_list(PDO $pdo, array $query): array
{
    if (!tm_db_table_exists($pdo, 'order_line_personalisation')) {
        return ['items' => [], 'meta' => ['count' => 0, 'total' => 0, 'page' => 1, 'perPage' => 25]];
    }

    $where = ['1=1'];
    $params = [];

    if (!empty($query['type']) && $query['type'] !== 'all') {
        $where[] = 'p.product_type = :type';
        $params[':type'] = (string) $query['type'];
    }
    if (!empty($query['q'])) {
        $where[] = '(o.customer_email LIKE :q OR o.customer_name LIKE :q OR CAST(o.id AS CHAR) LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(100, max(10, (int) ($query['perPage'] ?? 25)));
    $offset = ($page - 1) * $perPage;
    $whereSql = implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*)
                 FROM order_line_personalisation p
                 INNER JOIN order_lines ol ON ol.id = p.order_line_id
                 INNER JOIN orders o ON o.id = ol.order_id
                 WHERE ' . $whereSql;
    $st = $pdo->prepare($countSql);
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = 'SELECT p.*, ol.product_name, ol.quantity, ol.unit_price, ol.product_id, o.id AS order_id,
                   o.status AS order_status, o.customer_email, o.customer_name, o.created_at AS order_created_at
            FROM order_line_personalisation p
            INNER JOIN order_lines ol ON ol.id = p.order_line_id
            INNER JOIN orders o ON o.id = ol.order_id
            WHERE ' . $whereSql . '
            ORDER BY p.created_at DESC
            LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $pub = tm_personalisation_row_public($row);
        $pub['orderId'] = (int) ($row['order_id'] ?? 0);
        $pub['orderStatus'] = (string) ($row['order_status'] ?? '');
        $pub['customerEmail'] = (string) ($row['customer_email'] ?? '');
        $pub['customerName'] = $row['customer_name'] !== null ? (string) $row['customer_name'] : null;
        $pub['orderCreatedAt'] = (string) ($row['order_created_at'] ?? '');
        $items[] = $pub;
    }

    return [
        'items' => $items,
        'meta' => ['count' => count($items), 'total' => $total, 'page' => $page, 'perPage' => $perPage],
    ];
}
