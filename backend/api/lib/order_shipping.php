<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function tm_order_parse_shipping_from_body(array $body): array
{
    $ship = isset($body['shipping']) && is_array($body['shipping']) ? $body['shipping'] : $body;

    $phone = preg_replace('/\D/', '', (string) ($ship['phone'] ?? ''));
    if (strlen($phone) === 12 && str_starts_with($phone, '91')) {
        $phone = substr($phone, 2);
    }
    if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
        $phone = substr($phone, 1);
    }

    return [
        'phone' => substr($phone, 0, 10),
        'addressLine1' => trim((string) ($ship['addressLine1'] ?? $ship['address'] ?? '')),
        'addressLine2' => trim((string) ($ship['addressLine2'] ?? '')),
        'landmark' => trim((string) ($ship['landmark'] ?? '')),
        'pincode' => substr(preg_replace('/\D/', '', (string) ($ship['pincode'] ?? '')), 0, 6),
        'city' => trim((string) ($ship['city'] ?? '')),
        'state' => trim((string) ($ship['state'] ?? '')),
    ];
}

/**
 * @param array<string, mixed> $shipping
 * @return array{ok: bool, message?: string}
 */
function tm_order_validate_shipping(array $shipping): array
{
    $phone = (string) ($shipping['phone'] ?? '');
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        return ['ok' => false, 'message' => 'Valid 10-digit mobile number is required'];
    }

    if (strlen(trim((string) ($shipping['addressLine1'] ?? ''))) < 5) {
        return ['ok' => false, 'message' => 'Complete delivery address is required'];
    }

    $pin = (string) ($shipping['pincode'] ?? '');
    if (!preg_match('/^\d{6}$/', $pin)) {
        return ['ok' => false, 'message' => 'Valid 6-digit PIN code is required'];
    }

    if (strlen(trim((string) ($shipping['city'] ?? ''))) < 2) {
        return ['ok' => false, 'message' => 'City is required'];
    }

    if (strlen(trim((string) ($shipping['state'] ?? ''))) < 2) {
        return ['ok' => false, 'message' => 'State is required'];
    }

    return ['ok' => true];
}

function tm_orders_has_shipping_column(PDO $pdo, string $column): bool
{
    static $cache = [];
    if (isset($cache[$column])) {
        return $cache[$column];
    }
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'orders\' AND COLUMN_NAME = ?'
    );
    $st->execute([$column]);
    $cache[$column] = (int) $st->fetchColumn() > 0;

    return $cache[$column];
}

/**
 * @param array<string, mixed> $shipping
 */
function tm_order_format_shipping_address(array $shipping): string
{
    return implode("\n", array_values(array_filter([
        trim((string) ($shipping['addressLine1'] ?? '')),
        trim((string) ($shipping['addressLine2'] ?? '')),
        trim((string) ($shipping['landmark'] ?? '')) !== ''
            ? 'Landmark: ' . trim((string) $shipping['landmark'])
            : '',
    ])));
}

/**
 * @param array<string, mixed> $shipping
 */
function tm_order_save_shipping(PDO $pdo, int $orderId, array $shipping): void
{
    $phone = (string) ($shipping['phone'] ?? '');
    $address = tm_order_format_shipping_address($shipping);
    $city = trim((string) ($shipping['city'] ?? ''));
    $state = trim((string) ($shipping['state'] ?? ''));
    $pin = trim((string) ($shipping['pincode'] ?? ''));
    $landmark = trim((string) ($shipping['landmark'] ?? ''));

    $sets = ['shipping_phone = :phone', 'shipping_address = :address', 'shipping_city = :city'];
    $params = [
        ':phone' => $phone,
        ':address' => $address !== '' ? substr($address, 0, 5000) : null,
        ':city' => $city !== '' ? substr($city, 0, 128) : null,
        ':id' => $orderId,
    ];

    if (tm_orders_has_shipping_column($pdo, 'shipping_state')) {
        $sets[] = 'shipping_state = :state';
        $params[':state'] = $state !== '' ? substr($state, 0, 128) : null;
    }
    if (tm_orders_has_shipping_column($pdo, 'shipping_pincode')) {
        $sets[] = 'shipping_pincode = :pincode';
        $params[':pincode'] = $pin !== '' ? substr($pin, 0, 10) : null;
    }
    if (tm_orders_has_shipping_column($pdo, 'shipping_landmark')) {
        $sets[] = 'shipping_landmark = :landmark';
        $params[':landmark'] = $landmark !== '' ? substr($landmark, 0, 255) : null;
    }

    $sql = 'UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $st = $pdo->prepare($sql);
    $st->execute($params);
}
