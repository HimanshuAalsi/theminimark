<?php

declare(strict_types=1);

require_once __DIR__ . '/converter_settings.php';
require_once __DIR__ . '/image_optimize.php';

/** @return list<string> */
function tm_converter_raster_extensions(): array
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
}

function tm_converter_file_needs_work(string $fullPath, bool $reoptimizeWebp): bool
{
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        return true;
    }

    return $ext === 'webp' && $reoptimizeWebp;
}

/** Public path like /uploads/products/bookmarks/file.jpg */
function tm_converter_public_path_from_full(string $fullPath): string
{
    $base = realpath(tm_uploads_base_dir());
    $real = realpath($fullPath);
    if ($base === false || $real === false || !str_starts_with($real, $base)) {
        return '';
    }
    $rel = ltrim(str_replace('\\', '/', substr($real, strlen($base))), '/');

    return '/uploads/' . $rel;
}

/** Subdir under uploads for tm_upload_public_path, e.g. products/bookmarks */
function tm_converter_public_subdir(string $fullPath): string
{
    $public = tm_converter_public_path_from_full($fullPath);
    if ($public === '') {
        return '';
    }
    $rel = ltrim(substr($public, strlen('/uploads/')), '/');
    $dir = dirname($rel);

    return $dir === '.' ? '' : $dir;
}

function tm_converter_full_path_from_public(string $publicPath): string
{
    $rel = ltrim(str_replace('/uploads/', '', $publicPath), '/');

    return tm_uploads_base_dir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
}

/**
 * @param array<string, bool> $scopes
 * @return list<string>
 */
