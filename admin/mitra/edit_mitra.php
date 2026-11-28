<?php
require '../../app/config/security.php';
require_admin();
require '../../app/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: ' . app_url('admin/mitra/mitra.php?error=notfound'));
    exit;
}

$stmt = $koneksi->prepare('SELECT id_mitra, nama_mitra, logo FROM mitra WHERE id_mitra = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$mitra = $stmt->get_result()->fetch_assoc();
if (!$mitra) {
    header('Location: ' . app_url('admin/mitra/mitra.php?error=notfound'));
    exit;
}

include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/views/layout/media.php';
?>

<main class="main-content">
    <div class="form-page">
        <div class="form-card">
            <div class="form-title">
                <h2>Edit Mitra</h2>
                <p>Perbarui nama atau logo mitra Street Food</p>
            </div>

            <form action="proses_edit.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id_mitra" value="<?= (int) $mitra['id_mitra'] ?>">

                <div class="mb-3">
                    <label for="nama_mitra" class="form-label">Nama Mitra</label>
                    <input type="text"
                        id="nama_mitra"
                        name="nama_mitra"
                        class="form-control"
                        maxlength="255"
                        value="<?= e($mitra['nama_mitra']) ?>"
                        required>
                </div>

                <?php render_logo_edit_field($mitra['logo'] ?? '', 'Logo ' . $mitra['nama_mitra'], 'fa-building-columns'); ?>

                <div class="form-action">
                    <button type="submit" class="form-button primary">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Update
                    </button>
                    <a href="mitra.php" class="form-button secondary text-decoration-none">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>
