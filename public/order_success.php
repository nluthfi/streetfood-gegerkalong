<?php
require_once '../app/config/app.php';
require_once '../app/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$toko = filter_input(INPUT_GET, 'toko', FILTER_VALIDATE_INT);
if (!$id || !$toko) {
    header('Location: index.php');
    exit;
}

$stmt = $koneksi->prepare(
    'SELECT p.*, t.nama_toko FROM pesanan p JOIN toko t ON t.id_toko = p.id_toko WHERE p.id_pesanan = ? AND p.id_toko = ?'
);
$stmt->bind_param('ii', $id, $toko);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    header('Location: index.php');
    exit;
}

$detailStmt = $koneksi->prepare(
    'SELECT nama_menu, harga_satuan, jumlah, subtotal FROM detail_pesanan WHERE id_pesanan = ? ORDER BY id_detail ASC'
);
$detailStmt->bind_param('i', $id);
$detailStmt->execute();
$details = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$statusLabels = [
    'baru' => 'Menunggu Konfirmasi',
    'diproses' => 'Sedang Diproses',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
$statusText = $statusLabels[$order['status']] ?? $order['status'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pesanan #<?= (int)$order['id_pesanan'] ?> - <?= e($order['nama_toko']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .success-wrap{max-width:640px;margin:48px auto;padding:0 20px}
        .success-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:32px;box-shadow:0 12px 36px rgba(0,0,0,.25)}
        .success-icon{width:56px;height:56px;margin:0 auto 16px;border-radius:50%;background:rgba(34,197,94,.15);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800}
        .success-header{text-align:center;margin-bottom:24px}
        .success-header h1{font-size:24px;margin-bottom:6px;color:var(--text)}
        .success-header p{color:var(--text-muted);font-size:14px}
        .success-meta{display:grid;gap:10px;padding:16px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);margin-bottom:18px;font-size:14px}
        .success-row{display:flex;justify-content:space-between;gap:12px}
        .success-row span:first-child{color:var(--text-muted)}
        .success-status{display:inline-block;padding:3px 10px;border-radius:999px;background:rgba(240,165,0,.15);color:var(--accent);font-weight:700;font-size:12px}
        .success-items{display:grid;gap:10px;margin-bottom:18px}
        .success-item{display:flex;justify-content:space-between;gap:12px;font-size:14px}
        .success-total{display:flex;justify-content:space-between;font-size:18px;font-weight:800;padding-top:14px;border-top:1px solid var(--border);color:var(--accent)}
        .success-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}
        .success-actions a{flex:1;min-width:160px;text-align:center;padding:13px 18px;border-radius:12px;text-decoration:none;font-weight:700}
        .btn-back-store{background:var(--accent);color:#0f1623}
        .btn-back-store:hover{background:#e09800}
        .btn-back-home{background:var(--bg);border:1px solid var(--border);color:var(--text)}
    </style>
</head>
<body>
<header class="topbar">
    <a href="index.php" class="logo">
        <div class="logo-icon">🍜</div>
        <div class="logo-text">STREET FOOD<small>Gegerkalong</small></div>
    </a>
</header>
<main class="success-wrap">
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">✓</div>
            <h1>Pesanan Berhasil Dibuat</h1>
            <p><?= e($order['nama_toko']) ?></p>
        </div>

        <div class="success-meta">
            <div class="success-row">
                <span>Nomor Pesanan</span>
                <strong>#<?= (int)$order['id_pesanan'] ?></strong>
            </div>
            <div class="success-row">
                <span>Pemesan</span>
                <strong><?= e($order['nama_pemesan']) ?></strong>
            </div>
            <div class="success-row">
                <span>Telepon</span>
                <strong><?= e($order['no_telepon']) ?></strong>
            </div>
            <div class="success-row">
                <span>Status</span>
                <span class="success-status"><?= e($statusText) ?></span>
            </div>
            <?php if (!empty($order['catatan'])): ?>
            <div class="success-row">
                <span>Catatan</span>
                <span><?= e($order['catatan']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($details): ?>
        <div class="success-items">
            <?php foreach ($details as $d): ?>
            <div class="success-item">
                <span><?= e($d['nama_menu']) ?> × <?= (int)$d['jumlah'] ?></span>
                <strong>Rp <?= number_format((int)$d['subtotal'], 0, ',', '.') ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="success-total">
            <span>Total</span>
            <span>Rp <?= number_format((int)$order['total'], 0, ',', '.') ?></span>
        </div>

        <div class="success-actions">
            <a class="btn-back-store" href="page_menu.php?id_toko=<?= (int)$toko ?>">Kembali ke Toko</a>
            <a class="btn-back-home" href="index.php">Daftar Toko</a>
        </div>
    </div>
</main>
<script>
try {
    localStorage.removeItem('streetfood_cart_toko_<?= (int)$toko ?>');
} catch (_) {}
</script>
</body>
</html>
