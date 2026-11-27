<?php
require_once '../app/config/app.php';
require_admin();

include '../app/views/layout/header.php';
include '../app/views/layout/sidebar.php';
include '../app/config/database.php';

$stats = $koneksi->query("
    SELECT
        (SELECT COUNT(*) FROM mitra) AS total_mitra,
        (SELECT COUNT(*) FROM toko) AS total_toko,
        (SELECT COUNT(*) FROM menu) AS total_menu,
        (SELECT COUNT(*) FROM bayar) AS total_pembayaran,
        (SELECT COUNT(*) FROM review) AS total_ulasan
")->fetch_assoc();

$orderStats = $koneksi->query("
    SELECT
        COUNT(*) AS total_pesanan,
        COALESCE(SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END), 0) AS pesanan_baru,
        COALESCE(SUM(CASE WHEN status IN ('baru', 'diproses') THEN 1 ELSE 0 END), 0) AS pesanan_aktif,
        COALESCE(SUM(CASE WHEN status <> 'dibatalkan' THEN total ELSE 0 END), 0) AS total_pendapatan
    FROM pesanan
")->fetch_assoc();

$recentOrders = $koneksi->query("
    SELECT p.id_pesanan, p.nama_pemesan, p.no_telepon, p.total, p.status, p.tanggal_pesanan, t.nama_toko
    FROM pesanan p
    JOIN toko t ON t.id_toko = p.id_toko
    ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$statusMeta = [
    'baru' => ['label' => 'Baru', 'class' => 'order-status-baru'],
    'diproses' => ['label' => 'Diproses', 'class' => 'order-status-diproses'],
    'selesai' => ['label' => 'Selesai', 'class' => 'order-status-selesai'],
    'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'order-status-dibatalkan'],
];
?>

<main class="main-content dashboard-page">
    <section class="dashboard-hero">
        <div class="dashboard-hero-inner">
            <div>
                <h1>Dashboard Admin</h1>
                <p>Kelola data Street Food Gegerkalong, pesanan masuk, dan katalog kuliner</p>
            </div>
            <div class="dashboard-quick-actions">
                <a href="<?= app_url('admin/pesanan/pesanan.php') ?>" class="btn btn-primary">
                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                    Kelola Pesanan<?= (int) $orderStats['pesanan_baru'] > 0 ? ' (' . (int) $orderStats['pesanan_baru'] . ' Baru)' : '' ?>
                </a>
                <a href="<?= app_url('admin/toko/tambah_toko.php') ?>" class="btn-hero-secondary">
                    <i class="fa-solid fa-store" aria-hidden="true"></i>
                    Tambah Toko
                </a>
                <a href="<?= app_url('admin/menu/tambah_menu.php') ?>" class="btn-hero-secondary">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Tambah Menu
                </a>
            </div>
        </div>
    </section>

    <div class="dashboard-stats">
        <article class="dashboard-card<?= (int) $orderStats['pesanan_baru'] > 0 ? ' dashboard-card-highlight' : '' ?>">
            <div>
                <h6>Pesanan Baru</h6>
                <h3><?= number_format((int) $orderStats['pesanan_baru']) ?></h3>
                <span class="dashboard-card-sub"><?= number_format((int) $orderStats['pesanan_aktif']) ?> pesanan aktif</span>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-bell" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Total Pesanan</h6>
                <h3><?= number_format((int) $orderStats['total_pesanan']) ?></h3>
                <span class="dashboard-card-sub">Semua status pesanan</span>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Total Pendapatan</h6>
                <h3>Rp <?= number_format((int) $orderStats['total_pendapatan'], 0, ',', '.') ?></h3>
                <span class="dashboard-card-sub">Tidak termasuk dibatalkan</span>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Total Ulasan</h6>
                <h3><?= number_format((int) $stats['total_ulasan']) ?></h3>
                <span class="dashboard-card-sub">Ulasan pelanggan toko</span>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Total Toko</h6>
                <h3><?= number_format((int) $stats['total_toko']) ?></h3>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-store" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Total Menu</h6>
                <h3><?= number_format((int) $stats['total_menu']) ?></h3>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Total Mitra</h6>
                <h3><?= number_format((int) $stats['total_mitra']) ?></h3>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
            </div>
        </article>

        <article class="dashboard-card">
            <div>
                <h6>Pembayaran</h6>
                <h3><?= number_format((int) $stats['total_pembayaran']) ?></h3>
            </div>
            <div class="card-icon">
                <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
            </div>
        </article>
    </div>

    <section class="table-container mt-4">
        <div class="table-header">
            <div>
                <h4>Pesanan Terbaru</h4>
                <p>8 pesanan terakhir dari pelanggan</p>
            </div>
            <a href="<?= app_url('admin/pesanan/pesanan.php') ?>" class="btn btn-primary">
                Lihat Semua Pesanan
            </a>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Toko</th>
                        <th>Pemesan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order):
                        $meta = $statusMeta[$order['status']] ?? ['label' => $order['status'], 'class' => 'order-status-baru'];
                    ?>
                    <tr>
                        <td>#<?= (int) $order['id_pesanan'] ?></td>
                        <td><?= e($order['nama_toko']) ?></td>
                        <td>
                            <?= e($order['nama_pemesan']) ?><br>
                            <small class="text-muted"><?= e($order['no_telepon']) ?></small>
                        </td>
                        <td>Rp <?= number_format((int) $order['total'], 0, ',', '.') ?></td>
                        <td>
                            <span class="order-status-badge <?= e($meta['class']) ?>">
                                <?= e($meta['label']) ?>
                            </span>
                        </td>
                        <td><?= e(date('d/m/Y H:i', strtotime($order['tanggal_pesanan']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentOrders): ?>
                    <tr>
                        <td colspan="6" class="text-center">Belum ada pesanan masuk.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../app/views/layout/footer.php'; ?>
