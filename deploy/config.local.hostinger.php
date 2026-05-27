<?php

/**
 * Hostinger production — upload as public_html/api/config.local.php
 * Replace YOUR_DATABASE_PASSWORD and your real domain below.
 * Do not commit this file if it contains your real password.
 */
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u230300439_minimark',
        'user' => 'u230300439_minimark',
        'pass' => 'YOUR_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'api_base_path' => '/api',
    'cors_origin' => 'https://theminimark.com',
    'auth' => [
        'session_ttl_days' => 30,
        'reset_ttl_hours' => 1,
        'expose_reset_token' => false,
    ],
    /**
     * Copy key_id + key_secret from Razorpay Dashboard → API Keys (Test mode).
     * Use deploy/hostinger-config.local.php (gitignored) for the full file with secrets.
     */
    'razorpay' => [
        'enabled' => true,
        'key_id' => 'rzp_test_Su5MCl7M85ZfZ8',
        'key_secret' => '', // REQUIRED — paste Key Secret here on the server
        'company_name' => 'The Minimark',
    ],
];