function tm_converter_collect_files(array $scopes, bool $reoptimizeWebp): array
{
    $base = tm_uploads_base_dir();
    $roots = tm_upload_roots();
    $files = [];

    foreach ($scopes as $root => $enabled) {
        if (!$enabled || !isset($roots[$root])) {
            continue;
        }
        $rootDir = $base . DIRECTORY_SEPARATOR . $root;
        if (!is_dir($rootDir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (!tm_converter_file_needs_work($path, $reoptimizeWebp)) {
                continue;
            }
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * @return array{ok: bool, webpAvailable: bool, settings: array, summary: array, sample: list<array>}
 */
function tm_converter_scan(): array
{
    $settings = tm_converter_load_settings();
    $files = tm_converter_collect_files($settings['scopes'], (bool) $settings['reoptimizeExistingWebp']);

    $byExt = [];
    $totalBytes = 0;
    $sample = [];

    foreach ($files as $i => $fullPath) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $byExt[$ext] = ($byExt[$ext] ?? 0) + 1;
        $size = (int) (@filesize($fullPath) ?: 0);
        $totalBytes += $size;
        if ($i < 30) {
            $sample[] = [
                'path' => tm_converter_public_path_from_full($fullPath),
                'sizeBytes' => $size,
                'format' => $ext,
            ];
        }
    }

    return [
        'ok' => true,
        'webpAvailable' => tm_image_webp_available(),
        'zipAvailable' => class_exists('ZipArchive'),
        'settings' => $settings,
        'summary' => [
            'totalFiles' => count($files),
            'totalBytes' => $totalBytes,
            'byExtension' => $byExt,
        ],
        'sample' => $sample,
    ];
}

function tm_converter_replace_db_path(PDO $pdo, string $oldPath, string $newPath): int
{
    if ($oldPath === '' || $newPath === '' || $oldPath === $newPath) {
        return 0;
    }
    $updated = 0;

    $st = $pdo->prepare('UPDATE products SET image_url = ? WHERE image_url = ?');
    $st->execute([$newPath, $oldPath]);
    $updated += $st->rowCount();

    try {
        $st = $pdo->prepare('UPDATE product_images SET image_path = ? WHERE image_path = ?');
        $st->execute([$newPath, $oldPath]);
        $updated += $st->rowCount();
    } catch (PDOException) {
        /* optional table */
    }

    try {
        $st = $pdo->prepare('UPDATE categories SET image_path = ? WHERE image_path = ?');
        $st->execute([$newPath, $oldPath]);
        $updated += $st->rowCount();
    } catch (PDOException) {
        /* optional table */
    }

    try {
        $st = $pdo->prepare('UPDATE order_line_personalisation SET photo_path = ? WHERE photo_path = ?');
        $st->execute([$newPath, $oldPath]);
        $updated += $st->rowCount();
    } catch (PDOException) {
        /* optional table */
    }

    return $updated;
}

/**
 * @return array{ok: bool, message?: string, oldPath?: string, newPath?: string, bytesBefore?: int, bytesAfter?: int, skipped?: bool, dbRowsUpdated?: int}
 */
function tm_converter_process_file(PDO $pdo, string $fullPath, array $settings, bool $dryRun): array
{
    $oldPublic = tm_converter_public_path_from_full($fullPath);
    if ($oldPublic === '') {
        return ['ok' => false, 'message' => 'Invalid upload path'];
    }

    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $bytesBefore = (int) (@filesize($fullPath) ?: 0);
    $isWebp = $ext === 'webp';

    if ($isWebp && empty($settings['reoptimizeExistingWebp'])) {
        return ['ok' => true, 'skipped' => true, 'oldPath' => $oldPublic, 'message' => 'Already WebP'];
    }

    if ($dryRun) {
        return [
            'ok' => true,
            'oldPath' => $oldPublic,
            'newPath' => $isWebp ? $oldPublic : preg_replace('/\.[^.]+$/', '.webp', $oldPublic),
            'bytesBefore' => $bytesBefore,
            'bytesAfter' => $bytesBefore,
            'skipped' => false,
        ];
    }

    $options = [
        'quality' => (int) $settings['webpQuality'],
        'max_dimension' => (int) $settings['maxDimension'],
    ];

    if ($isWebp) {
        $tempDest = $fullPath . '.opt.webp';
        $conv = tm_image_convert_file_to_webp($fullPath, $tempDest, $options);
        if (!$conv['ok']) {
            return ['ok' => false, 'message' => $conv['message'] ?? 'Conversion failed', 'oldPath' => $oldPublic];
        }
        if (!@rename($tempDest, $fullPath)) {
            @unlink($tempDest);

            return ['ok' => false, 'message' => 'Could not replace WebP file', 'oldPath' => $oldPublic];
        }
        $newPublic = $oldPublic;
        $fullNew = $fullPath;
    } else {
        $subdir = tm_converter_public_subdir($fullPath);
        $optimized = tm_image_optimize_saved_file_with_options($fullPath, $subdir, $options);
        if (!$optimized['ok']) {
            return ['ok' => false, 'message' => $optimized['message'] ?? 'Conversion failed', 'oldPath' => $oldPublic];
        }
        $newPublic = (string) ($optimized['path'] ?? $oldPublic);
        $fullNew = tm_converter_full_path_from_public($newPublic);
    }

    $bytesAfter = is_file($fullNew) ? (int) filesize($fullNew) : $bytesBefore;

    $dbRows = 0;
    if (!empty($settings['updateDatabasePaths']) && $oldPublic !== $newPublic) {
        $dbRows = tm_converter_replace_db_path($pdo, $oldPublic, $newPublic);
    }

    return [
        'ok' => true,
        'oldPath' => $oldPublic,
        'newPath' => $newPublic,
        'bytesBefore' => $bytesBefore,
        'bytesAfter' => $bytesAfter,
        'bytesSaved' => max(0, $bytesBefore - $bytesAfter),
        'dbRowsUpdated' => $dbRows,
    ];
}

/**
 * @param array{quality: int, max_dimension: int} $options
 * @return array{ok: bool, message?: string, path?: string, filename?: string}
 */
function tm_image_optimize_saved_file_with_options(string $filePath, string $publicSubdir, array $options): array
{
    $filePath = realpath($filePath) ?: $filePath;
    if (!is_file($filePath)) {
        return ['ok' => false, 'message' => 'File not found'];
    }

    $dir = dirname($filePath);
    $base = pathinfo($filePath, PATHINFO_FILENAME);
    $webpName = $base . '.webp';
    $webpPath = $dir . DIRECTORY_SEPARATOR . $webpName;

    $conv = tm_image_convert_file_to_webp($filePath, $webpPath, $options);
    if (!$conv['ok']) {
        return $conv;
    }

    if (realpath($filePath) !== realpath($webpPath)) {
        @unlink($filePath);
    }

    $publicSubdir = trim(str_replace('\\', '/', $publicSubdir), '/');
    $path = tm_upload_public_path($publicSubdir, $webpName);

    return [
        'ok' => true,
        'path' => $path,
        'filename' => $webpName,
    ];
}

/**
 * @param array<string, mixed> $query
 * @return array<string, mixed>
 */
function tm_converter_run_batch(PDO $pdo, array $query): array
{
    if (!tm_image_webp_available()) {
        return ['ok' => false, 'message' => 'WebP conversion is not available on this server (PHP GD + WebP required).'];
    }

    $settings = tm_converter_load_settings();
    $dryRun = !empty($query['dryRun']);
    $offset = max(0, (int) ($query['offset'] ?? 0));
    $limit = min(50, max(1, (int) ($query['limit'] ?? 15)));

    $files = tm_converter_collect_files($settings['scopes'], (bool) $settings['reoptimizeExistingWebp']);
    $total = count($files);
    $slice = array_slice($files, $offset, $limit);

    $converted = 0;
    $skipped = 0;
    $failed = 0;
    $bytesBefore = 0;
    $bytesAfter = 0;
    $dbUpdated = 0;
    $items = [];
    $errors = [];

    foreach ($slice as $fullPath) {
        $result = tm_converter_process_file($pdo, $fullPath, $settings, $dryRun);
        if (!$result['ok']) {
            $failed++;
            $errors[] = ($result['oldPath'] ?? $fullPath) . ': ' . ($result['message'] ?? 'Failed');
            continue;
        }
        if (!empty($result['skipped'])) {
            $skipped++;
            continue;
        }
        $converted++;
        $bytesBefore += (int) ($result['bytesBefore'] ?? 0);
        $bytesAfter += (int) ($result['bytesAfter'] ?? 0);
        $dbUpdated += (int) ($result['dbRowsUpdated'] ?? 0);
        $items[] = [
            'oldPath' => $result['oldPath'] ?? '',
            'newPath' => $result['newPath'] ?? '',
            'bytesSaved' => (int) ($result['bytesSaved'] ?? max(0, (int) ($result['bytesBefore'] ?? 0) - (int) ($result['bytesAfter'] ?? 0))),
        ];
    }

    $nextOffset = $offset + count($slice);
    $done = $nextOffset >= $total;

    return [
        'ok' => true,
        'dryRun' => $dryRun,
        'settings' => $settings,
        'progress' => [
            'offset' => $offset,
            'nextOffset' => $nextOffset,
            'limit' => $limit,
            'total' => $total,
            'done' => $done,
        ],
        'stats' => [
            'converted' => $converted,
            'skipped' => $skipped,
            'failed' => $failed,
            'bytesBefore' => $bytesBefore,
            'bytesAfter' => $bytesAfter,
            'bytesSaved' => max(0, $bytesBefore - $bytesAfter),
            'dbRowsUpdated' => $dbUpdated,
        ],
        'items' => $items,
        'errors' => $errors,
    ];
}

/** @return array<string, mixed> */
function tm_converter_status(): array
{
    $settings = tm_converter_load_settings();
    $scan = tm_converter_scan();

    return [
        'ok' => true,
        'webpAvailable' => tm_image_webp_available(),
        'zipAvailable' => class_exists('ZipArchive'),
        'settings' => $settings,
        'summary' => $scan['summary'],
    ];
}

function tm_converter_exports_dir(): string
{
    return tm_uploads_base_dir() . DIRECTORY_SEPARATOR . 'converter_exports';
}

function tm_converter_remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            tm_converter_remove_dir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function tm_converter_cleanup_old_exports(int $maxAgeSeconds = 86400): void
{
    $dir = tm_converter_exports_dir();
    if (!is_dir($dir)) {
        return;
    }
    $cutoff = time() - $maxAgeSeconds;
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
        if (@filemtime($path) !== false && filemtime($path) < $cutoff) {
            if (is_dir($path)) {
                tm_converter_remove_dir($path);
            } else {
                @unlink($path);
            }
        }
    }
}

/** @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}> */
function tm_converter_normalize_uploaded_files(): array
{
    foreach (['files', 'file', 'images'] as $key) {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
            continue;
        }
        $f = $_FILES[$key];
        if (!isset($f['name'])) {
            continue;
        }
        if (!is_array($f['name'])) {
            return [[
                'name' => (string) $f['name'],
                'type' => (string) ($f['type'] ?? ''),
                'tmp_name' => (string) ($f['tmp_name'] ?? ''),
                'error' => (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($f['size'] ?? 0),
            ]];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            $out[] = [
                'name' => (string) $name,
                'type' => (string) ($f['type'][$i] ?? ''),
                'tmp_name' => (string) ($f['tmp_name'][$i] ?? ''),
                'error' => (int) ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($f['size'][$i] ?? 0),
            ];
        }

        return $out;
    }

    return [];
}

function tm_converter_safe_webp_basename(string $originalName, int $index = 0): string
{
    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9._-]+/', '-', (string) $base) ?: 'image';
    $base = trim((string) $base, '-.');
    if ($base === '') {
        $base = 'image';
    }
    if (strlen($base) > 72) {
        $base = substr($base, 0, 72);
    }
    if ($index > 0) {
        $base .= '-' . $index;
    }

    return $base . '.webp';
}

function tm_converter_zip_path(string $jobId): string
{
    return tm_converter_exports_dir() . DIRECTORY_SEPARATOR . $jobId . '.zip';
}

function tm_converter_job_dir(string $jobId): string
{
    return tm_converter_exports_dir() . DIRECTORY_SEPARATOR . $jobId;
}

/**
 * Upload external images, convert to WebP, return ZIP download job.
 *
 * @return array<string, mixed>
 */
function tm_converter_upload_to_zip(): array
{
    if (!tm_image_webp_available()) {
        return ['ok' => false, 'message' => 'WebP conversion is not available on this server.'];
    }
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'message' => 'PHP ZipArchive is required for ZIP downloads.'];
    }

    tm_converter_cleanup_old_exports();

    $uploads = tm_converter_normalize_uploaded_files();
    if ($uploads === []) {
        return ['ok' => false, 'message' => 'No files uploaded. Use field name files[] with JPEG, PNG, GIF, or WebP.'];
    }

    $maxFiles = 50;
    if (count($uploads) > $maxFiles) {
        return ['ok' => false, 'message' => "Maximum {$maxFiles} files per batch."];
    }

    $settings = tm_converter_load_settings();
    $options = [
        'quality' => (int) $settings['webpQuality'],
        'max_dimension' => (int) $settings['maxDimension'],
    ];

    $exportsDir = tm_converter_exports_dir();
    if (!is_dir($exportsDir) && !mkdir($exportsDir, 0755, true) && !is_dir($exportsDir)) {
        return ['ok' => false, 'message' => 'Could not create export directory. Check uploads folder permissions.'];
    }

    $jobId = bin2hex(random_bytes(16));
    $jobDir = tm_converter_job_dir($jobId);
    if (!mkdir($jobDir, 0755, true) && !is_dir($jobDir)) {
        return ['ok' => false, 'message' => 'Could not create job folder.'];
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $converted = [];
    $errors = [];
    $bytesBefore = 0;
    $bytesAfter = 0;
    $usedNames = [];
    $totalBeforeUpload = 0;

    foreach ($uploads as $file) {
        $originalName = (string) ($file['name'] ?? 'image.jpg');
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = $originalName . ': upload error';
            continue;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_file($tmp) || !is_uploaded_file($tmp)) {
            $errors[] = $originalName . ': invalid upload';
            continue;
        }
        if ($size > 8 * 1024 * 1024) {
            $errors[] = $originalName . ': exceeds 8 MB limit';
            continue;
        }
        $totalBeforeUpload += $size;
        if ($totalBeforeUpload > 80 * 1024 * 1024) {
            $errors[] = $originalName . ': total batch size exceeds 80 MB';
            continue;
        }

        $mime = tm_upload_detect_mime($tmp);
        if ($mime === '' || !in_array($mime, $allowedMime, true)) {
            $errors[] = $originalName . ': unsupported format';
            continue;
        }

        $index = 0;
        do {
            $webpName = tm_converter_safe_webp_basename($originalName, $index);
            $index++;
        } while (isset($usedNames[$webpName]));
        $usedNames[$webpName] = true;

        $destPath = $jobDir . DIRECTORY_SEPARATOR . $webpName;
        $conv = tm_image_convert_file_to_webp($tmp, $destPath, $options);
        if (!$conv['ok']) {
            $errors[] = $originalName . ': ' . ($conv['message'] ?? 'conversion failed');
            continue;
        }

        $after = (int) (@filesize($destPath) ?: 0);
        $bytesBefore += $size;
        $bytesAfter += $after;
        $converted[] = [
            'originalName' => $originalName,
            'webpName' => $webpName,
            'bytesBefore' => $size,
            'bytesAfter' => $after,
            'bytesSaved' => max(0, $size - $after),
        ];
    }

    if ($converted === []) {
        tm_converter_remove_dir($jobDir);

        return ['ok' => false, 'message' => 'No files converted.', 'errors' => $errors];
    }

    $zipPath = tm_converter_zip_path($jobId);
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        tm_converter_remove_dir($jobDir);
        @unlink($zipPath);

        return ['ok' => false, 'message' => 'Could not create ZIP archive.'];
    }
    foreach ($converted as $item) {
        $full = $jobDir . DIRECTORY_SEPARATOR . $item['webpName'];
        if (is_file($full)) {
            $zip->addFile($full, $item['webpName']);
        }
    }
    $zip->close();
    tm_converter_remove_dir($jobDir);

    if (!is_file($zipPath)) {
        return ['ok' => false, 'message' => 'ZIP file was not created.'];
    }

    return [
        'ok' => true,
        'jobId' => $jobId,
        'fileCount' => count($converted),
        'zipBytes' => (int) filesize($zipPath),
        'stats' => [
            'bytesBefore' => $bytesBefore,
            'bytesAfter' => $bytesAfter,
            'bytesSaved' => max(0, $bytesBefore - $bytesAfter),
            'failed' => count($errors),
        ],
        'files' => $converted,
        'errors' => $errors,
    ];
}

function tm_converter_serve_zip_download(string $jobId): void
{
    if (!preg_match('/^[a-f0-9]{32}$/', $jobId)) {
        tm_json(['message' => 'Invalid download id'], 400);
        return;
    }
    $zipPath = tm_converter_zip_path($jobId);
    if (!is_file($zipPath)) {
        tm_json(['message' => 'Download expired or not found'], 404);
        return;
    }

    $filename = 'webp-converted-' . date('Y-m-d-His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) filesize($zipPath));
    header('Cache-Control: no-store');
    readfile($zipPath);
    exit;
}
