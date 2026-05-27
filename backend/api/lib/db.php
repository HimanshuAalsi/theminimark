<?php

declare(strict_types=1);

function tm_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = tm_config()['db'];
    $host = (string) $c['host'];
    $port = (int) ($c['port'] ?? 3306);
    $name = (string) $c['name'];
    $user = (string) $c['user'];
    $pass = (string) ($c['pass'] ?? '');
    $charset = (string) ($c['charset'] ?? 'utf8mb4');

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
