<?php

/**
 * Creates users / user_sessions / password_reset_tokens using the same MySQL
 * settings as api/config.local.php (or config.php).
 *
 * From the repository root:
 *   php backend/api/tools/apply-auth-migration.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/bootstrap.php';

$pdo = tm_db();
$result = tm_auth_apply_migration_file($pdo);

if (PHP_SAPI === 'cli') {
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    if (!($result['ok'] ?? false)) {
        exit(1);
    }
    $check = tm_auth_install_status($pdo);
    fwrite(STDOUT, PHP_EOL . 'Verify: ' . json_encode($check, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
