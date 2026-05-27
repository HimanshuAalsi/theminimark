<?php

/**
 * Router for PHP's built-in server (`php -S … router.php`).
 * Sends non-file requests to index.php so paths like /v1/products work.
 */
declare(strict_types=1);

$uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$file = __DIR__ . $uri;

if ($uri !== '/' && $uri !== '' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
