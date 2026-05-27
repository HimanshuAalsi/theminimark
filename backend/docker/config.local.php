<?php

/**
 * Local Docker only — matches docker-compose.yml (do not use in production).
 */
return [
    'db' => [
        'host' => 'db',
        'port' => 3306,
        'name' => 'theminimark',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8mb4',
    ],
    'api_base_path' => '',
    'cors_origin' => '*',
    'auth' => [
        'expose_reset_token' => true,
    ],
];
