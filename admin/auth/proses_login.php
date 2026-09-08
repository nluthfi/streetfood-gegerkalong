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

$now = time();
$window = 15 * 60;
$maxAttempts = 5;
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'start' => $now];
if ($now - (int)$attempts['start'] > $window) {
    $attempts = ['count' => 0, 'start' => $now];
}
if ((int)$attempts['count'] >= $maxAttempts) {
    header('Location: login.php?error=rate');
    exit;
}

$stmt = $koneksi->prepare('SELECT id_user, username, password FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    unset($_SESSION['login_attempts']);
    session_regenerate_id(true);
    $_SESSION['login'] = true;
    $_SESSION['id_user'] = (int)$user['id_user'];
    $_SESSION['username'] = $user['username'];
    header('Location: ' . app_url('admin/dashboard.php'));
    exit;
}

$attempts['count']++;
$_SESSION['login_attempts'] = $attempts;
header('Location: login.php?error=invalid');
exit;
