<?php
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/views/layout/alerts.php';
include '../../app/views/layout/media.php';
include '../../app/config/database.php';

$result = $koneksi->query(
    'SELECT m.id_mitra, m.nama_mitra, m.logo, COUNT(tm.id_toko) AS jumlah_toko
     FROM mitra m
     LEFT JOIN toko_mitra tm ON tm.id_mitra = m.id_mitra
     GROUP BY m.id_mitra, m.nama_mitra, m.logo
     ORDER BY m.nama_mitra ASC, m.id_mitra ASC'
);
$mitras = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$successMessages = [
    'tambah' => 'Data mitra berhasil ditambahkan.',
    'edit' => 'Data mitra berhasil diperbarui.',
    'hapus' => 'Data mitra berhasil dihapus.',
];
$errorMessages = [
    'used' => 'Mitra tidak dapat dihapus karena masih digunakan oleh toko.',
    'notfound' => 'Data mitra tidak ditemukan.',
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
                <h4>Data Mitra</h4>
                <p>Mitra Street Food yang tersedia (<?= number_format(count($mitras)) ?> mitra)</p>
            </div>

            <a href="tambah_mitra.php" class="btn btn-primary">
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
                            <th>Nama Mitra</th>
                            <th>Digunakan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($mitras as $index => $row):
                            $idMitra = (int) $row['id_mitra'];
                            $jumlahToko = (int) $row['jumlah_toko'];
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <?php render_logo_thumbnail($row['logo'] ?? '', 'Logo ' . $row['nama_mitra'], 'fa-building-columns'); ?>
                            </td>
                            <td><?= e($row['nama_mitra']) ?></td>
                            <td><span class="usage-pill"><?= number_format($jumlahToko) ?> toko</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="edit_mitra.php?id=<?= $idMitra ?>" class="btn-action edit" aria-label="Edit mitra <?= e($row['nama_mitra']) ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <form action="hapus_mitra.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $idMitra ?>">
                                        <button type="submit" class="btn-action delete" aria-label="Hapus mitra <?= e($row['nama_mitra']) ?>">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$mitras): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data mitra.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>
