<?php
require '../../app/config/security.php';
require '../../app/config/database.php';
start_secure_session();
verify_csrf();

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
if ($username === '' || mb_strlen($username) > 255 || $password === '') {
    header('Location: login.php?error=invalid');
    exit;
}

$window = 15 * 60;
$maxAttempts = 5;
if (is_rate_limited('login_attempts', $maxAttempts, $window)) {
    header('Location: login.php?error=rate');
    exit;
}

$stmt = $koneksi->prepare('SELECT id_user, username, password FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    clear_rate_limit('login_attempts');
    session_regenerate_id(true);
    $_SESSION['login'] = true;
    $_SESSION['id_user'] = (int)$user['id_user'];
    $_SESSION['username'] = $user['username'];
    header('Location: ' . app_url('admin/dashboard.php'));
    exit;
}

record_rate_attempt('login_attempts', $window);
header('Location: login.php?error=invalid');
exit;
