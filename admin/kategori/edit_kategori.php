<?php
require '../../app/config/security.php';
require_admin();
require '../../app/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: ' . app_url('admin/kategori/kategori.php?error=notfound'));
    exit;
}

$stmt = $koneksi->prepare('SELECT id_kategori, kategori_makanan FROM kategori WHERE id_kategori = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) {
    header('Location: ' . app_url('admin/kategori/kategori.php?error=notfound'));
    exit;
}

include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
?>

<main class="main-content">
    <div class="form-page">
        <div class="form-card">
            <div class="form-title">
                <h2>Edit Kategori</h2>
                <p>Ubah data kategori makanan</p>
            </div>

            <form action="proses_edit.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_kategori" value="<?= (int) $data['id_kategori'] ?>">

                <div class="mb-3">
                    <label for="kategori_makanan" class="form-label">Nama Kategori</label>
                    <input type="text"
                        id="kategori_makanan"
                        name="kategori_makanan"
                        class="form-control"
                        maxlength="100"
                        value="<?= e($data['kategori_makanan']) ?>"
                        required>
                </div>

                <div class="form-action">
                    <button type="submit" class="form-button primary" name="simpan">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Update
                    </button>

                    <a href="kategori.php" class="form-button secondary text-decoration-none">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>
