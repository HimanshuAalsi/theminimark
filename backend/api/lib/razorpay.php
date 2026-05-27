<?php

declare(strict_types=1);

/**
 * Razorpay Checkout (India — UPI, cards, netbanking, wallets).
 * @see https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/
 */

/**
 * @return array{enabled: bool, key_id: string, key_secret: string, company_name: string}
 */
function tm_razorpay_is_placeholder_secret(string $secret): bool
{
    $s = strtolower(trim($secret));
    if ($s === '') {
        return true;
    }
    foreach (['paste', 'your_', 'xxxxxxxx', 'secret_here', 'changeme'] as $needle) {
        if (str_contains($s, $needle)) {
            return true;
        }
    }

    return false;
}

function tm_razorpay_settings(): array
{
    $cfg = tm_config()['razorpay'] ?? [];
    $keyId = trim((string) ($cfg['key_id'] ?? ''));
    $keySecret = trim((string) ($cfg['key_secret'] ?? ''));
    $secretOk = $keySecret !== '' && !tm_razorpay_is_placeholder_secret($keySecret);

    return [
        'enabled' => !empty($cfg['enabled']) && $keyId !== '' && $secretOk,
        'key_id' => $keyId,
        'key_secret' => $keySecret,
        'secret_ok' => $secretOk,
        'company_name' => trim((string) ($cfg['company_name'] ?? 'The Minimark')) ?: 'The Minimark',
    ];
}

/** Quick auth check against Razorpay (no order created). */
function tm_razorpay_credentials_valid(): bool
{
    $s = tm_razorpay_settings();
    if (!$s['enabled']) {
        return false;
    }

    return tm_razorpay_http('GET', '/orders?count=1')['ok'];
}

function tm_razorpay_public_config(): array
{
    $s = tm_razorpay_settings();
    $credentialsValid = $s['enabled'] ? tm_razorpay_credentials_valid() : false;

    return [
        'enabled' => $s['enabled'] && $credentialsValid,
        'keyId' => $s['enabled'] && $credentialsValid ? $s['key_id'] : null,
        'currency' => 'INR',
        'companyName' => $s['company_name'],
        'credentialsValid' => $credentialsValid,
        'setupHint' => !$s['secret_ok']
            ? 'Set razorpay.key_secret in api/config.local.php (Key Secret from Razorpay Dashboard).'
            : ($s['enabled'] && !$credentialsValid
                ? 'Razorpay rejected the API keys. Check key_id and key_secret match the same Test/Live mode pair.'
                : null),
    ];
}

/**
 * @return array{ok: bool, id?: string, amount?: int, currency?: string, message: string}
 */
function tm_razorpay_api_create_order(int $amountPaise, string $receipt, array $notes = []): array
{
    $s = tm_razorpay_settings();
    if (!$s['secret_ok']) {
        return ['ok' => false, 'message' => 'Razorpay Key Secret is missing or still a placeholder in api/config.local.php'];
    }
    if (!$s['enabled']) {
        return ['ok' => false, 'message' => 'Razorpay is not configured'];
    }
    if ($amountPaise < 100) {
        return ['ok' => false, 'message' => 'Minimum order amount is ₹1'];
    }

    $payload = [
        'amount' => $amountPaise,
        'currency' => 'INR',
        'receipt' => substr($receipt, 0, 40),
        'notes' => $notes,
    ];

    $res = tm_razorpay_http('POST', '/orders', $payload);
    if (!$res['ok']) {
        return $res;
    }
    $body = $res['body'];
    $id = isset($body['id']) ? (string) $body['id'] : '';
    if ($id === '') {
        return ['ok' => false, 'message' => 'Invalid response from Razorpay'];
    }

    return [
        'ok' => true,
        'id' => $id,
        'amount' => (int) ($body['amount'] ?? $amountPaise),
        'currency' => (string) ($body['currency'] ?? 'INR'),
        'message' => 'Razorpay order created',
    ];
}

function tm_razorpay_verify_payment_signature(string $razorpayOrderId, string $paymentId, string $signature): bool
{
    $s = tm_razorpay_settings();
    if (!$s['enabled']) {
        return false;
    }
    $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, $s['key_secret']);

    return hash_equals($expected, $signature);
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, body?: array<string, mixed>, message: string}
 */
