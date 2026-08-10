<?php

declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

function tm_blog_table_exists(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'blog_posts'"
    );
    $cache = $st !== false && (int) $st->fetchColumn() > 0;

    return $cache;
}

/** @return list<string> */
function tm_blog_decode_tags(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $decoded = json_decode($json, true);

    return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
}

function tm_blog_image_url(?string $path): ?string
{
    if ($path === null || trim($path) === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return tm_upload_resolve_url($path);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_blog_public_summary(array $row): array
{
    $tags = tm_blog_decode_tags(isset($row['tags']) ? (string) $row['tags'] : null);

    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'title' => (string) $row['title'],
        'excerpt' => $row['excerpt'] !== null ? (string) $row['excerpt'] : null,
        'featuredImageUrl' => tm_blog_image_url($row['featured_image_path'] ?? null),
        'authorName' => $row['author_name'] !== null ? (string) $row['author_name'] : null,
        'tags' => $tags,
        'readingTimeMin' => $row['reading_time_min'] !== null ? (int) $row['reading_time_min'] : null,
        'publishedAt' => $row['published_at'] ?? null,
        'isFeatured' => (bool) (int) ($row['is_featured'] ?? 0),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function tm_blog_public_detail(array $row): array
{
    $summary = tm_blog_public_summary($row);
    $cfg = tm_config();
    $siteUrl = rtrim((string) ($cfg['public_base_url'] ?? ''), '/');
    $slug = (string) $row['slug'];
    $canonical = $row['canonical_url'] ?? null;
    if ($canonical === null || trim((string) $canonical) === '') {
        $canonical = $siteUrl !== '' ? $siteUrl . '/blog/' . rawurlencode($slug) : '/blog/' . $slug;
    }

    $metaTitle = trim((string) ($row['meta_title'] ?? '')) ?: (string) $row['title'];
    $metaDesc = trim((string) ($row['meta_description'] ?? '')) ?: (string) ($row['excerpt'] ?? '');
    $ogTitle = trim((string) ($row['og_title'] ?? '')) ?: $metaTitle;
    $ogDesc = trim((string) ($row['og_description'] ?? '')) ?: $metaDesc;
    $ogImage = tm_blog_image_url($row['og_image_path'] ?? null)
        ?? tm_blog_image_url($row['featured_image_path'] ?? null);

    return array_merge($summary, [
        'contentHtml' => (string) ($row['content_html'] ?? ''),
        'seo' => [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDesc,
            'metaKeywords' => $row['meta_keywords'] ?? null,
            'canonicalUrl' => (string) $canonical,
            'robotsIndex' => (bool) (int) ($row['robots_index'] ?? 1),
        ],
        'openGraph' => [
            'title' => $ogTitle,
            'description' => $ogDesc,
            'imageUrl' => $ogImage,
            'type' => (string) ($row['og_type'] ?? 'article'),
        ],
        'twitterCard' => (string) ($row['twitter_card'] ?? 'summary_large_image'),
        'updatedAt' => $row['updated_at'] ?? null,
    ]);
}

/**
 * SQL fragment: posts visible on the public blog.
 * Published = live immediately. Scheduled = live when publish time has passed.
 */
function tm_blog_public_visibility_sql(): string
{
    return "(status = 'published' OR (status = 'scheduled' AND published_at IS NOT NULL AND published_at <= UTC_TIMESTAMP()))";
}

/**
 * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
 */
function tm_blog_list_public(PDO $pdo, array $query): array
{
    if (!tm_blog_table_exists($pdo)) {
        return ['items' => [], 'meta' => ['total' => 0, 'page' => 1, 'perPage' => 12, 'count' => 0]];
    }

    $page = max(1, (int) ($query['page'] ?? 1));
    $perPage = min(24, max(6, (int) ($query['perPage'] ?? 12)));
    $offset = ($page - 1) * $perPage;

    $where = [tm_blog_public_visibility_sql()];
    $params = [];

    if (!empty($query['q'])) {
        $where[] = '(title LIKE :q OR excerpt LIKE :q OR meta_keywords LIKE :q)';
        $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $query['q']) . '%';
    }
    if (!empty($query['tag'])) {
        $where[] = 'JSON_CONTAINS(tags, :tag_json, "$")';
        $params[':tag_json'] = json_encode((string) $query['tag'], JSON_THROW_ON_ERROR);
    }
    if (!empty($query['featured'])) {
        $where[] = 'is_featured = 1';
    }

    $whereSql = implode(' AND ', $where);
    $st = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE {$whereSql}");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $sql = "SELECT id, slug, title, excerpt, featured_image_path, author_name, tags, reading_time_min,
                   published_at, is_featured
            FROM blog_posts WHERE {$whereSql}
            ORDER BY is_featured DESC, published_at DESC, id DESC
            LIMIT " . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = tm_blog_public_summary($row);
    }

    return [
        'items' => $items,
        'meta' => ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'count' => count($items)],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function tm_blog_by_slug(PDO $pdo, string $slug): ?array
{
    if (!tm_blog_table_exists($pdo)) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT * FROM blog_posts
         WHERE slug = ? AND ' . tm_blog_public_visibility_sql() . '
         LIMIT 1'
    );
    $st->execute([$slug]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row !== false ? tm_blog_public_detail($row) : null;
}
