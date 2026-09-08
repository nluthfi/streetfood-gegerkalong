<?php
require_once '../app/config/security.php';
start_secure_session();
require_once '../app/config/database.php';

$idToko = filter_input(INPUT_GET, 'id_toko', FILTER_VALIDATE_INT);
$rawCart = (string)($_GET['cart'] ?? '');
if (!$idToko || $idToko < 1 || $rawCart === '') {
    header('Location: index.php'); exit;
}

try {
    $cart = json_decode($rawCart, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($cart) || count($cart) === 0 || count($cart) > 50) throw new InvalidArgumentException('Keranjang tidak valid.');

    $ids = [];
    foreach ($cart as $id => $item) {
        $menuId = filter_var($id, FILTER_VALIDATE_INT);
        $qty = is_array($item) ? filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT) : false;
        if (!$menuId || $qty === false || $qty < 1 || $qty > 99) throw new InvalidArgumentException('Isi keranjang tidak valid.');
        $ids[] = $menuId;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $koneksi->prepare("SELECT m.id_menu, m.nama_menu, m.harga, m.foto_menu, m.id_toko, t.nama_toko FROM menu m JOIN toko t ON t.id_toko = m.id_toko WHERE m.id_menu IN ($placeholders) AND m.id_toko = ?");
    $bindIds = $ids; $bindIds[] = $idToko; $types .= 'i';
    $stmt->bind_param($types, ...$bindIds);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (count($found) !== count($ids)) throw new InvalidArgumentException('Ada menu yang sudah tidak tersedia.');

    $items = [];
    $total = 0;
    $storeName = $found[0]['nama_toko'] ?? 'Toko';
    foreach ($found as $row) {
        $qty = (int)$cart[$row['id_menu']]['qty'];
        $subtotal = (int)$row['harga'] * $qty;
        $row['qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $items[] = $row;
        $total += $subtotal;
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit(e($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - <?= e($storeName) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.checkout-wrap{max-width:900px;margin:40px auto;padding:0 20px}.checkout-card{background:var(--card-bg,#fff);border-radius:20px;padding:24px;box-shadow:0 10px 35px rgba(0,0,0,.08);margin-bottom:20px}.checkout-item{display:flex;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid #eee}.checkout-item img{width:70px;height:70px;object-fit:cover;border-radius:12px}.checkout-item .grow{flex:1}.checkout-form{display:grid;gap:14px}.checkout-form label{font-weight:700}.checkout-form input,.checkout-form textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:10px}.checkout-total{display:flex;justify-content:space-between;font-weight:800;font-size:20px;margin-top:20px}.checkout-actions{display:flex;gap:10px;flex-wrap:wrap}.checkout-actions a,.checkout-actions button{border:0;border-radius:10px;padding:12px 18px;text-decoration:none;cursor:pointer}.checkout-actions a{background:#eee;color:#222}.checkout-actions button{background:#111;color:#fff}
</style>
</head>
<body>
<header class="topbar"><a href="index.php" class="logo"><div class="logo-icon">🍜</div><div class="logo-text">STREET FOOD<small>Gegerkalong</small></div></a></header>
<main class="checkout-wrap">
<div class="checkout-card"><h1>Checkout</h1><p><?= e($storeName) ?></p></div>
<div class="checkout-card">
<?php foreach ($items as $item): ?>
<div class="checkout-item"><div><?php if ($item['foto_menu']): ?><img src="<?= e(str_starts_with($item['foto_menu'],'http') ? $item['foto_menu'] : 'assets/images/makanan/'.$item['foto_menu']) ?>" alt=""><?php endif; ?></div><div class="grow"><strong><?= e($item['nama_menu']) ?></strong><div><?= $item['qty'] ?> × Rp <?= number_format($item['harga'],0,',','.') ?></div></div><strong>Rp <?= number_format($item['subtotal'],0,',','.') ?></strong></div>
<?php endforeach; ?>
<div class="checkout-total"><span>Total</span><span>Rp <?= number_format($total,0,',','.') ?></span></div>
</div>
<div class="checkout-card">
<h2>Data Pemesan</h2>
<form class="checkout-form" action="process_order.php" method="POST">
<?= csrf_field() ?><input type="hidden" name="id_toko" value="<?= (int)$idToko ?>"><input type="hidden" name="cart" value="<?= e($rawCart) ?>">
<label for="nama">Nama</label><input id="nama" name="nama" maxlength="100" required>
<label for="telepon">Nomor Telepon</label><input id="telepon" name="telepon" maxlength="30" pattern="[0-9+() \-]{6,30}" required>
<label for="catatan">Catatan</label><textarea id="catatan" name="catatan" maxlength="500" rows="4" placeholder="Opsional"></textarea>
<div class="checkout-actions"><a href="page_menu.php?id_toko=<?= (int)$idToko ?>">Kembali</a><button type="submit">Buat Pesanan</button></div>
</form>
</div>
</main>
</body></html>
