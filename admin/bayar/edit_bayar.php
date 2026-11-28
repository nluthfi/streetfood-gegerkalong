<?php
require '../../app/config/security.php';
require_admin();
require '../../app/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: ' . app_url('admin/bayar/bayar.php?error=notfound'));
    exit;
}

$stmt = $koneksi->prepare('SELECT id_metode, metode_pembayaran, logo FROM bayar WHERE id_metode = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$bayar = $stmt->get_result()->fetch_assoc();
if (!$bayar) {
    header('Location: ' . app_url('admin/bayar/bayar.php?error=notfound'));
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
                <h2>Edit Metode Pembayaran</h2>
                <p>Perbarui nama atau logo metode pembayaran</p>
            </div>

            <form action="proses_edit.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id_metode" value="<?= (int) $bayar['id_metode'] ?>">

                <div class="mb-3">
                    <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                    <input type="text"
                        id="metode_pembayaran"
                        name="metode_pembayaran"
                        class="form-control"
                        maxlength="255"
                        value="<?= e($bayar['metode_pembayaran']) ?>"
                        required>
                </div>

                <?php render_logo_edit_field($bayar['logo'] ?? '', 'Logo ' . $bayar['metode_pembayaran'], 'fa-money-bill-wave'); ?>

                <div class="form-action">
                    <button type="submit" class="form-button primary">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Update
                    </button>
                    <a href="bayar.php" class="form-button secondary text-decoration-none">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>
