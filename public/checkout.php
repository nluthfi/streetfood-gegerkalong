<?php
require_once '../app/config/security.php';
start_secure_session();
require_once '../app/config/database.php';
require_once '../app/views/layout/public_error.php';

$idToko = filter_input(INPUT_GET, 'id_toko', FILTER_VALIDATE_INT);
$rawCart = (string) ($_GET['cart'] ?? '');
if (!$idToko || $idToko < 1 || $rawCart === '') {
    header('Location: index.php');
    exit;
}

try {
    $cart = json_decode($rawCart, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($cart) || count($cart) === 0 || count($cart) > 50) {
        throw new InvalidArgumentException('Keranjang tidak valid.');
    }

    $ids = [];
    foreach ($cart as $id => $item) {
        $menuId = filter_var($id, FILTER_VALIDATE_INT);
        $qty = is_array($item) ? filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT) : false;
        if (!$menuId || $qty === false || $qty < 1 || $qty > 99) {
            throw new InvalidArgumentException('Isi keranjang tidak valid.');
        }
        $ids[] = $menuId;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids)) . 'i';
    $stmt = $koneksi->prepare(
        "SELECT m.id_menu, m.nama_menu, m.harga, m.foto_menu, m.id_toko,
                t.nama_toko, t.lokasi, t.jam_buka, t.jam_tutup
         FROM menu m
         JOIN toko t ON t.id_toko = m.id_toko
         WHERE m.id_menu IN ($placeholders) AND m.id_toko = ?"
    );
    $bindIds = $ids;
    $bindIds[] = $idToko;
    $stmt->bind_param($types, ...$bindIds);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (count($found) !== count($ids)) {
        throw new InvalidArgumentException('Ada menu yang sudah tidak tersedia di toko ini.');
    }

    $items = [];
    $total = 0;
    $totalQty = 0;
    $storeName = $found[0]['nama_toko'] ?? 'Toko';
    $storeLokasi = $found[0]['lokasi'] ?? '';
    $jamBuka = !empty($found[0]['jam_buka']) ? substr((string) $found[0]['jam_buka'], 0, 5) : '';
    $jamTutup = !empty($found[0]['jam_tutup']) ? substr((string) $found[0]['jam_tutup'], 0, 5) : '';

    foreach ($found as $row) {
        $qty = (int) $cart[$row['id_menu']]['qty'];
        $subtotal = (int) $row['harga'] * $qty;
        $row['qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $items[] = $row;
        $total += $subtotal;
        $totalQty += $qty;
    }

    $payStmt = $koneksi->prepare(
        'SELECT b.metode_pembayaran
         FROM metode_toko mt
         JOIN bayar b ON b.id_metode = mt.id_metode
         WHERE mt.id_toko = ?
         ORDER BY b.metode_pembayaran ASC'
    );
    $payStmt->bind_param('i', $idToko);
    $payStmt->execute();
    $paymentMethods = $payStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    render_public_error(
        'Checkout Tidak Valid',
        $e->getMessage(),
        'page_menu.php?id_toko=' . (int) $idToko,
        'Kembali ke Toko'
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – <?= e($storeName) ?></title>
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
    <a href="page_menu.php?id_toko=<?= (int) $idToko ?>" class="back-btn">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
    </a>
    <div class="topbar-right">
        <a class="btn-nav-link" href="cek_pesanan.php">
            <i class="fas fa-receipt" aria-hidden="true"></i>
            <span>Cek Pesanan</span>
        </a>
    </div>
</header>
<main class="checkout-wrap">
    <div class="checkout-card">
        <h1>Checkout</h1>
        <p><?= e($storeName) ?></p>
        <?php if ($storeLokasi !== '' || ($jamBuka !== '' && $jamTutup !== '')): ?>
        <div class="checkout-store-meta">
            <?php if ($storeLokasi !== ''): ?>
            <span><i class="fas fa-map-marker-alt" style="color:var(--accent2)" aria-hidden="true"></i> <?= e($storeLokasi) ?></span>
            <?php endif; ?>
            <?php if ($jamBuka !== '' && $jamTutup !== ''): ?>
            <span><i class="fas fa-clock" style="color:var(--blue)" aria-hidden="true"></i> <?= e($jamBuka) ?> – <?= e($jamTutup) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($paymentMethods): ?>
        <div class="checkout-payment-list">
            <span class="checkout-payment-label">Pembayaran diterima:</span>
            <?php foreach ($paymentMethods as $pm): ?>
            <span class="bayar-badge">
                <i class="fas fa-wallet" style="font-size:11px" aria-hidden="true"></i>
                <?= e($pm['metode_pembayaran']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="checkout-card">
        <h2>Ringkasan Pesanan (<?= number_format($totalQty) ?> item)</h2>
        <?php foreach ($items as $item):
            $imgSrc = '';
            if (!empty($item['foto_menu'])) {
                $imgSrc = (str_starts_with($item['foto_menu'], 'http://') || str_starts_with($item['foto_menu'], 'https://'))
                    ? $item['foto_menu']
                    : 'assets/images/makanan/' . rawurlencode($item['foto_menu']);
            }
        ?>
        <div class="checkout-item">
            <div class="checkout-item-thumb">
                <?php if ($imgSrc !== ''): ?>
                <img src="<?= e($imgSrc) ?>" alt="<?= e($item['nama_menu']) ?>"
                    onerror="this.parentElement.innerHTML='<i class=\'fas fa-utensils\' aria-hidden=\'true\'></i>'">
                <?php else: ?>
                <i class="fas fa-utensils" aria-hidden="true"></i>
                <?php endif; ?>
            </div>
            <div class="grow">
                <strong><?= e($item['nama_menu']) ?></strong>
                <div><?= (int) $item['qty'] ?> × Rp <?= number_format((int) $item['harga'], 0, ',', '.') ?></div>
            </div>
            <strong>Rp <?= number_format((int) $item['subtotal'], 0, ',', '.') ?></strong>
        </div>
        <?php endforeach; ?>
        <div class="checkout-total">
            <span>Total</span>
            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="checkout-card">
        <h2>Data Pemesan</h2>
        <form class="checkout-form" action="process_order.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_toko" value="<?= (int) $idToko ?>">
            <input type="hidden" name="cart" value="<?= e($rawCart) ?>">
            <label for="nama">Nama</label>
            <input id="nama" name="nama" maxlength="100" placeholder="Nama lengkap pemesan..." required>
            <label for="telepon">Nomor Telepon</label>
            <input id="telepon" name="telepon" maxlength="30" pattern="[0-9+() \-]{6,30}" placeholder="Contoh: 081234567890" required>
            <label for="catatan">Catatan</label>
            <textarea id="catatan" name="catatan" maxlength="500" rows="4" placeholder="Opsional (misal: pedas sedang, tanpa bawang)"></textarea>
            <div class="checkout-actions">
                <a href="page_menu.php?id_toko=<?= (int) $idToko ?>">Kembali ke Toko</a>
                <button type="submit">Buat Pesanan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
