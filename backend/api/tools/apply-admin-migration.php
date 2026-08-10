<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require dirname(__DIR__) . '/bootstrap.php';

$pdo = tm_db();
$path = dirname(__DIR__, 2) . '/sql/migration_admin_portal.sql';
if (!is_readable($path)) {
    fwrite(STDERR, "Missing migration file.\n");
    exit(1);
}

$sql = (string) file_get_contents($path);
try {
    $pdo->exec($sql);
    echo "Admin migration applied (or already present).\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Migration error: ' . $e->getMessage() . "\n");
    exit(1);
}
