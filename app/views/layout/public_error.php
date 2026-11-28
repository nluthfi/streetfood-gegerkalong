<?php

/**
 * Renders a styled public error page for storefront validation/request failures.
 */
function render_public_error(
    string $title,
    string $message,
    string $backUrl = 'index.php',
    string $backLabel = 'Kembali',
    int $statusCode = 400
): never {
    http_response_code($statusCode);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> – Street Food Gegerkalong</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="logo">
            <div class="logo-icon">🍜</div>
            <div class="logo-text">STREET FOOD<small>Gegerkalong</small></div>
        </a>
        <div class="topbar-right">
            <a class="btn-nav-link" href="cek_pesanan.php">
                <i class="fas fa-receipt" aria-hidden="true"></i>
                <span>Cek Pesanan</span>
            </a>
        </div>
    </header>
    <main class="public-error-wrap">
        <div class="public-error-card" role="alert">
            <div class="public-error-icon">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            </div>
            <h1><?= e($title) ?></h1>
            <p><?= e($message) ?></p>
            <div class="public-error-actions">
                <a class="track-btn-primary" href="<?= e($backUrl) ?>">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span><?= e($backLabel) ?></span>
                </a>
                <a class="track-btn-secondary" href="index.php">Daftar Toko</a>
            </div>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}
