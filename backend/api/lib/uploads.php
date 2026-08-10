<?php

declare(strict_types=1);

/** Allowed upload roots under backend/uploads/ */
function tm_upload_roots(): array
{
    return [
        'products' => ['bookmarks', 'cards', 'calendars', 'magnets', 'hampers', 'misc'],
        'personalise' => ['drafts'],
        'site' => ['banners', 'misc', 'home'],
        'blog' => ['posts'],
    ];
}

function tm_uploads_base_dir(): string
{
    $apiUploads = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    $legacyUploads = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';
    if (is_dir($apiUploads)) {
        return $apiUploads;
    }
    if (is_dir($legacyUploads)) {
        return $legacyUploads;
    }

    return $apiUploads;
}

/** Detect image MIME without requiring ext-fileinfo (common on Windows PHP builds). */
function tm_upload_detect_mime(string $path): string
{
    if ($path === '' || !is_file($path)) {
        return '';
    }
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return is_string($mime) ? $mime : '';
    }
    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($path);

        return is_string($mime) ? $mime : '';
    }
    $info = @getimagesize($path);
    if (is_array($info) && isset($info['mime']) && is_string($info['mime'])) {
        return $info['mime'];
    }

    return '';
}

/** Public URL path stored in DB (served via /v1/uploads/...). */
function tm_upload_public_path(string $subdir, string $filename): string
{
    $subdir = trim(str_replace('\\', '/', $subdir), '/');
    $filename = basename($filename);
    return '/uploads/' . $subdir . '/' . $filename;
}

/**
 * @return array{ok: bool, message?: string, path?: string, url?: string}
 */
function tm_upload_save_image(array $file, string $root, string $subfolder): array
{
    $roots = tm_upload_roots();
    if (!isset($roots[$root])) {
        return ['ok' => false, 'message' => 'Invalid upload root'];
    }
    $allowedSubs = $roots[$root];
    $subfolder = preg_replace('/[^a-z0-9_-]/', '', strtolower($subfolder)) ?: 'misc';
    if (!in_array($subfolder, $allowedSubs, true)) {
        $subfolder = 'misc';
    }

    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed (code ' . $err . ')'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        return ['ok' => false, 'message' => 'No file uploaded'];
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp)) {
        return ['ok' => false, 'message' => 'Invalid upload'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size > 8 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'Image must be under 8 MB'];
    }

    $mime = tm_upload_detect_mime($tmp);
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if ($mime === '' || !isset($extMap[$mime])) {
        return ['ok' => false, 'message' => 'Only JPEG, PNG, WebP, or GIF images are allowed'];
    }

    $dir = tm_uploads_base_dir() . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR . $subfolder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create upload directory'];
    }

    require_once __DIR__ . '/image_optimize.php';

    $publicSubdir = $root . '/' . $subfolder;
    $stored = tm_image_store_upload_as_webp($tmp, $dir, $publicSubdir);
    if (!$stored['ok']) {
        return ['ok' => false, 'message' => $stored['message'] ?? 'Could not optimize image'];
    }

    return [
        'ok' => true,
        'path' => $stored['path'],
        'url' => $stored['url'],
        'format' => 'webp',
    ];
}

/** Turn stored path into absolute URL for API responses. */
function tm_upload_resolve_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    if (str_starts_with($path, '/uploads/')) {
        $cfg = tm_config();
        $public = (string) ($cfg['public_base_url'] ?? '');
        if ($public !== '') {
            return rtrim($public, '/') . '/api/v1' . $path;
        }
        return '/api/v1' . $path;
    }
    return $path;
}

/**
 * Serve a file under backend/uploads/ from route segments after "uploads".
 *
 * @param list<string> $segments e.g. ['v1','uploads','products','bookmarks','file.jpg']
 */
function tm_uploads_serve(array $segments): void
{
    if (count($segments) < 4 || $segments[0] !== 'v1' || $segments[1] !== 'uploads') {
        tm_json(['message' => 'Not found'], 404);
        return;
    }
    $parts = array_slice($segments, 2);
    $rel = implode(DIRECTORY_SEPARATOR, $parts);
    if (str_contains($rel, '..') || str_contains($rel, "\0")) {
        tm_json(['message' => 'Invalid path'], 400);
        return;
    }

    $base = realpath(tm_uploads_base_dir());
    if ($base === false) {
        tm_json(['message' => 'Uploads not configured'], 404);
        return;
    }
    $full = realpath($base . DIRECTORY_SEPARATOR . $rel);
    if ($full === false || !str_starts_with($full, $base) || !is_file($full)) {
        tm_json(['message' => 'File not found'], 404);
        return;
    }

    $mime = mime_content_type($full) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    readfile($full);
    exit;
}
