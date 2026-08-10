<?php

declare(strict_types=1);

/**
 * Admin API routes under /v1/admin/*
 *
 * @return bool True if route was handled
 */
function tm_admin_dispatch(array $segments, string $method, ?string $bearer): bool
{
    if (count($segments) < 2 || $segments[0] !== 'v1' || $segments[1] !== 'admin') {
        return false;
    }

    $pdo = tm_db();

    if ($segments === ['v1', 'admin', 'login'] && $method === 'POST') {
        $body = tm_read_json_body();
        $result = tm_auth_login($pdo, (string) ($body['email'] ?? ''), (string) ($body['password'] ?? ''));
        if (!$result['ok']) {
            tm_json($result, 401);
            return true;
        }
        if ((string) (($result['user']['role'] ?? 'customer')) !== 'admin'
            && !tm_auth_is_staff((string) ($result['user']['role'] ?? 'customer'))) {
            tm_json(['ok' => false, 'message' => 'This account is not authorized for admin.'], 403);
            return true;
        }
        tm_json($result, 200);
        return true;
    }

    if ($segments === ['v1', 'admin', 'me'] && $method === 'GET') {
        $auth = tm_auth_require_staff($pdo, $bearer);
        tm_json(
            $auth['ok'] ? ['ok' => true, 'user' => $auth['user']] : ['message' => $auth['message']],
            $auth['ok'] ? 200 : (($auth['message'] ?? '') === 'Staff access required' ? 403 : 401),
        );
        return true;
    }

    $auth = tm_auth_require_staff($pdo, $bearer);
    if (!$auth['ok']) {
        $code = ($auth['message'] ?? '') === 'Staff access required' ? 403 : 401;
        tm_json(['message' => $auth['message']], $code);
        return true;
    }
    $staffRole = (string) ($auth['user']['role'] ?? 'staff');

    if ($segments === ['v1', 'admin', 'dashboard'] && $method === 'GET') {
        tm_json(['ok' => true, 'stats' => tm_admin_dashboard_stats($pdo)]);
        return true;
    }

    if ($segments === ['v1', 'admin', 'upload'] && $method === 'POST') {
        $file = $_FILES['file'] ?? $_FILES['image'] ?? null;
        if (!is_array($file)) {
            tm_json(['ok' => false, 'message' => 'Missing file field'], 400);
            return true;
        }
        $result = tm_upload_save_image(
            $file,
            (string) ($_POST['root'] ?? 'products'),
            (string) ($_POST['folder'] ?? $_POST['category'] ?? 'misc'),
        );
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }

    /* Categories */
    if ($segments === ['v1', 'admin', 'categories'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_admin_categories_list($pdo)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'categories'] && $method === 'POST') {
        $result = tm_admin_category_create($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'categories') {
        $catId = (int) $segments[3];
        if ($method === 'GET') {
            $cat = tm_admin_category_by_id($pdo, $catId);
            tm_json($cat ? ['ok' => true, 'category' => $cat] : ['message' => 'Not found'], $cat ? 200 : 404);
            return true;
        }
        if ($method === 'PATCH') {
            $result = tm_admin_category_update($pdo, $catId, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
        if ($method === 'DELETE') {
            $result = tm_admin_category_delete($pdo, $catId);
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
    }

    /* Bulk import / export */
    if ($segments === ['v1', 'admin', 'bulk', 'template'] && $method === 'GET') {
        $path = tm_admin_bulk_template_path();
        if (!is_readable($path)) {
            tm_json(['message' => 'Template not found'], 404);
            return true;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products_bulk_import_TEMPLATE.csv"');
        readfile($path);
        exit;
    }
    if ($segments === ['v1', 'admin', 'bulk', 'export'] && $method === 'GET') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');
        echo tm_admin_bulk_export_csv($pdo);
        exit;
    }
    if ($segments === ['v1', 'admin', 'bulk', 'import'] && $method === 'POST') {
        $csv = $_FILES['csv'] ?? $_FILES['file'] ?? null;
        if (!is_array($csv)) {
            tm_json(['ok' => false, 'message' => 'Upload a CSV file (field name: csv).'], 400);
            return true;
        }
        $uploadErr = (int) ($csv['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $uploadMessages = [
                UPLOAD_ERR_INI_SIZE => 'CSV exceeds server upload_max_filesize in PHP settings.',
                UPLOAD_ERR_FORM_SIZE => 'CSV exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL => 'CSV upload was interrupted — try again.',
                UPLOAD_ERR_NO_FILE => 'No CSV file received.',
            ];
            tm_json([
                'ok' => false,
                'message' => $uploadMessages[$uploadErr] ?? ('CSV upload failed (error code ' . $uploadErr . ').'),
            ], 400);
            return true;
        }
        $tmp = (string) ($csv['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            tm_json(['ok' => false, 'message' => 'Invalid CSV upload.'], 400);
            return true;
        }
        $zipPath = null;
        if (isset($_FILES['images_zip']) && is_array($_FILES['images_zip'])) {
            $zipErr = (int) ($_FILES['images_zip']['error'] ?? UPLOAD_ERR_NO_FILE);
            $z = (string) ($_FILES['images_zip']['tmp_name'] ?? '');
            if ($zipErr === UPLOAD_ERR_OK && $z !== '' && is_uploaded_file($z)) {
                $zipPath = $z;
            } elseif ($zipErr !== UPLOAD_ERR_NO_FILE && $zipErr !== UPLOAD_ERR_OK) {
                tm_json(['ok' => false, 'message' => 'Images ZIP upload failed.'], 400);
                return true;
            }
        }
        $dryRun = isset($_POST['dryRun']) && ($_POST['dryRun'] === '1' || $_POST['dryRun'] === 'true');
        try {
            $result = tm_admin_bulk_import($pdo, $tmp, $zipPath, $dryRun);
        } catch (Throwable $e) {
            tm_json(['ok' => false, 'message' => $e->getMessage()], 500);
            return true;
        }
        tm_json($result, $result['ok'] ? 200 : 422);
        return true;
    }

    /* Products */
    if ($segments === ['v1', 'admin', 'products'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_admin_products_list($pdo, $_GET)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'products'] && $method === 'POST') {
        $result = tm_admin_product_create($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if ($segments === ['v1', 'admin', 'products', 'bulk'] && $method === 'PATCH') {
        $result = tm_admin_products_bulk($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 200 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'products') {
        $id = $segments[3];
        if ($method === 'GET') {
            $product = tm_admin_product_by_id($pdo, $id);
            tm_json($product ? ['ok' => true, 'product' => $product] : ['message' => 'Not found'], $product ? 200 : 404);
            return true;
        }
        if ($method === 'PATCH') {
            $result = tm_admin_product_update($pdo, $id, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
        if ($method === 'DELETE') {
            $result = tm_admin_product_delete($pdo, $id);
            tm_json($result, $result['ok'] ? 200 : 404);
            return true;
        }
    }

    if ($segments === ['v1', 'admin', 'home-page'] && $method === 'GET') {
        tm_json(['ok' => true, 'homePage' => tm_home_page_load()]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'home-page'] && $method === 'PATCH') {
        $result = tm_admin_home_page_save(tm_read_json_body());
        tm_json($result, $result['ok'] ? 200 : 400);
        return true;
    }

    if ($segments === ['v1', 'admin', 'free-gifts'] && $method === 'GET') {
        tm_json(['ok' => true, 'freeGifts' => tm_free_gifts_load()]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'free-gifts'] && $method === 'PATCH') {
        $result = tm_admin_free_gifts_save(tm_read_json_body());
        tm_json($result, $result['ok'] ? 200 : 400);
        return true;
    }

    if ($segments === ['v1', 'admin', 'feature-collections'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_feature_collections_load()]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'feature-collections'] && $method === 'POST') {
        $result = tm_admin_feature_collection_create(tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'feature-collections') {
        $collectionId = (string) $segments[3];
        if ($method === 'PATCH') {
            $result = tm_admin_feature_collection_update($collectionId, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
        if ($method === 'DELETE') {
            $result = tm_admin_feature_collection_delete($collectionId);
            tm_json($result, $result['ok'] ? 200 : 404);
            return true;
        }
    }

    if ($segments === ['v1', 'admin', 'subcategories'] && $method === 'GET') {
        $cat = isset($_GET['category']) ? (string) $_GET['category'] : null;
        tm_json(['ok' => true, 'items' => tm_subcategories_list($pdo, $cat ?: null)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'subcategories'] && $method === 'POST') {
        $result = tm_admin_subcategory_create($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'subcategories') {
        $subId = (int) $segments[3];
        if ($method === 'PATCH') {
            $result = tm_admin_subcategory_update($pdo, $subId, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
        if ($method === 'DELETE') {
            $result = tm_admin_subcategory_delete($pdo, $subId);
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
    }

    if ($segments === ['v1', 'admin', 'personalisation'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_admin_personalisations_list($pdo, $_GET)]);
        return true;
    }

    if ($segments === ['v1', 'admin', 'orders'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_admin_orders_list($pdo, $_GET)]);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'orders') {
        $orderId = (int) $segments[3];
        if ($method === 'GET') {
            $order = tm_admin_order_detail($pdo, $orderId);
            tm_json($order ? ['ok' => true, 'order' => $order] : ['message' => 'Not found'], $order ? 200 : 404);
            return true;
        }
        if ($method === 'PATCH') {
            $result = tm_admin_order_update($pdo, $orderId, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
    }

    if ($segments === ['v1', 'admin', 'customers'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_admin_customers_list($pdo, $_GET)]);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'customers' && $method === 'GET') {
        $customer = tm_admin_customer_detail($pdo, (int) $segments[3]);
        tm_json($customer ? ['ok' => true, 'customer' => $customer] : ['message' => 'Not found'], $customer ? 200 : 404);
        return true;
    }
    if ($segments === ['v1', 'admin', 'newsletter'] && $method === 'GET') {
        tm_json(['ok' => true, ...tm_admin_newsletter_list($pdo, $_GET)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'newsletter', 'export'] && $method === 'GET') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
        echo tm_admin_newsletter_export_csv($pdo);
        exit;
    }

    /* Coupons (admin/manager only) */
    if ($segments === ['v1', 'admin', 'coupons'] && $method === 'GET') {
        if (!tm_auth_staff_can($staffRole, 'coupons')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(['ok' => true, ...tm_admin_coupons_list($pdo)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'coupons'] && $method === 'POST') {
        if (!tm_auth_staff_can($staffRole, 'coupons')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $result = tm_admin_coupon_create($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'coupons') {
        if (!tm_auth_staff_can($staffRole, 'coupons')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $couponId = (int) $segments[3];
        if ($method === 'PATCH') {
            $result = tm_admin_coupon_update($pdo, $couponId, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
        if ($method === 'DELETE') {
            $result = tm_admin_coupon_delete($pdo, $couponId);
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
    }

    /* Staff management (admin only) */
    if ($segments === ['v1', 'admin', 'staff'] && $method === 'GET') {
        if (!tm_auth_staff_can($staffRole, 'staff')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(['ok' => true, ...tm_admin_staff_list($pdo)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'staff'] && $method === 'POST') {
        if (!tm_auth_staff_can($staffRole, 'staff')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $result = tm_admin_staff_create($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'staff' && $method === 'PATCH') {
        if (!tm_auth_staff_can($staffRole, 'staff')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $result = tm_admin_staff_update($pdo, (int) $segments[3], tm_read_json_body());
        tm_json($result, $result['ok'] ? 200 : 400);
        return true;
    }

    if ($segments === ['v1', 'admin', 'analytics', 'revenue'] && $method === 'GET') {
        $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        tm_json(['ok' => true, 'items' => tm_admin_revenue_by_day($pdo, $days)]);
        return true;
    }

    /* Blog */
    if ($segments === ['v1', 'admin', 'blog'] && $method === 'GET') {
        if (!tm_auth_staff_can($staffRole, 'blog')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(['ok' => true, ...tm_admin_blog_list($pdo, $_GET)]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'blog'] && $method === 'POST') {
        if (!tm_auth_staff_can($staffRole, 'blog')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $result = tm_admin_blog_create($pdo, tm_read_json_body());
        tm_json($result, $result['ok'] ? 201 : 400);
        return true;
    }
    if (count($segments) === 4 && $segments[2] === 'blog') {
        if (!tm_auth_staff_can($staffRole, 'blog')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $postId = (int) $segments[3];
        if ($method === 'GET') {
            $post = tm_admin_blog_by_id($pdo, $postId);
            tm_json($post ? ['ok' => true, 'post' => $post] : ['message' => 'Not found'], $post ? 200 : 404);
            return true;
        }
        if ($method === 'PATCH') {
            $result = tm_admin_blog_update($pdo, $postId, tm_read_json_body());
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
        if ($method === 'DELETE') {
            $result = tm_admin_blog_delete($pdo, $postId);
            tm_json($result, $result['ok'] ? 200 : 400);
            return true;
        }
    }

    /* WebP converter (admin/manager) */
    if ($segments === ['v1', 'admin', 'converter'] && $method === 'GET') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(tm_converter_status());
        return true;
    }
    if ($segments === ['v1', 'admin', 'converter', 'settings'] && $method === 'GET') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(['ok' => true, 'settings' => tm_converter_load_settings(), 'webpAvailable' => tm_image_webp_available()]);
        return true;
    }
    if ($segments === ['v1', 'admin', 'converter', 'settings'] && $method === 'PATCH') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $result = tm_converter_save_settings(tm_read_json_body());
        tm_json($result, $result['ok'] ? 200 : 400);
        return true;
    }
    if ($segments === ['v1', 'admin', 'converter', 'scan'] && $method === 'POST') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(tm_converter_scan());
        return true;
    }
    if ($segments === ['v1', 'admin', 'converter', 'run'] && $method === 'POST') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        $body = tm_read_json_body();
        tm_json(tm_converter_run_batch($pdo, $body));
        return true;
    }
    if ($segments === ['v1', 'admin', 'converter', 'upload'] && $method === 'POST') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_json(tm_converter_upload_to_zip(), 201);
        return true;
    }
    if (count($segments) === 5 && $segments[2] === 'converter' && $segments[3] === 'download' && $method === 'GET') {
        if (!tm_auth_staff_can($staffRole, 'converter')) {
            tm_json(['message' => 'Permission denied'], 403);
            return true;
        }
        tm_converter_serve_zip_download((string) $segments[4]);
        return true;
    }

    tm_json(['message' => 'Admin route not found'], 404);
    return true;
}