function tm_razorpay_http(string $method, string $path, ?array $body = null): array
{
    $s = tm_razorpay_settings();
    $url = 'https://api.razorpay.com/v1' . $path;
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'message' => 'Could not connect to Razorpay'];
    }

    $headers = ['Content-Type: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $s['key_id'] . ':' . $s['key_secret'],
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? [], JSON_THROW_ON_ERROR));
    }

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || $raw === false) {
        return ['ok' => false, 'message' => 'Razorpay request failed'];
    }

    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : [];

    if ($status >= 200 && $status < 300) {
        return ['ok' => true, 'body' => $data, 'message' => 'OK'];
    }

    $msg = isset($data['error']['description'])
        ? (string) $data['error']['description']
        : 'Razorpay error (HTTP ' . $status . ')';

    return ['ok' => false, 'message' => $msg];
}

/**
 * Create shop order + Razorpay order for checkout.
 *
 * @param list<array{productId?: string|int, name: string, unitPrice: float|int, quantity: int}> $lines
 * @return array<string, mixed>
 */
function tm_payment_razorpay_start_checkout(
    PDO $pdo,
    string $customerEmail,
    ?string $customerName,
    array $lines,
    ?string $notes
): array {
    $created = tm_order_create($pdo, $customerEmail, $customerName, 'INR', $lines, $notes);
    if (!$created['ok'] || !isset($created['orderId'])) {
        return $created;
    }

    $orderId = (int) $created['orderId'];
    $order = tm_order_get($pdo, $orderId);
    if ($order === null) {
        return ['ok' => false, 'message' => 'Order not found after create'];
    }

    $subtotal = (float) $order['subtotal'];
    $amountPaise = (int) round($subtotal * 100);
    $rzp = tm_razorpay_api_create_order($amountPaise, 'tm_' . $orderId, [
        'shop_order_id' => (string) $orderId,
    ]);
    if (!$rzp['ok'] || !isset($rzp['id'])) {
        $msg = $rzp['message'];
        if (stripos($msg, 'authentication failed') !== false) {
            $msg = 'Razorpay authentication failed — fix key_secret in public_html/api/config.local.php (must match key_id from the same API key pair).';
        }

        return ['ok' => false, 'message' => $msg, 'orderId' => $orderId];
    }

    tm_order_set_razorpay_order_id($pdo, $orderId, $rzp['id']);

    $settings = tm_razorpay_settings();

    return [
        'ok' => true,
        'orderId' => $orderId,
        'razorpayOrderId' => $rzp['id'],
        'amount' => $rzp['amount'] ?? $amountPaise,
        'currency' => 'INR',
        'keyId' => $settings['key_id'],
        'companyName' => $settings['company_name'],
        'customer' => [
            'name' => $customerName ?? '',
            'email' => $customerEmail,
        ],
        'message' => 'Checkout ready',
    ];
}

/**
 * @return array{ok: bool, orderId?: int, message: string}
 */
function tm_payment_razorpay_verify(
    PDO $pdo,
    int $orderId,
    string $razorpayOrderId,
    string $paymentId,
    string $signature
): array {
    $razorpayOrderId = trim($razorpayOrderId);
    $paymentId = trim($paymentId);
    $signature = trim($signature);

    if ($razorpayOrderId === '' || $paymentId === '' || $signature === '') {
        return ['ok' => false, 'message' => 'Missing payment details'];
    }

    if (!tm_razorpay_verify_payment_signature($razorpayOrderId, $paymentId, $signature)) {
        return ['ok' => false, 'message' => 'Payment verification failed'];
    }

    $order = tm_order_get($pdo, $orderId);
    if ($order === null) {
        return ['ok' => false, 'message' => 'Order not found'];
    }

    $storedRzp = (string) ($order['razorpay_order_id'] ?? '');
    if ($storedRzp !== '' && $storedRzp !== $razorpayOrderId) {
        return ['ok' => false, 'message' => 'Order does not match payment'];
    }

    $mark = tm_order_mark_paid($pdo, $orderId, $paymentId);
    if (!$mark['ok']) {
        return $mark;
    }

    return ['ok' => true, 'orderId' => $orderId, 'message' => 'Payment successful'];
}
