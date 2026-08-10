<?php

declare(strict_types=1);

require_once __DIR__ . '/blog.php';

function tm_admin_blog_slugify(string $title): string
{
    $s = strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');

    return $s !== '' ? substr($s, 0, 180) : 'post';
}

function tm_admin_blog_estimate_reading_time(string $html): int
{
    $text = trim(strip_tags($html));
    if ($text === '') {
        return 1;
    }
    $words = str_word_count($text);

    return max(1, (int) ceil($words / 200));
}

/** Normalize publish datetime based on status (stored as UTC). */
function tm_admin_blog_normalize_published_at(string $status, ?string $publishedAt): ?string
{
    $publishedAt = $publishedAt !== null ? trim($publishedAt) : null;
    if ($publishedAt === '') {
        $publishedAt = null;
    }

    if ($status === 'draft') {
        return $publishedAt;
    }

    if ($status === 'published') {
        if ($publishedAt === null) {
            return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        }

        return $publishedAt;
    }

    // scheduled — keep as provided (may be null until admin sets a date)
    return $publishedAt;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_admin_blog_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'title' => (string) $row['title'],
        'excerpt' => $row['excerpt'] !== null ? (string) $row['excerpt'] : '',
        'contentHtml' => (string) ($row['content_html'] ?? ''),
        'status' => (string) $row['status'],
        'featuredImagePath' => $row['featured_image_path'] ?? null,
        'featuredImageUrl' => tm_blog_image_url($row['featured_image_path'] ?? null),
        'authorName' => $row['author_name'] !== null ? (string) $row['author_name'] : '',
        'tags' => tm_blog_decode_tags(isset($row['tags']) ? (string) $row['tags'] : null),
        'readingTimeMin' => $row['reading_time_min'] !== null ? (int) $row['reading_time_min'] : null,
        'publishedAt' => $row['published_at'] ?? null,
        'isFeatured' => (bool) (int) ($row['is_featured'] ?? 0),
        'metaTitle' => $row['meta_title'] ?? '',
        'metaDescription' => $row['meta_description'] ?? '',
        'metaKeywords' => $row['meta_keywords'] ?? '',
        'canonicalUrl' => $row['canonical_url'] ?? '',
        'robotsIndex' => (bool) (int) ($row['robots_index'] ?? 1),
        'ogTitle' => $row['og_title'] ?? '',
        'ogDescription' => $row['og_description'] ?? '',
        'ogImagePath' => $row['og_image_path'] ?? null,
        'ogImageUrl' => tm_blog_image_url($row['og_image_path'] ?? null),
        'ogType' => (string) ($row['og_type'] ?? 'article'),
        'twitterCard' => (string) ($row['twitter_card'] ?? 'summary_large_image'),
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'updatedAt' => $row['updated_at'] ?? null,
    ];
}

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_admin_blog_list(PDO $pdo, array $query): array
{
    if (!tm_blog_table_exists($pdo)) {
        return ['items' => [], 'meta' => ['total' => 0, 'page' => 1, 'perPage' => 20, 'count' => 0]];
    }

    $where = ['1=1'];
    $params = [];
    if (!empty($query['status']) && $query['status'] !== 'all') {
        $where[] = 'status = :status';
        $params[':status'] = (string) $query['status'];
    }
    if (!empty($query['q'])) {
        $where[] = '(title LIKE :q OR slug LIKE :q OR excerpt LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(50, max(10, (int) ($query['perPage'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    $whereSql = implode(' AND ', $where);

    $st = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE {$whereSql}");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = "SELECT * FROM blog_posts WHERE {$whereSql} ORDER BY updated_at DESC LIMIT {$perPage} OFFSET {$offset}";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = tm_admin_blog_row($row);
    }

    return [
        'items' => $items,
        'meta' => ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'count' => count($items)],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function tm_admin_blog_by_id(PDO $pdo, int $id): ?array
{
    if (!tm_blog_table_exists($pdo)) {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row !== false ? tm_admin_blog_row($row) : null;
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, post?: array}
 */
function tm_admin_blog_create(PDO $pdo, array $body): array
{
    if (!tm_blog_table_exists($pdo)) {
        return ['ok' => false, 'message' => 'Run migration_blog.sql first'];
    }

    $title = trim((string) ($body['title'] ?? ''));
    if ($title === '') {
        return ['ok' => false, 'message' => 'Title is required'];
    }

    $slug = trim((string) ($body['slug'] ?? ''));
    if ($slug === '') {
        $slug = tm_admin_blog_slugify($title);
    } else {
        $slug = tm_admin_blog_slugify($slug);
    }

    $content = (string) ($body['contentHtml'] ?? '');
    if (trim(strip_tags($content)) === '') {
        return ['ok' => false, 'message' => 'Post content is required'];
    }

    $status = (string) ($body['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
        $status = 'draft';
    }

    $tags = $body['tags'] ?? [];
    if (!is_array($tags)) {
        $tags = array_filter(array_map('trim', explode(',', (string) $tags)));
    }
    $tagsJson = json_encode(array_values(array_unique(array_filter(array_map('strval', $tags)))), JSON_THROW_ON_ERROR);

    $publishedAt = tm_admin_blog_normalize_published_at(
        $status,
        !empty($body['publishedAt']) ? (string) $body['publishedAt'] : null
    );

    try {
        $st = $pdo->prepare(
            'INSERT INTO blog_posts (
                slug, title, excerpt, content_html, status, featured_image_path, author_name, tags,
                reading_time_min, published_at, is_featured,
                meta_title, meta_description, meta_keywords, canonical_url, robots_index,
                og_title, og_description, og_image_path, og_type, twitter_card
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
             )'
        );
        $st->execute([
            substr($slug, 0, 191),
            substr($title, 0, 255),
            substr(trim((string) ($body['excerpt'] ?? '')), 0, 5000) ?: null,
            $content,
            $status,
            substr(trim((string) ($body['featuredImagePath'] ?? '')), 0, 512) ?: null,
            substr(trim((string) ($body['authorName'] ?? '')), 0, 128) ?: null,
            $tagsJson,
            tm_admin_blog_estimate_reading_time($content),
            $publishedAt,
            !empty($body['isFeatured']) ? 1 : 0,
            substr(trim((string) ($body['metaTitle'] ?? '')), 0, 255) ?: null,
            substr(trim((string) ($body['metaDescription'] ?? '')), 0, 512) ?: null,
            substr(trim((string) ($body['metaKeywords'] ?? '')), 0, 512) ?: null,
            substr(trim((string) ($body['canonicalUrl'] ?? '')), 0, 512) ?: null,
            !isset($body['robotsIndex']) || $body['robotsIndex'] ? 1 : 0,
            substr(trim((string) ($body['ogTitle'] ?? '')), 0, 255) ?: null,
            substr(trim((string) ($body['ogDescription'] ?? '')), 0, 512) ?: null,
            substr(trim((string) ($body['ogImagePath'] ?? '')), 0, 512) ?: null,
            substr(trim((string) ($body['ogType'] ?? 'article')), 0, 32) ?: 'article',
            substr(trim((string) ($body['twitterCard'] ?? 'summary_large_image')), 0, 32) ?: 'summary_large_image',
        ]);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Slug already in use'];
        }
        throw $e;
    }

    $id = (int) $pdo->lastInsertId();
    $post = tm_admin_blog_by_id($pdo, $id);

    return ['ok' => true, 'post' => $post];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message?: string, post?: array}
 */
function tm_admin_blog_update(PDO $pdo, int $id, array $body): array
{
    if (tm_admin_blog_by_id($pdo, $id) === null) {
        return ['ok' => false, 'message' => 'Post not found'];
    }

    $sets = [];
    $params = [];

    if (isset($body['title'])) {
        $sets[] = 'title = ?';
        $params[] = substr(trim((string) $body['title']), 0, 255);
    }
    if (isset($body['slug'])) {
        $sets[] = 'slug = ?';
        $params[] = substr(tm_admin_blog_slugify((string) $body['slug']), 0, 191);
    }
    if (array_key_exists('excerpt', $body)) {
        $sets[] = 'excerpt = ?';
        $params[] = substr(trim((string) $body['excerpt']), 0, 5000) ?: null;
    }
    if (isset($body['contentHtml'])) {
        $content = (string) $body['contentHtml'];
        $sets[] = 'content_html = ?';
        $params[] = $content;
        $sets[] = 'reading_time_min = ?';
        $params[] = tm_admin_blog_estimate_reading_time($content);
    }
    if (isset($body['status']) && in_array($body['status'], ['draft', 'published', 'scheduled'], true)) {
        $sets[] = 'status = ?';
        $params[] = (string) $body['status'];
    }
    if (array_key_exists('featuredImagePath', $body)) {
        $sets[] = 'featured_image_path = ?';
        $params[] = substr(trim((string) $body['featuredImagePath']), 0, 512) ?: null;
    }
    if (array_key_exists('authorName', $body)) {
        $sets[] = 'author_name = ?';
        $params[] = substr(trim((string) $body['authorName']), 0, 128) ?: null;
    }
    if (isset($body['tags'])) {
        $tags = is_array($body['tags']) ? $body['tags'] : explode(',', (string) $body['tags']);
        $sets[] = 'tags = ?';
        $params[] = json_encode(array_values(array_unique(array_filter(array_map('strval', $tags)))), JSON_THROW_ON_ERROR);
    }
    if (array_key_exists('publishedAt', $body) || isset($body['status'])) {
        $existing = tm_admin_blog_by_id($pdo, $id);
        $status = isset($body['status']) && in_array($body['status'], ['draft', 'published', 'scheduled'], true)
            ? (string) $body['status']
            : (string) ($existing['status'] ?? 'draft');
        $rawPublished = array_key_exists('publishedAt', $body)
            ? ($body['publishedAt'] ? (string) $body['publishedAt'] : null)
            : ($existing['publishedAt'] ?? null);
        $sets[] = 'published_at = ?';
        $params[] = tm_admin_blog_normalize_published_at($status, $rawPublished);
    }
    if (isset($body['isFeatured'])) {
        $sets[] = 'is_featured = ?';
        $params[] = !empty($body['isFeatured']) ? 1 : 0;
    }
    foreach ([
        'metaTitle' => 'meta_title',
        'metaDescription' => 'meta_description',
        'metaKeywords' => 'meta_keywords',
        'canonicalUrl' => 'canonical_url',
        'ogTitle' => 'og_title',
        'ogDescription' => 'og_description',
        'ogImagePath' => 'og_image_path',
        'ogType' => 'og_type',
        'twitterCard' => 'twitter_card',
    ] as $key => $col) {
        if (array_key_exists($key, $body)) {
            $sets[] = "{$col} = ?";
            $params[] = substr(trim((string) $body[$key]), 0, $col === 'meta_description' || str_contains($col, 'description') ? 512 : 255) ?: null;
        }
    }
    if (isset($body['robotsIndex'])) {
        $sets[] = 'robots_index = ?';
        $params[] = !empty($body['robotsIndex']) ? 1 : 0;
    }

    if ($sets === []) {
        return ['ok' => false, 'message' => 'Nothing to update'];
    }

    $params[] = $id;
    try {
        $pdo->prepare('UPDATE blog_posts SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    } catch (PDOException $e) {
        if (tm_auth_pdo_is_duplicate_key($e)) {
            return ['ok' => false, 'message' => 'Slug already in use'];
        }
        throw $e;
    }

    return ['ok' => true, 'post' => tm_admin_blog_by_id($pdo, $id)];
}

/**
 * @return array{ok: bool, message?: string}
 */
function tm_admin_blog_delete(PDO $pdo, int $id): array
{
    $st = $pdo->prepare('DELETE FROM blog_posts WHERE id = ?');
    $st->execute([$id]);
    if ($st->rowCount() === 0) {
        return ['ok' => false, 'message' => 'Post not found'];
    }

    return ['ok' => true];
}
