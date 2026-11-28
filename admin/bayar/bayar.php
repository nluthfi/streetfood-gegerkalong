<?php
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/views/layout/alerts.php';
include '../../app/views/layout/media.php';
include '../../app/config/database.php';

$result = $koneksi->query(
    'SELECT b.id_metode, b.metode_pembayaran, b.logo, COUNT(mt.id_toko) AS jumlah_toko
     FROM bayar b
     LEFT JOIN metode_toko mt ON mt.id_metode = b.id_metode
     GROUP BY b.id_metode, b.metode_pembayaran, b.logo
     ORDER BY b.metode_pembayaran ASC, b.id_metode ASC'
);
$methods = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$successMessages = [
    'tambah' => 'Metode pembayaran berhasil ditambahkan.',
    'edit' => 'Metode pembayaran berhasil diperbarui.',
    'hapus' => 'Metode pembayaran berhasil dihapus.',
];
$errorMessages = [
    'used' => 'Metode pembayaran tidak dapat dihapus karena masih digunakan oleh toko.',
    'notfound' => 'Metode pembayaran tidak ditemukan.',
];
?>

<main class="main-content">
    <div class="mb-4">
        <h2 class="fw-bold">STREET FOOD</h2>
    </div>

    <?php render_admin_alerts($successMessages, $errorMessages); ?>

    <div class="table-container">
        <div class="table-header">
            <div>
                <h4>Data Metode Pembayaran</h4>
                <p>Metode pembayaran yang tersedia (<?= number_format(count($methods)) ?> metode)</p>
            </div>

            <a href="tambah_bayar.php" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Data
            </a>
        </div>

        <div class="table-responsive">
            <div class="table-scroll">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Logo</th>
                            <th>Metode Pembayaran</th>
                            <th>Digunakan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($methods as $index => $row):
                            $idMetode = (int) $row['id_metode'];
                            $jumlahToko = (int) $row['jumlah_toko'];
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <?php render_logo_thumbnail($row['logo'] ?? '', 'Logo ' . $row['metode_pembayaran'], 'fa-money-bill-wave'); ?>
                            </td>
                            <td><?= e($row['metode_pembayaran']) ?></td>
                            <td><span class="usage-pill"><?= number_format($jumlahToko) ?> toko</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="edit_bayar.php?id=<?= $idMetode ?>" class="btn-action edit" aria-label="Edit metode <?= e($row['metode_pembayaran']) ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <form action="hapus_bayar.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $idMetode ?>">
                                        <button type="submit" class="btn-action delete" aria-label="Hapus metode <?= e($row['metode_pembayaran']) ?>">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$methods): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data metode pembayaran.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>
