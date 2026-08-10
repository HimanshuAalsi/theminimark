<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require dirname(__DIR__) . '/bootstrap.php';
$path = dirname(__DIR__, 2) . '/sql/migration_admin_advanced.sql';
if (!is_readable($path)) {
    fwrite(STDERR, "Missing migration file.\n");
    exit(1);
}
try {
    tm_db()->exec((string) file_get_contents($path));
    echo "Advanced admin migration applied.\n";
} catch (PDOException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
