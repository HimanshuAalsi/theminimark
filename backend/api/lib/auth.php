<?php

declare(strict_types=1);

/**
 * Email + password auth with opaque bearer tokens (SHA-256 hashed at rest).
 */

function tm_auth_config(): array
{
    $c = tm_config();
    $a = is_array($c['auth'] ?? null) ? $c['auth'] : [];
    return [
        'session_ttl_days' => max(1, (int) ($a['session_ttl_days'] ?? 30)),
        'reset_ttl_hours' => max(1, (int) ($a['reset_ttl_hours'] ?? 1)),
        'expose_reset_token' => (bool) ($a['expose_reset_token'] ?? false),
    ];
}

function tm_auth_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function tm_auth_token_hash(string $raw): string
{
    return hash('sha256', $raw);
}

function tm_auth_pdo_is_duplicate_key(PDOException $e): bool
{
    if ((string) $e->getCode() === '23000') {
        return true;
    }
    $mysql = (int) ($e->errorInfo[1] ?? 0);
    return $mysql === 1062;
}

/** User-facing hint when auth tables were never migrated. */
function tm_auth_pdo_schema_hint(PDOException $e): ?string
{
    $state = (string) ($e->errorInfo[0] ?? '');
    $msg = $e->getMessage();
    if (
        $state === '42S02'
        || str_contains($msg, 'Base table or view not found')
        || str_contains($msg, "doesn't exist")
        || str_contains($msg, "does not exist")
        || str_contains($msg, 'Unknown table')
    ) {
        return 'ACCOUNT_TABLES_MISSING';
    }
    return null;
}

/** Which auth tables exist in the database the API is connected to. */
function tm_auth_install_status(PDO $pdo): array
{
    $cfgName = (string) (tm_config()['db']['name'] ?? '');
    $live = '';
    try {
        $q = $pdo->query('SELECT DATABASE()');
        if ($q !== false) {
            $live = (string) ($q->fetchColumn() ?: '');
        }
    } catch (Throwable) {
    }

    $tables = ['users', 'user_sessions', 'password_reset_tokens'];
    $present = [];
    foreach ($tables as $t) {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $st->execute([$t]);
        $present[$t] = (int) $st->fetchColumn() > 0;
    }

    return [
        'configDbName' => $cfgName,
        'connectedDatabase' => $live,
        'tables' => $present,
        'authTablesReady' => !in_array(false, $present, true),
    ];
}

/**
 * Apply migration_auth_tables.sql using the same DB connection as the API.
 *
 * @return array{ok: bool, message?: string, applied?: list<string>}
 */
function tm_auth_apply_migration_file(PDO $pdo): array
{
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'migration_auth_tables.sql';
    if (!is_readable($path)) {
        return ['ok' => false, 'message' => 'Migration file not found at: ' . $path];
    }
    $raw = (string) file_get_contents($path);
    $norm = str_replace("\r\n", "\n", $raw);
    $lines = explode("\n", $norm);
    $buf = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*--/', $line)) {
            continue;
        }
        $buf[] = $line;
    }
    $norm = trim(implode("\n", $buf));
    $chunks = preg_split('/;\s*\n/', $norm) ?: [];
    $applied = [];
    try {
        foreach ($chunks as $chunk) {
            $stmt = rtrim(trim($chunk), ';');
            if ($stmt === '') {
                continue;
            }
            $pdo->exec($stmt . ';');
            $applied[] = strlen($stmt) > 80 ? substr($stmt, 0, 77) . '…' : $stmt;
        }
    } catch (PDOException $e) {
        return [
            'ok' => false,
            'message' => 'Migration failed: ' . $e->getMessage(),
            'applied' => $applied,
        ];
    }

    return ['ok' => true, 'applied' => $applied];
}

function tm_auth_missing_tables_message(PDO $pdo): string
{
    $st = tm_auth_install_status($pdo);
    $cfg = $st['configDbName'] !== '' ? $st['configDbName'] : '(not set in config)';
    $live = $st['connectedDatabase'] !== '' ? $st['connectedDatabase'] : '(none — check db.name in config.local.php)';
    $t = $st['tables'];
    $parts = [];
    foreach ($t as $name => $ok) {
        $parts[] = $name . '=' . ($ok ? 'yes' : 'no');
    }
    $tbl = implode(', ', $parts);

    return 'Account tables are missing on the database this PHP API is using. '
        . 'Config db.name is "' . $cfg . '"; MySQL DATABASE() is "' . $live . '". '
        . 'Tables: ' . $tbl . '. '
        . 'Fix: from the repo root run `php backend/api/tools/apply-auth-migration.php` (uses the same config as the API), '
        . 'or import backend/sql/migration_auth_tables.sql in phpMyAdmin into that exact database (not a different server/schema).';
}

function tm_auth_password_errors(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (strlen($password) > 200) {
        return 'Password is too long.';
    }
    return null;
}

function tm_auth_bearer_token(): ?string
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($h === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $h = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (!is_string($h) || !preg_match('/Bearer\s+(\S+)/i', $h, $m)) {
        return null;
    }
    return $m[1];
}

