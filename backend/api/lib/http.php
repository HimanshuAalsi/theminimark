<?php

declare(strict_types=1);

function tm_config(): array
{
    return $GLOBALS['tm_config'];
}

function tm_cors(): void
{
    $origin = tm_config()['cors_origin'] ?? '*';
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    header('Access-Control-Max-Age: 86400');
}

function tm_json(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function tm_read_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function tm_route_path(): string
{
    $cfg = tm_config();
    $base = (string) ($cfg['api_base_path'] ?? '/api');
    $base = '/' . trim($base, '/');

    if (!empty($_SERVER['PATH_INFO'])) {
        return trim((string) $_SERVER['PATH_INFO'], '/');
    }

    if (isset($_GET['path']) && is_string($_GET['path'])) {
        return trim($_GET['path'], '/');
    }

    $uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
    if ($base !== '/' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = trim($uri, '/');
    if (str_starts_with($uri, 'index.php/')) {
        $uri = substr($uri, 10);
    }
    $uri = trim($uri, '/');

    return $uri;
}

function tm_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}
