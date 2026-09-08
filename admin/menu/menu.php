<?php
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/config/database.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
$keyword = trim((string)($_GET['q'] ?? ''));
$like = "%{$keyword}%";

if ($keyword !== '') {
    $countStmt = $koneksi->prepare('SELECT COUNT(*) total FROM menu m LEFT JOIN toko t ON t.id_toko=m.id_toko WHERE m.nama_menu LIKE ? OR t.nama_toko LIKE ?');
    $countStmt->bind_param('ss', $like, $like);
    $countStmt->execute();
    $totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $stmt = $koneksi->prepare('SELECT m.id_menu,m.nama_menu,m.foto_menu,m.deskripsi,m.harga,k.kategori_makanan,t.nama_toko FROM menu m LEFT JOIN kategori k ON k.id_kategori=m.id_kategori LEFT JOIN toko t ON t.id_toko=m.id_toko WHERE m.nama_menu LIKE ? OR t.nama_toko LIKE ? ORDER BY m.id_menu DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('ssii', $like, $like, $perPage, $offset);
} else {
    $totalRows = (int)$koneksi->query('SELECT COUNT(*) total FROM menu')->fetch_assoc()['total'];
    $stmt = $koneksi->prepare('SELECT m.id_menu,m.nama_menu,m.foto_menu,m.deskripsi,m.harga,k.kategori_makanan,t.nama_toko FROM menu m LEFT JOIN kategori k ON k.id_kategori=m.id_kategori LEFT JOIN toko t ON t.id_toko=m.id_toko ORDER BY m.id_menu DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$menus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { header('Location: menu.php?page='.$totalPages.'&q='.rawurlencode($keyword)); exit; }
?>
<main class="main-content">
<div class="mb-4"><h2 class="fw-bold">STREET FOOD</h2></div>
<div class="table-container">
<div class="table-header"><div><h4>Data Menu</h4><p><?= number_format($totalRows) ?> menu, tampil <?= $perPage ?> per halaman</p></div><a class="btn btn-primary" href="tambah_menu.php"><i class="fa-solid fa-plus"></i> Tambah Data</a></div>
<form method="GET" class="d-flex gap-2 mb-3"><input class="form-control" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Cari menu atau toko..."><button class="btn btn-outline-secondary">Cari</button></form>
<div class="table-responsive"><div class="table-scroll"><table class="custom-table"><thead><tr><th>No</th><th>Nama Menu</th><th>Toko</th><th>Detail</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($menus as $i=>$row): ?><tr><td><?= $offset+$i+1 ?></td><td><?= e($row['nama_menu']) ?></td><td><?= e($row['nama_toko']) ?></td><td><button class="btn btn-detail" data-bs-toggle="modal" data-bs-target="#detail<?= (int)$row['id_menu'] ?>"><i class="fa-solid fa-circle-info"></i> Detail</button></td><td><a href="edit_menu.php?id=<?= (int)$row['id_menu'] ?>" class="btn-action edit" aria-label="Edit menu"><i class="fa-solid fa-pen"></i></a><form action="hapus_menu.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id_menu'] ?>"><button type="submit" class="btn-action delete" aria-label="Hapus menu"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endforeach; ?>
<?php if(!$menus): ?><tr><td colspan="5" class="text-center">Tidak ada menu ditemukan.</td></tr><?php endif; ?>
</tbody></table></div></div>
<nav class="mt-3"><ul class="pagination flex-wrap"><?php for($p=1;$p<=$totalPages;$p++): ?><li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?page=<?= $p ?>&q=<?= rawurlencode($keyword) ?>"><?= $p ?></a></li><?php endfor; ?></ul></nav>
</div></main>
<?php foreach($menus as $row): ?>
<div class="modal fade" id="detail<?= (int)$row['id_menu'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4"><div class="modal-header border-0"><h5 class="modal-title fw-bold">Detail Menu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="text-center mb-3"><?php if($row['foto_menu']): ?><img src="<?= e(asset_url($row['foto_menu'],'makanan')) ?>" width="120" height="120" style="object-fit:cover;border-radius:10px" alt="<?= e($row['nama_menu']) ?>" loading="lazy"><?php endif; ?></div><div class="detail-item"><span class="detail-label">Nama Menu</span><span class="detail-value"><?= e($row['nama_menu']) ?></span></div><div class="detail-item"><span class="detail-label">Deskripsi</span><span class="detail-value"><?= e($row['deskripsi']) ?></span></div><div class="detail-item"><span class="detail-label">Harga</span><span class="detail-value">Rp <?= number_format($row['harga'],0,',','.') ?></span></div><div class="detail-item"><span class="detail-label">Kategori</span><span class="detail-value"><?= e($row['kategori_makanan']) ?></span></div><div class="detail-item"><span class="detail-label">Toko</span><span class="detail-value"><?= e($row['nama_toko']) ?></span></div></div></div></div></div>
<?php endforeach; ?>
<?php include '../../app/views/layout/footer.php'; ?>
