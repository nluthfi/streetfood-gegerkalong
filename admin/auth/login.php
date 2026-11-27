<?php
require_once '../../app/config/app.php';
require_once '../../app/config/security.php';
start_secure_session();

if (!empty($_SESSION['login'])) {
    header('Location: ' . app_url('admin/dashboard.php'));
    exit;
}

$errorMessages = [
    'invalid' => 'Username atau password salah. Silakan coba lagi.',
    'rate' => 'Terlalu banyak percobaan login. Silakan tunggu 15 menit lagi.',
];
$errorCode = (string) ($_GET['error'] ?? '');
$errorMessage = $errorMessages[$errorCode] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Street Food Gegerkalong</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('public/assets/auth/login.css?v=20260909') ?>">
</head>
<body>

    <div class="login-container d-flex align-items-center justify-content-center">
        <div class="login-box card p-4 p-sm-5 text-center">
            
            <div class="login-logo mb-3">
                🍜 STREET FOOD <span>GEGERKALONG</span>
            </div>
            
            <h2 class="h5 text-white fw-bold mb-1">Selamat Datang</h2>
            <p class="subtitle mb-4">Silakan masuk ke akun Anda</p>

            <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger py-2 px-3 small text-start mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                <?= e($errorMessage) ?>
            </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <?= csrf_field() ?>
                
                <div class="mb-3 text-start">
                    <label for="username" class="form-label text-light-gray small fw-medium mb-2">Username atau Email</label>
                    <div class="input-group-kustom">
                        <i class="bi bi-person-fill input-icon"></i>
                        <input type="text" class="form-control-kustom" id="username" name="username" autocomplete="username" placeholder="Masukkan username Anda..." required>
                    </div>
                </div>

                <div class="mb-4 text-start">
                    <label for="password" class="form-label text-light-gray small fw-medium mb-2">Password</label>
                    <div class="input-group-kustom">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" class="form-control-kustom" id="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandi..." required>
                    </div>
                </div>
                <button type="submit" class="btn-accent-kustom w-100 fw-bold py-3 mb-2">Masuk</button>
            </form>

            <div class="mt-3">
                <a href="<?= app_url('public/') ?>" class="text-secondary-gray text-decoration-none">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>