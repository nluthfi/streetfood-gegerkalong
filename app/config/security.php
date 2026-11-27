<?php

require_once __DIR__ . '/app.php';

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan muat ulang halaman.');
    }
}

function post_text(string $key, int $maxLength = 255, bool $required = true): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($required && $value === '') {
        throw new InvalidArgumentException('Data wajib belum diisi.');
    }
    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException('Data terlalu panjang.');
    }

    return $value;
}

function post_int(string $key, int $min = 1, ?int $max = null): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    if ($value === false || $value === null || $value < $min || ($max !== null && $value > $max)) {
        throw new InvalidArgumentException('Nilai angka tidak valid.');
    }

    return $value;
}

function query_int(string $key, int $min = 1): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    if ($value === false || $value === null || $value < $min) {
        throw new InvalidArgumentException('Nilai ID tidak valid.');
    }

    return $value;
}

function upload_image(array $file, string $directory, int $maxBytes = 2097152): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload gambar gagal.');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new InvalidArgumentException('Ukuran gambar maksimal 2 MB.');
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        throw new InvalidArgumentException('Upload gambar tidak valid.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!is_string($mime) || !isset($extensions[$mime]) || @getimagesize($tmp) === false) {
        throw new InvalidArgumentException('Format gambar tidak didukung.');
    }

    $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Folder upload tidak tersedia.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmp, $directory . $filename)) {
        throw new RuntimeException('Gambar gagal disimpan.');
    }

    return $filename;
}

function remove_local_upload(string $directory, ?string $filename): void
{
    if (!$filename || preg_match('/^https?:\/\//i', $filename)) {
        return;
    }

    $filename = basename($filename);
    $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path)) {
        unlink($path);
    }
}

function admin_error(Throwable $error, string $fallback): void
{
    error_log($error->getMessage());
    http_response_code(400);
    exit($fallback);
}

function validate_price(int $price): int
{
    if ($price < 100 || $price > 10000000) {
        throw new InvalidArgumentException('Harga harus antara Rp100 dan Rp10.000.000.');
    }
    return $price;
}

function validate_halal(string $status): string
{
    $allowed = ['tersertifikasi', 'belum tersertifikasi', 'non halal'];
    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException('Status halal tidak valid.');
    }
    return $status;
}

function validate_time(string $time): string
{
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
        throw new InvalidArgumentException('Jam operasional tidak valid.');
    }
    return $time;
}

function validate_phone(string $phone): string
{
    if (!preg_match('/^[0-9+() -]{6,30}$/', $phone)) {
        throw new InvalidArgumentException('Nomor telepon tidak valid.');
    }
    return $phone;
}

/**
 * Checks whether a session-scoped rate-limit bucket has reached maxAttempts within windowSeconds.
 */
function is_rate_limited(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    $now = time();
    $entry = $_SESSION['rate_limits'][$bucket] ?? null;
    if (!is_array($entry) || ($now - (int) ($entry['start'] ?? 0)) > $windowSeconds) {
        $_SESSION['rate_limits'][$bucket] = ['count' => 0, 'start' => $now];
        return false;
    }

    return (int) ($entry['count'] ?? 0) >= $maxAttempts;
}

/**
 * Records an attempt in a session-scoped rate-limit bucket.
 */
function record_rate_attempt(string $bucket, int $windowSeconds): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    $now = time();
    $entry = $_SESSION['rate_limits'][$bucket] ?? null;
    if (!is_array($entry) || ($now - (int) ($entry['start'] ?? 0)) > $windowSeconds) {
        $entry = ['count' => 0, 'start' => $now];
    }
    $entry['count'] = (int) ($entry['count'] ?? 0) + 1;
    $_SESSION['rate_limits'][$bucket] = $entry;
}

/**
 * Clears a session-scoped rate-limit bucket after a successful action.
 */
function clear_rate_limit(string $bucket): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['rate_limits'][$bucket]);
    }
}

/**
 * Parses positive unique integer IDs from a POST array input.
 */
function post_int_list(string $key): array
{
    $raw = $_POST[$key] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    $ids = array_filter(array_map('intval', $raw), static fn(int $id): bool => $id > 0);
    return array_values(array_unique($ids));
}
