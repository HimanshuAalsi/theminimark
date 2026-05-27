<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

tm_cors();

if (tm_method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $path = tm_route_path();
    $method = tm_method();
    $segments = $path === '' ? [] : explode('/', $path);

    if ($segments === ['v1', 'health']) {
        tm_json(['ok' => true, 'service' => 'theminimark-api']);
        exit;
    }

    if ($segments === ['v1', 'ready']) {
        tm_db()->query('SELECT 1');
        tm_json(['ok' => true, 'database' => true]);
        exit;
    }

    if ($segments === ['v1', 'site'] && $method === 'GET') {
        tm_json(tm_site_payload());
        exit;
    }

    if ($segments === ['v1', 'products'] && $method === 'GET') {
        $pdo = tm_db();
        $filters = [
            'category' => $_GET['category'] ?? null,
            'q' => $_GET['q'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'home_bestseller' => isset($_GET['home_bestseller']) ? (string) $_GET['home_bestseller'] : null,
            'home_secondary' => isset($_GET['home_secondary']) ? (string) $_GET['home_secondary'] : null,
        ];
        $rows = tm_products_query($pdo, $filters);
        $items = tm_products_public_rows($rows);
        tm_json(['items' => $items, 'meta' => ['count' => count($items)]]);
        exit;
    }

    if (count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'products' && $method === 'GET') {
        $slug = $segments[2];
        $row = tm_product_by_slug(tm_db(), $slug);
        if ($row === null) {
            tm_json(['message' => 'Product not found'], 404);
            exit;
        }
        tm_json(tm_product_public($row));
        exit;
    }

    if ($segments === ['v1', 'newsletter'] && $method === 'POST') {
        $body = tm_read_json_body();
        $email = isset($body['email']) ? (string) $body['email'] : '';
        $source = isset($body['source']) ? (string) $body['source'] : null;
        $result = tm_newsletter_subscribe(tm_db(), $email, $source);
        tm_json($result, $result['ok'] ? 201 : 400);
        exit;
    }

    if ($segments === ['v1', 'orders'] && $method === 'POST') {
        $body = tm_read_json_body();
        $email = isset($body['customerEmail']) ? (string) $body['customerEmail'] : '';
        $name = isset($body['customerName']) ? (string) $body['customerName'] : null;
        $currency = isset($body['currency']) ? (string) $body['currency'] : 'USD';
        $lines = isset($body['lines']) && is_array($body['lines']) ? $body['lines'] : [];
        $notes = isset($body['notes']) ? (string) $body['notes'] : null;
        $result = tm_order_create(tm_db(), $email, $name, $currency, $lines, $notes);
        tm_json($result, $result['ok'] ? 201 : 400);
        exit;
    }

    if ($segments === ['v1', 'payments', 'razorpay', 'config'] && $method === 'GET') {
        tm_json(tm_razorpay_public_config());
        exit;
    }

    if ($segments === ['v1', 'payments', 'razorpay', 'checkout'] && $method === 'POST') {
        if (!tm_razorpay_settings()['enabled']) {
            tm_json(['ok' => false, 'message' => 'Online payment is not enabled'], 503);
            exit;
        }
        $body = tm_read_json_body();
        $email = isset($body['customerEmail']) ? (string) $body['customerEmail'] : '';
        $name = isset($body['customerName']) ? (string) $body['customerName'] : null;
        $lines = isset($body['lines']) && is_array($body['lines']) ? $body['lines'] : [];
        $notes = isset($body['notes']) ? (string) $body['notes'] : null;
        $result = tm_payment_razorpay_start_checkout(tm_db(), $email, $name, $lines, $notes);
        tm_json($result, $result['ok'] ? 201 : 400);
        exit;
    }

    if ($segments === ['v1', 'payments', 'razorpay', 'verify'] && $method === 'POST') {
        if (!tm_razorpay_settings()['enabled']) {
            tm_json(['ok' => false, 'message' => 'Online payment is not enabled'], 503);
            exit;
        }
        $body = tm_read_json_body();
        $orderId = isset($body['orderId']) ? (int) $body['orderId'] : 0;
        $rzpOrderId = isset($body['razorpayOrderId']) ? (string) $body['razorpayOrderId'] : '';
        $paymentId = isset($body['razorpayPaymentId']) ? (string) $body['razorpayPaymentId'] : '';
        $signature = isset($body['razorpaySignature']) ? (string) $body['razorpaySignature'] : '';
        $result = tm_payment_razorpay_verify(tm_db(), $orderId, $rzpOrderId, $paymentId, $signature);
        tm_json($result, $result['ok'] ? 200 : 400);
        exit;
    }

    $bearer = tm_auth_bearer_token();

    if (isset($segments[0], $segments[1]) && $segments[0] === 'v1' && $segments[1] === 'wishlist') {
        $pdo = tm_db();
        $auth = tm_auth_require_user($pdo, $bearer);
        if (!$auth['ok']) {
            tm_json(['message' => $auth['message']], 401);
            exit;
        }
        $userId = (int) $auth['userId'];

        if ($segments === ['v1', 'wishlist'] && $method === 'GET') {
            tm_json([
                'ok' => true,
                'items' => tm_wishlist_products($pdo, $userId),
                'productIds' => tm_wishlist_product_ids($pdo, $userId),
            ]);
            exit;
        }

        if ($segments === ['v1', 'wishlist', 'sync'] && $method === 'POST') {
            $body = tm_read_json_body();
            $ids = isset($body['productIds']) && is_array($body['productIds']) ? $body['productIds'] : [];
            $result = tm_wishlist_sync($pdo, $userId, $ids);
            tm_json($result, $result['ok'] ? 200 : 400);
            exit;
        }

        if ($segments === ['v1', 'wishlist'] && $method === 'POST') {
            $body = tm_read_json_body();
            $productId = isset($body['productId']) ? (string) $body['productId'] : '';
            $result = tm_wishlist_add($pdo, $userId, $productId);
            tm_json($result, $result['ok'] ? 201 : 400);
            exit;
        }

        if (count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'wishlist' && $method === 'DELETE') {
            $result = tm_wishlist_remove($pdo, $userId, $segments[2]);
            tm_json($result, $result['ok'] ? 200 : 400);
            exit;
        }
    }

    if (isset($segments[0], $segments[1]) && $segments[0] === 'v1' && $segments[1] === 'auth') {
        $pdo = tm_db();

        if ($segments === ['v1', 'auth', 'install-status'] && $method === 'GET') {
            tm_json(tm_auth_install_status($pdo));
            exit;
        }

        if ($segments === ['v1', 'auth', 'register'] && $method === 'POST') {
            $body = tm_read_json_body();
            $email = isset($body['email']) ? (string) $body['email'] : '';
            $password = isset($body['password']) ? (string) $body['password'] : '';
            $fullName = isset($body['fullName']) ? (string) $body['fullName'] : '';
            $result = tm_auth_register($pdo, $email, $password, $fullName);
            tm_json($result, $result['ok'] ? 201 : 400);
            exit;
        }

        if ($segments === ['v1', 'auth', 'login'] && $method === 'POST') {
            $body = tm_read_json_body();
            $email = isset($body['email']) ? (string) $body['email'] : '';
            $password = isset($body['password']) ? (string) $body['password'] : '';
            $result = tm_auth_login($pdo, $email, $password);
            tm_json($result, $result['ok'] ? 200 : 401);
            exit;
        }

        if ($segments === ['v1', 'auth', 'logout'] && $method === 'POST') {
            $t = $bearer ?? '';
            tm_auth_logout($pdo, $t);
            tm_json(['ok' => true]);
            exit;
        }

        if ($segments === ['v1', 'auth', 'me'] && $method === 'GET') {
            $result = tm_auth_me($pdo, $bearer);
            tm_json($result, $result['ok'] ? 200 : 401);
            exit;
        }

        if ($segments === ['v1', 'auth', 'me'] && $method === 'PATCH') {
            $auth = tm_auth_require_user($pdo, $bearer);
            if (!$auth['ok']) {
                tm_json(['message' => $auth['message']], 401);
                exit;
            }
            $body = tm_read_json_body();
            $result = tm_auth_update_profile($pdo, (int) $auth['userId'], $body);
            tm_json($result, $result['ok'] ? 200 : 400);
            exit;
        }

        if ($segments === ['v1', 'auth', 'change-password'] && $method === 'POST') {
            $auth = tm_auth_require_user($pdo, $bearer);
            if (!$auth['ok']) {
                tm_json(['message' => $auth['message']], 401);
                exit;
            }
            $body = tm_read_json_body();
            $cur = isset($body['currentPassword']) ? (string) $body['currentPassword'] : '';
            $new = isset($body['newPassword']) ? (string) $body['newPassword'] : '';
            $result = tm_auth_change_password($pdo, (int) $auth['userId'], $cur, $new);
            tm_json($result, $result['ok'] ? 200 : 400);
            exit;
        }

        if ($segments === ['v1', 'auth', 'forgot-password'] && $method === 'POST') {
            $body = tm_read_json_body();
            $email = isset($body['email']) ? (string) $body['email'] : '';
            $result = tm_auth_forgot_password($pdo, $email);
            tm_json($result, 200);
            exit;
        }

        if ($segments === ['v1', 'auth', 'reset-password'] && $method === 'POST') {
            $body = tm_read_json_body();
            $token = isset($body['token']) ? (string) $body['token'] : '';
            $password = isset($body['password']) ? (string) $body['password'] : '';
            $result = tm_auth_reset_password($pdo, $token, $password);
            tm_json($result, $result['ok'] ? 200 : 400);
            exit;
        }
    }

    tm_json(['message' => 'Not found', 'path' => $path], 404);
} catch (PDOException $e) {
    tm_json([
        'message' => 'Database unavailable (MySQL did not accept the connection). Match `db.host`, `db.port`, `db.user`, `db.pass`, and `db.name` in `backend/api/config.local.php` to a running server — see `backend/README.md` (Docker on host: port 3307, password root; local install: often port 3306).',
        'error' => getenv('TM_DEBUG') === '1' ? $e->getMessage() : null,
    ], 503);
} catch (Throwable $e) {
    tm_json([
        'message' => 'Server error',
        'error' => getenv('TM_DEBUG') === '1' ? $e->getMessage() : null,
    ], 500);
}
