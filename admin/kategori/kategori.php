<?php
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/views/layout/alerts.php';
include '../../app/config/database.php';

$result = $koneksi->query(
    'SELECT k.id_kategori, k.kategori_makanan, COUNT(m.id_menu) AS jumlah_menu
     FROM kategori k
     LEFT JOIN menu m ON m.id_kategori = k.id_kategori
     GROUP BY k.id_kategori, k.kategori_makanan
     ORDER BY k.id_kategori ASC'
);
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$successMessages = [
    'tambah' => 'Data kategori berhasil ditambahkan.',
    'edit' => 'Data kategori berhasil diperbarui.',
    'hapus' => 'Data kategori berhasil dihapus.',
];
$errorMessages = [
    'used' => 'Kategori tidak dapat dihapus karena masih digunakan oleh menu.',
    'notfound' => 'Data kategori tidak ditemukan.',
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
                <h4>Data Kategori</h4>
                <p>Kategori makanan yang tersedia (<?= number_format(count($categories)) ?> kategori)</p>
            </div>

            <a href="tambah_kategori.php" class="btn btn-primary">
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
                            <th>Kategori Makanan</th>
                            <th>Digunakan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($categories as $index => $row):
                            $idKategori = (int) $row['id_kategori'];
                            $jumlahMenu = (int) $row['jumlah_menu'];
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($row['kategori_makanan']) ?></td>
                            <td><span class="usage-pill"><?= number_format($jumlahMenu) ?> menu</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="edit_kategori.php?id=<?= $idKategori ?>" class="btn-action edit" aria-label="Edit kategori <?= e($row['kategori_makanan']) ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <form action="hapus_kategori.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $idKategori ?>">
                                        <button type="submit" class="btn-action delete" aria-label="Hapus kategori <?= e($row['kategori_makanan']) ?>">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data kategori.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>
