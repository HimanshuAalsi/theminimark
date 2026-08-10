<?php

declare(strict_types=1);

require_once __DIR__ . '/converter_settings.php';

/**
 * Preserves visual quality while reducing file size (metadata stripped, efficient encoding).
 */

function tm_image_webp_available(): bool
{
    return extension_loaded('gd') && function_exists('imagewebp') && function_exists('imagecreatefromjpeg');
}

/** @return array{quality: int, max_dimension: int} */
function tm_image_upload_settings(): array
{
    if (function_exists('tm_converter_upload_options')) {
        $s = tm_converter_upload_options();

        return [
            'quality' => $s['quality'],
            'max_dimension' => $s['max_dimension'],
        ];
    }
    $cfg = tm_config()['uploads'] ?? [];

    return [
        'quality' => min(100, max(80, (int) ($cfg['webp_quality'] ?? 92))),
        'max_dimension' => min(4096, max(1200, (int) ($cfg['max_dimension'] ?? 2560))),
    ];
}

/**
 * @return GdImage|false
 */
function tm_image_gd_load(string $path, string $mime)
{
    return match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        'image/gif' => @imagecreatefromgif($path),
        default => false,
    };
}

/** @param GdImage|resource $img */
function tm_image_gd_prepare_alpha($img): void
{
    if (!is_object($img) && !is_resource($img)) {
        return;
    }
    if (function_exists('imagepalettetotruecolor')) {
        @imagepalettetotruecolor($img);
    }
    @imagesavealpha($img, true);
    @imagealphablending($img, true);
}

/**
 * @param GdImage|resource $img
 * @return GdImage|resource
 */
function tm_image_gd_fix_exif_orientation($img, string $path, string $mime)
{
    if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
        return $img;
    }
    $exif = @exif_read_data($path);
    if (!is_array($exif) || !isset($exif['Orientation'])) {
        return $img;
    }
    $angle = match ((int) $exif['Orientation']) {
        3 => 180,
        6 => -90,
        8 => 90,
        default => 0,
    };
    if ($angle === 0) {
        return $img;
    }
    $rotated = @imagerotate($img, $angle, 0);
    if ($rotated === false) {
        return $img;
    }
    if (is_object($img) || is_resource($img)) {
        @imagedestroy($img);
    }

    return $rotated;
}

/**
 * Downscale only when the longest edge exceeds max (keeps quality for normal product shots).
 *
 * @param GdImage|resource $img
 * @return GdImage|resource
 */
function tm_image_gd_resize_within($img, int $maxDimension)
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0) {
        return $img;
    }
    $longest = max($w, $h);
    if ($longest <= $maxDimension) {
        return $img;
    }
    $scale = $maxDimension / $longest;
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $resized = imagecreatetruecolor($nw, $nh);
    if ($resized === false) {
        return $img;
    }
    tm_image_gd_prepare_alpha($resized);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if (is_object($img) || is_resource($img)) {
        @imagedestroy($img);
    }

    return $resized;
}

/**
 * @param GdImage|resource $img
 */
function tm_image_gd_save_webp($img, string $destPath, int $quality): bool
{
    tm_image_gd_prepare_alpha($img);
    $dir = dirname($destPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    return @imagewebp($img, $destPath, $quality);
}

/**
 * Convert any supported raster image file to WebP.
 *
 * @param array{quality?: int, max_dimension?: int}|null $optionsOverride
 * @return array{ok: bool, message?: string, bytes?: int}
 */
function tm_image_convert_file_to_webp(string $sourcePath, string $destPath, ?array $optionsOverride = null): array
{
    if (!tm_image_webp_available()) {
        return ['ok' => false, 'message' => 'WebP conversion is not available (enable PHP GD with WebP support).'];
    }
    if (!is_file($sourcePath)) {
        return ['ok' => false, 'message' => 'Source image not found'];
    }

    $mime = tm_upload_detect_mime($sourcePath);
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if ($mime === '' || !in_array($mime, $allowed, true)) {
        return ['ok' => false, 'message' => 'Unsupported image format'];
    }

    $img = tm_image_gd_load($sourcePath, $mime);
    if ($img === false) {
        return ['ok' => false, 'message' => 'Could not read image file'];
    }

    $settings = tm_image_upload_settings();
    if ($optionsOverride !== null) {
        if (isset($optionsOverride['quality'])) {
            $settings['quality'] = min(100, max(80, (int) $optionsOverride['quality']));
        }
        if (isset($optionsOverride['max_dimension'])) {
            $settings['max_dimension'] = min(4096, max(800, (int) $optionsOverride['max_dimension']));
        }
    }
    $img = tm_image_gd_fix_exif_orientation($img, $sourcePath, $mime);
    $img = tm_image_gd_resize_within($img, $settings['max_dimension']);

    $ok = tm_image_gd_save_webp($img, $destPath, $settings['quality']);
    if (is_object($img) || is_resource($img)) {
        @imagedestroy($img);
    }
    if (!$ok) {
        return ['ok' => false, 'message' => 'Could not save WebP image'];
    }

    return ['ok' => true, 'bytes' => (int) (@filesize($destPath) ?: 0)];
}

/**
 * Optimize a saved file in place: writes .webp alongside, deletes original when different.
 *
 * @return array{ok: bool, message?: string, path?: string, filename?: string}
 */
function tm_image_optimize_saved_file(string $filePath, string $publicSubdir): array
{
    $filePath = realpath($filePath) ?: $filePath;
    if (!is_file($filePath)) {
        return ['ok' => false, 'message' => 'File not found'];
    }

    $dir = dirname($filePath);
    $base = pathinfo($filePath, PATHINFO_FILENAME);
    $webpName = $base . '.webp';
    $webpPath = $dir . DIRECTORY_SEPARATOR . $webpName;

    $conv = tm_image_convert_file_to_webp($filePath, $webpPath);
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
 * Convert an uploaded temp file directly to WebP destination (no intermediate original kept).
 *
 * @return array{ok: bool, message?: string, path?: string, url?: string, filename?: string}
 */
function tm_image_store_upload_as_webp(string $tmpPath, string $destDir, string $publicSubdir): array
{
    $base = bin2hex(random_bytes(8)) . '-' . time();
    $webpName = $base . '.webp';
    $destPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $webpName;

    $conv = tm_image_convert_file_to_webp($tmpPath, $destPath);
    if (!$conv['ok']) {
        return $conv;
    }

    $publicSubdir = trim(str_replace('\\', '/', $publicSubdir), '/');
    $path = tm_upload_public_path($publicSubdir, $webpName);

    return [
        'ok' => true,
        'path' => $path,
        'url' => tm_upload_resolve_url($path),
        'filename' => $webpName,
    ];
}
