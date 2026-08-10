<?php

declare(strict_types=1);

/**
 * Create or promote an admin user.
 *
 * Usage: php backend/api/tools/create_admin.php admin@example.com "YourSecurePassword" "Admin Name"
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';
$fullName = $argv[3] ?? 'Admin';

if ($email === '' || $password === '') {
    fwrite(STDERR, "Usage: php create_admin.php <email> <password> [full name]\n");
    exit(1);
}

require dirname(__DIR__) . '/bootstrap.php';

$pdo = tm_db();
$email = tm_auth_normalize_email($email);

$migration = dirname(__DIR__, 2) . '/sql/migration_admin_portal.sql';
if (is_readable($migration)) {
    $sql = (string) file_get_contents($migration);
    foreach (preg_split('/;\s*\n/', $sql) ?: [] as $chunk) {
        $stmt = trim($chunk);
        if ($stmt === '' || str_starts_with($stmt, '--') || str_starts_with($stmt, 'SET @')) {
            continue;
        }
        if (str_starts_with($stmt, 'PREPARE') || str_starts_with($stmt, 'EXECUTE') || str_starts_with($stmt, 'DEALLOCATE')) {
            try {
                $pdo->exec($stmt);
            } catch (Throwable) {
                /* migration uses dynamic SQL */
            }
            continue;
        }
        if (str_starts_with($stmt, 'UPDATE orders')) {
            try {
                $pdo->exec($stmt);
            } catch (Throwable) {
            }
        }
    }
}

$pwErr = tm_auth_password_errors($password);
if ($pwErr !== null) {
    fwrite(STDERR, $pwErr . "\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if ($row !== false) {
    $id = (int) $row['id'];
    $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role = ? WHERE id = ?')
        ->execute([$hash, $fullName, 'admin', $id]);
    echo "Updated existing user #{$id} ({$email}) to admin.\n";
    exit(0);
}

$pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)')
    ->execute([$email, $hash, $fullName, 'admin']);
echo "Created admin user: {$email}\n";
echo "Sign in at /admin/login\n";
