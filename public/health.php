<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../app/config/database.php';

try {
    $koneksi->query('SELECT 1');
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Healthcheck failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'database' => 'unavailable',
    ], JSON_UNESCAPED_SLASHES);
}