/** @return array{id: int, email: string, full_name: string}|null */
function tm_auth_user_by_id(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT id, email, full_name FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function tm_auth_user_public(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'email' => (string) $row['email'],
        'fullName' => (string) $row['full_name'],
    ];
}

/** @return array{ok: bool, message?: string, user?: array, token?: string} */
function tm_auth_register(PDO $pdo, string $email, string $password, string $fullName): array
{
    $email = tm_auth_normalize_email($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Valid email is required.'];
    }
    $pwErr = tm_auth_password_errors($password);
    if ($pwErr !== null) {
        return ['ok' => false, 'message' => $pwErr];
    }
    $fullName = trim($fullName);
    if (strlen($fullName) > 255) {
        return ['ok' => false, 'message' => 'Name is too long.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $raw = bin2hex(random_bytes(32));
    $th = tm_auth_token_hash($raw);
    $days = tm_auth_config()['session_ttl_days'];
    $exp = (new DateTimeImmutable('now'))->modify("+{$days} days")->format('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)');
        $st->execute([$email, $hash, $fullName]);
        $id = (int) $pdo->lastInsertId();
        if ($id <= 0) {
            throw new RuntimeException('User insert did not return an id.');
        }
        $st = $pdo->prepare('INSERT INTO user_sessions (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $st->execute([$id, $th, $exp]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $hint = tm_auth_pdo_schema_hint($e);
        if ($hint !== null) {
            $msg = $hint === 'ACCOUNT_TABLES_MISSING' ? tm_auth_missing_tables_message($pdo) : $hint;
            return ['ok' => false, 'message' => $msg];
        }
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'An account with this email already exists.'];
        }
        throw $e;
    }

    $user = tm_auth_user_by_id($pdo, $id);
    if ($user === null) {
        return ['ok' => false, 'message' => 'Registration failed.'];
    }
    return ['ok' => true, 'user' => tm_auth_user_public($user), 'token' => $raw];
}

