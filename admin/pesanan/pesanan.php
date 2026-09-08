<?php
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/config/database.php';

$orders = $koneksi->query("SELECT p.*, t.nama_toko FROM pesanan p JOIN toko t ON t.id_toko=p.id_toko ORDER BY p.tanggal_pesanan DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
?>
<main class="main-content">
<div class="mb-4"><h2 class="fw-bold">STREET FOOD</h2></div>
<div class="table-container"><div class="table-header"><div><h4>Pesanan</h4><p>Maksimal 200 pesanan terbaru</p></div></div>
<div class="table-responsive"><div class="table-scroll"><table class="custom-table"><thead><tr><th>ID</th><th>Toko</th><th>Pemesan</th><th>Total</th><th>Status</th><th>Waktu</th><th>Detail</th></tr></thead><tbody>
<?php foreach($orders as $order): ?>
<tr><td>#<?= (int)$order['id_pesanan'] ?></td><td><?= e($order['nama_toko']) ?></td><td><?= e($order['nama_pemesan']) ?><br><small><?= e($order['no_telepon']) ?></small></td><td>Rp <?= number_format($order['total'],0,',','.') ?></td><td><form action="update_status.php" method="POST" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$order['id_pesanan'] ?>"><select name="status" class="form-select form-select-sm"><option value="baru" <?= $order['status']==='baru'?'selected':'' ?>>Baru</option><option value="diproses" <?= $order['status']==='diproses'?'selected':'' ?>>Diproses</option><option value="selesai" <?= $order['status']==='selesai'?'selected':'' ?>>Selesai</option><option value="dibatalkan" <?= $order['status']==='dibatalkan'?'selected':'' ?>>Dibatalkan</option></select><button class="btn btn-sm btn-primary">Simpan</button></form></td><td><?= e(date('d/m/Y H:i', strtotime($order['tanggal_pesanan']))) ?></td><td><button class="btn btn-detail" data-bs-toggle="modal" data-bs-target="#order<?= (int)$order['id_pesanan'] ?>">Detail</button></td></tr>
<?php endforeach; if(!$orders): ?><tr><td colspan="7" class="text-center">Belum ada pesanan.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div></main>
<?php foreach($orders as $order): $stmt=$koneksi->prepare('SELECT nama_menu,harga_satuan,jumlah,subtotal FROM detail_pesanan WHERE id_pesanan=? ORDER BY id_detail');$stmt->bind_param('i',$order['id_pesanan']);$stmt->execute();$details=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); ?>
<div class="modal fade" id="order<?= (int)$order['id_pesanan'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4"><div class="modal-header"><h5 class="modal-title">Pesanan #<?= (int)$order['id_pesanan'] ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p><strong><?= e($order['nama_pemesan']) ?></strong> · <?= e($order['no_telepon']) ?></p><?php foreach($details as $d): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= e($d['nama_menu']) ?> × <?= (int)$d['jumlah'] ?></span><strong>Rp <?= number_format($d['subtotal'],0,',','.') ?></strong></div><?php endforeach; ?><p class="mt-3 mb-0"><strong>Catatan:</strong> <?= e($order['catatan'] ?: '-') ?></p></div></div></div></div>
<?php endforeach; ?>
<?php include '../../app/views/layout/footer.php'; ?>
