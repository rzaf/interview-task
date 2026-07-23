<?php
// Simple PHP router for interview task (no framework).
// Run with: php -S 127.0.0.1:8080 -t public
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($path === '/health') {
    echo json_encode(['ok' => true, 'ts' => time(), 'token_hint' => 'CAND-NT1']);
    exit;
}

if ($path === '/api/orders' && $method === 'GET') {
    require __DIR__ . '/../src/orders.php';
    exit;
}

// 404
http_response_code(404);
echo json_encode(['error' => 'Not Found', 'path' => $path]);