/** @return array{ok: bool, message?: string, user?: array, token?: string} */
function tm_auth_login(PDO $pdo, string $email, string $password): array
{
    $email = tm_auth_normalize_email($email);
    $st = $pdo->prepare('SELECT id, email, full_name, password_hash FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $hash = null;
    if ($row !== false) {
        $hash = (string) $row['password_hash'];
    }
    if ($hash === null || !password_verify($password, $hash)) {
        return ['ok' => false, 'message' => 'Invalid email or password.'];
    }
    $id = (int) $row['id'];
    $user = ['id' => $id, 'email' => (string) $row['email'], 'full_name' => (string) $row['full_name']];
    try {
        $token = tm_auth_issue_session($pdo, $id);
    } catch (PDOException $e) {
        $hint = tm_auth_pdo_schema_hint($e);
        if ($hint !== null) {
            $msg = $hint === 'ACCOUNT_TABLES_MISSING' ? tm_auth_missing_tables_message($pdo) : $hint;
            return ['ok' => false, 'message' => $msg];
        }
        throw $e;
    }
    return ['ok' => true, 'user' => tm_auth_user_public($user), 'token' => $token];
}

function tm_auth_issue_session(PDO $pdo, int $userId): string
{
    $raw = bin2hex(random_bytes(32));
    $th = tm_auth_token_hash($raw);
    $days = tm_auth_config()['session_ttl_days'];
    $exp = (new DateTimeImmutable('now'))->modify("+{$days} days")->format('Y-m-d H:i:s');
    $st = $pdo->prepare('INSERT INTO user_sessions (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
    $st->execute([$userId, $th, $exp]);
    return $raw;
}

/** @return int|null user id */
function tm_auth_session_user_id(PDO $pdo, string $rawToken): ?int
{
    if ($rawToken === '' || strlen($rawToken) < 32) {
        return null;
    }
    $th = tm_auth_token_hash($rawToken);
    $st = $pdo->prepare(
        'SELECT user_id FROM user_sessions WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
    );
    $st->execute([$th]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    return (int) $row['user_id'];
}

function tm_auth_logout(PDO $pdo, string $rawToken): void
{
    if ($rawToken === '') {
        return;
    }
    $th = tm_auth_token_hash($rawToken);
    $st = $pdo->prepare('DELETE FROM user_sessions WHERE token_hash = ?');
    $st->execute([$th]);
}

function tm_auth_logout_all(PDO $pdo, int $userId): void
{
    $st = $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ?');
    $st->execute([$userId]);
}

/** @return array{ok: bool, message?: string, user?: array} */
function tm_auth_me(PDO $pdo, ?string $rawToken): array
{
    if ($rawToken === null || $rawToken === '') {
        return ['ok' => false, 'message' => 'Not authenticated.'];
    }
    $uid = tm_auth_session_user_id($pdo, $rawToken);
    if ($uid === null) {
        return ['ok' => false, 'message' => 'Session expired or invalid.'];
    }
    $user = tm_auth_user_by_id($pdo, $uid);
    if ($user === null) {
        return ['ok' => false, 'message' => 'User not found.'];
    }
    return ['ok' => true, 'user' => tm_auth_user_public($user)];
}

/** @return array{ok: bool, message?: string, user?: array} */
function tm_auth_update_profile(PDO $pdo, int $userId, array $body): array
{
    $touchName = array_key_exists('fullName', $body);
    $fullNameVal = $touchName ? trim((string) $body['fullName']) : '';
    $hasEmailField = array_key_exists('email', $body);
    $emailTrim = $hasEmailField ? trim((string) $body['email']) : '';
    $changeEmail = $hasEmailField && $emailTrim !== '';
    $newEmail = $changeEmail ? tm_auth_normalize_email($emailTrim) : '';
    $currentPassword = isset($body['currentPassword']) ? (string) $body['currentPassword'] : '';

    if (!$touchName && !$hasEmailField) {
        return ['ok' => false, 'message' => 'Nothing to update.'];
    }
    if ($hasEmailField && $emailTrim === '' && !$touchName) {
        return ['ok' => false, 'message' => 'Add your name, a new email, or both.'];
    }

    if ($touchName && strlen($fullNameVal) > 255) {
        return ['ok' => false, 'message' => 'Name is too long.'];
    }

    if ($changeEmail) {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Valid email is required.'];
        }
        if ($currentPassword === '') {
            return ['ok' => false, 'message' => 'Current password is required to change email.'];
        }
        $st = $pdo->prepare('SELECT password_hash, email FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !password_verify($currentPassword, (string) $row['password_hash'])) {
            return ['ok' => false, 'message' => 'Current password is incorrect.'];
        }
        $curEm = tm_auth_normalize_email((string) $row['email']);
        if ($curEm !== $newEmail) {
            try {
                $st = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
                $st->execute([$newEmail, $userId]);
            } catch (PDOException $e) {
                if (tm_auth_pdo_is_duplicate_key($e)) {
                    return ['ok' => false, 'message' => 'That email is already in use.'];
                }
                throw $e;
            }
        }
    }

    if ($touchName) {
        $st = $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?');
        $st->execute([$fullNameVal, $userId]);
    }

    $user = tm_auth_user_by_id($pdo, $userId);
    return ['ok' => true, 'user' => $user !== null ? tm_auth_user_public($user) : []];
}

/** @return array{ok: bool, message?: string} */
function tm_auth_change_password(PDO $pdo, int $userId, string $current, string $new): array
{
    $pwErr = tm_auth_password_errors($new);
    if ($pwErr !== null) {
        return ['ok' => false, 'message' => $pwErr];
    }
    $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false || !password_verify($current, (string) $row['password_hash'])) {
        return ['ok' => false, 'message' => 'Current password is incorrect.'];
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $st = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $st->execute([$hash, $userId]);
    tm_auth_logout_all($pdo, $userId);
    return ['ok' => true];
}

/** @return array{ok: bool, message: string, debugResetToken?: string} */
function tm_auth_forgot_password(PDO $pdo, string $email): array
{
    $email = tm_auth_normalize_email($email);
    $msg = 'If an account exists for that email, password reset instructions have been recorded.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => true, 'message' => $msg];
    }

    $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return ['ok' => true, 'message' => $msg];
    }
    $userId = (int) $row['id'];
    $raw = bin2hex(random_bytes(32));
    $th = tm_auth_token_hash($raw);
    $hours = tm_auth_config()['reset_ttl_hours'];
    $exp = (new DateTimeImmutable('now'))->modify("+{$hours} hours")->format('Y-m-d H:i:s');

    $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
    $st = $pdo->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
    );
    $st->execute([$userId, $th, $exp]);

    $out = ['ok' => true, 'message' => $msg];
    if (tm_auth_config()['expose_reset_token']) {
        $out['debugResetToken'] = $raw;
    }
    return $out;
}

/** @return array{ok: bool, message?: string} */
function tm_auth_reset_password(PDO $pdo, string $rawToken, string $newPassword): array
{
    if ($rawToken === '' || strlen($rawToken) < 32) {
        return ['ok' => false, 'message' => 'Invalid or expired reset link.'];
    }
    $pwErr = tm_auth_password_errors($newPassword);
    if ($pwErr !== null) {
        return ['ok' => false, 'message' => $pwErr];
    }
    $th = tm_auth_token_hash($rawToken);
    $st = $pdo->prepare(
        'SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
    );
    $st->execute([$th]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return ['ok' => false, 'message' => 'Invalid or expired reset link.'];
    }
    $tokenId = (int) $row['id'];
    $userId = (int) $row['user_id'];
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([$tokenId]);
        tm_auth_logout_all($pdo, $userId);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return ['ok' => true];
}

/** @return array{ok: bool, message?: string}|array{ok: true, userId: int} */
function tm_auth_require_user(PDO $pdo, ?string $rawToken): array
{
    if ($rawToken === null || $rawToken === '') {
        return ['ok' => false, 'message' => 'Authentication required.'];
    }
    $uid = tm_auth_session_user_id($pdo, $rawToken);
    if ($uid === null) {
        return ['ok' => false, 'message' => 'Session expired or invalid.'];
    }
    return ['ok' => true, 'userId' => $uid];
}
