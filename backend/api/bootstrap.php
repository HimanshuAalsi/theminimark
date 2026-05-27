<?php

declare(strict_types=1);

header('X-Content-Type-Options: nosniff');

$configPath = __DIR__ . '/config.local.php';
if (!is_file($configPath)) {
    $configPath = __DIR__ . '/config.php';
}
if (!is_file($configPath)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'API is not configured. Copy config.example.php to config.local.php and set database credentials.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** @var array{db: array<string, mixed>, api_base_path: string, cors_origin: string} $GLOBALS['tm_config'] */
$GLOBALS['tm_config'] = require $configPath;

require __DIR__ . '/lib/http.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/products.php';
require __DIR__ . '/lib/site.php';
require __DIR__ . '/lib/newsletter.php';
require __DIR__ . '/lib/orders.php';
require __DIR__ . '/lib/razorpay.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/wishlist.php';
