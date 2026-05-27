<?php

/**
 * Copy to config.local.php and fill in your Hostinger MySQL credentials.
 * Do not commit config.local.php.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        /**
         * Local MySQL/MariaDB: usually **3306** and `pass` **''** (or your real password).
         * Repo Docker DB only (`docker compose up -d db`): use **3307** and `pass` **root** (host maps container 3306 → 3307).
         */
        'port' => 3306,
        'name' => 'theminimark',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    /**
     * Strip this URL prefix from the request path before routing.
     * Use '' with `php -S` + Vite proxy (see backend/README.md).
     * Use '/api' when this folder is deployed at https://yourdomain.com/api/
     */
    'api_base_path' => '',
    /** CORS: use your Vue dev origin or production domain. * is fine for read-only public APIs. */
    'cors_origin' => '*',
    /**
     * Auth (optional keys use defaults).
     * expose_reset_token: when true, POST /v1/auth/forgot-password includes debugResetToken in JSON for local testing only.
     */
    'auth' => [
        'session_ttl_days' => 30,
        'reset_ttl_hours' => 1,
        'expose_reset_token' => false,
    ],
    /**
     * Razorpay (India) — https://dashboard.razorpay.com/
     * Test keys: Dashboard → Test mode. Live keys: activate account, then Live mode.
     * Run backend/sql/migration_payment_razorpay.sql on existing databases.
     */
    'razorpay' => [
        'enabled' => false,
        'key_id' => '',
        'key_secret' => '',
        'company_name' => 'The Minimark',
    ],
];
