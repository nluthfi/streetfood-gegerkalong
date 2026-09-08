<?php

function app_base_url(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    return str_starts_with($scriptName, '/tubes/') ? '/tubes' : '';
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function app_url(string $path = ''): string
{
    return app_base_url() . '/' . ltrim($path, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset_url(string $path, string $directory): string
{
    if (preg_match('/^https?:\\/\\//i', $path)) {
        return $path;
    }

    return app_url('public/assets/images/' . trim($directory, '/') . '/' . rawurlencode($path));
}

function require_admin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    if (empty($_SESSION['login'])) {
        header('Location: ' . app_url('admin/auth/login.php'));
        exit;
    }
}