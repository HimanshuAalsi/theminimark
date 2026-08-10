<?php

declare(strict_types=1);

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_admin_newsletter_list(PDO $pdo, array $query): array
{
    $where = ['1=1'];
    $params = [];
    if (!empty($query['q'])) {
        $where[] = 'email LIKE :q';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(200, max(10, (int) ($query['perPage'] ?? 50)));
    $offset = ($page - 1) * $perPage;
    $whereSql = implode(' AND ', $where);

    $st = $pdo->prepare('SELECT COUNT(*) FROM newsletter_subscribers WHERE ' . $whereSql);
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = 'SELECT id, email, source, created_at FROM newsletter_subscribers WHERE '
        . $whereSql . ' ORDER BY created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'source' => $row['source'] !== null ? (string) $row['source'] : null,
            'createdAt' => (string) $row['created_at'],
        ];
    }

    return [
        'items' => $items,
        'meta' => ['count' => count($items), 'total' => $total, 'page' => $page, 'perPage' => $perPage],
    ];
}

function tm_admin_newsletter_export_csv(PDO $pdo): string
{
    $st = $pdo->query('SELECT email, source, created_at FROM newsletter_subscribers ORDER BY created_at DESC');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = fopen('php://temp', 'r+');
    fputcsv($out, ['email', 'source', 'subscribed_at']);
    foreach ($rows as $row) {
        fputcsv($out, [
            (string) $row['email'],
            $row['source'] ?? '',
            (string) $row['created_at'],
        ]);
    }
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return $csv !== false ? $csv : '';
}
