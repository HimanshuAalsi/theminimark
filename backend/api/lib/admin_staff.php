<?php

declare(strict_types=1);

/** @return list<string> */
function tm_auth_staff_roles(): array
{
    return ['admin', 'manager', 'staff'];
}

function tm_auth_is_staff(string $role): bool
{
    return in_array(strtolower($role), tm_auth_staff_roles(), true);
}

/** @return array{ok: bool, message?: string, userId?: int, user?: array} */
function tm_auth_require_staff(PDO $pdo, ?string $rawToken): array
{
    $auth = tm_auth_require_user($pdo, $rawToken);
    if (!$auth['ok']) {
        return $auth;
    }
    $user = tm_auth_user_by_id($pdo, (int) $auth['userId']);
    if ($user === null) {
        return ['ok' => false, 'message' => 'User not found'];
    }
    $role = (string) ($user['role'] ?? 'customer');
    if (!tm_auth_is_staff($role)) {
        return ['ok' => false, 'message' => 'Staff access required'];
    }

    return [
        'ok' => true,
        'userId' => (int) $auth['userId'],
        'user' => tm_auth_user_public($user),
    ];
}

/** Permissions: admin=all, manager=all except staff, staff=limited */
function tm_auth_staff_can(string $role, string $permission): bool
{
    $role = strtolower($role);
    if ($role === 'admin') {
        return true;
    }
    if ($role === 'manager') {
        return $permission !== 'staff';
    }
    if ($role === 'staff') {
        return in_array($permission, [
            'dashboard', 'orders', 'products', 'inventory', 'categories',
            'customers', 'newsletter', 'personalisation', 'analytics',
        ], true);
    }

    return false;
}

/** @return array{ok: bool, message?: string, userId?: int, user?: array} */
function tm_auth_require_permission(PDO $pdo, ?string $rawToken, string $permission): array
{
    $auth = tm_auth_require_staff($pdo, $rawToken);
    if (!$auth['ok']) {
        return $auth;
    }
    $role = (string) ($auth['user']['role'] ?? 'customer');
    if (!tm_auth_staff_can($role, $permission)) {
        return ['ok' => false, 'message' => 'Permission denied'];
    }

    return $auth;
}

/**
 * @return array{items: list<array<string, mixed>>}
 */
function tm_admin_staff_list(PDO $pdo): array
{
    $roles = tm_auth_staff_roles();
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $st = $pdo->prepare(
        "SELECT id, email, full_name, role, created_at FROM users WHERE role IN ({$placeholders}) ORDER BY created_at DESC"
    );
    $st->execute($roles);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'fullName' => (string) $row['full_name'],
            'role' => (string) $row['role'],
            'createdAt' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return ['items' => $items];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, user?: array}
 */
function tm_admin_staff_create(PDO $pdo, array $body): array
{
    $email = tm_auth_normalize_email((string) ($body['email'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    $fullName = trim((string) ($body['fullName'] ?? ''));
    $role = strtolower(trim((string) ($body['role'] ?? 'staff')));

    if (!in_array($role, tm_auth_staff_roles(), true)) {
        return ['ok' => false, 'message' => 'Invalid role'];
    }
    $pwErr = tm_auth_password_errors($password);
    if ($pwErr !== null) {
        return ['ok' => false, 'message' => $pwErr];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Valid email required'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $st = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
        $st->execute([$email, $hash, substr($fullName, 0, 255) ?: 'Staff', $role]);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Email already registered'];
        }
        throw $e;
    }

    $id = (int) $pdo->lastInsertId();
    $user = tm_auth_user_by_id($pdo, $id);

    return ['ok' => true, 'user' => $user ? tm_auth_user_public($user) : null];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string}
 */
function tm_admin_staff_update(PDO $pdo, int $userId, array $body): array
{
    $st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $st->execute([$userId]);
    $existingRole = $st->fetchColumn();
    if ($existingRole === false) {
        return ['ok' => false, 'message' => 'User not found'];
    }
    if (!tm_auth_is_staff((string) $existingRole)) {
        return ['ok' => false, 'message' => 'Not a staff account'];
    }

    $sets = [];
    $params = [];
    if (isset($body['role']) && in_array(strtolower((string) $body['role']), tm_auth_staff_roles(), true)) {
        $sets[] = 'role = ?';
        $params[] = strtolower((string) $body['role']);
    }
    if (isset($body['fullName'])) {
        $sets[] = 'full_name = ?';
        $params[] = substr(trim((string) $body['fullName']), 0, 255);
    }
    if (!empty($body['password'])) {
        $pwErr = tm_auth_password_errors((string) $body['password']);
        if ($pwErr !== null) {
            return ['ok' => false, 'message' => $pwErr];
        }
        $sets[] = 'password_hash = ?';
        $params[] = password_hash((string) $body['password'], PASSWORD_DEFAULT);
    }

    if ($sets === []) {
        return ['ok' => false, 'message' => 'Nothing to update'];
    }

    $params[] = $userId;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    return ['ok' => true];
}
