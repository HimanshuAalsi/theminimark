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
require_once __DIR__ . '/lib/uploads.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/products.php';
require __DIR__ . '/lib/site.php';
require __DIR__ . '/lib/admin_site.php';
require __DIR__ . '/lib/admin_free_gifts.php';
require __DIR__ . '/lib/admin_feature_collections.php';
require __DIR__ . '/lib/subcategories.php';
require __DIR__ . '/lib/newsletter.php';
require __DIR__ . '/lib/shipping.php';
require __DIR__ . '/lib/order_shipping.php';
require __DIR__ . '/lib/coupons.php';
require __DIR__ . '/lib/orders.php';
require __DIR__ . '/lib/order_inventory.php';
require __DIR__ . '/lib/mail.php';
require __DIR__ . '/lib/order_lifecycle.php';
require __DIR__ . '/lib/personalisation.php';
require __DIR__ . '/lib/razorpay.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/wishlist.php';
require_once __DIR__ . '/lib/product_images_store.php';
require_once __DIR__ . '/lib/admin_categories.php';
require_once __DIR__ . '/lib/admin_bulk.php';
require_once __DIR__ . '/lib/admin_products.php';
require __DIR__ . '/lib/admin_orders.php';
require_once __DIR__ . '/lib/admin_staff.php';
require __DIR__ . '/lib/admin_stats.php';
require __DIR__ . '/lib/admin_customers.php';
require __DIR__ . '/lib/admin_newsletter.php';
require __DIR__ . '/lib/admin_coupons.php';
require __DIR__ . '/lib/blog.php';
require __DIR__ . '/lib/admin_blog.php';
require __DIR__ . '/lib/admin_converter.php';
require __DIR__ . '/admin_routes.php';
