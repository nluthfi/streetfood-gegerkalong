<?php
include '../../app/config/security.php';
require_admin();
include '../../app/config/database.php';
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';

$id = query_int('id');

$stmt = $koneksi->prepare('SELECT * FROM toko WHERE id_toko = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$toko = $stmt->get_result()->fetch_assoc();

$mitra = mysqli_query($koneksi, "SELECT * FROM mitra");
$metode = mysqli_query($koneksi, "SELECT * FROM bayar");

$mitra_selected = [];
$stmt = $koneksi->prepare('SELECT id_mitra FROM toko_mitra WHERE id_toko = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$q1 = $stmt->get_result();
while($r = mysqli_fetch_assoc($q1)){
    $mitra_selected[] = $r['id_mitra'];
}

$metode_selected = [];
$stmt = $koneksi->prepare('SELECT id_metode FROM metode_toko WHERE id_toko = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$q2 = $stmt->get_result();
while($r = mysqli_fetch_assoc($q2)){
    $metode_selected[] = $r['id_metode'];
}
?>

<main class="main-content">

    <div class="form-page">
        <div class="form-card">
            <div class="form-title">
                <h2>Edit Toko</h2>
                <p>
                    Ubah data street food beserta mitra dan metode pembayaran
                </p>
            </div>

            <form method="POST" action="proses_edit.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id_toko" value="<?= $toko['id_toko']; ?>">
                <!-- NAMA TOKO -->
                <div class="mb-3">
                    <label class="form-label">Nama Toko</label>
                    <input type="text" name="nama_toko" class="form-control" value="<?= e($toko['nama_toko']); ?>"
                        required>
                </div>

                <!-- FOTO (optional edit, kalau mau bisa dikembangin nanti) -->
                <div class="mb-3">

                    <label class="form-label">Foto Outlet</label>

                    <div class="mb-3 text-center">

                        <img src="<?= e(asset_url($toko['foto_outlet'], 'pict')); ?>" alt="<?= e($toko['nama_toko']); ?>"
                            id="previewFoto" style="
                width:100%;
                max-height:250px;
                object-fit:cover;
                border-radius:16px;
            ">

                    </div>

                    <input type="file" name="foto_outlet" class="form-control" accept="image/*"
                        onchange="previewImage(event)">

                </div>

                <!-- NOMOR -->
                <div class="mb-3">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="number" name="no_telepon" class="form-control" value="<?= $toko['no_telepon']; ?>"
                        required>
                </div>

                <!-- LOKASI -->
                <div class="mb-3">
                    <label class="form-label">Lokasi</label>
                    <textarea name="lokasi" class="form-control" rows="3" required><?= e($toko['lokasi']); ?></textarea>
                </div>

                <!-- JAM -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Buka</label>
                        <input type="time" name="jam_buka" class="form-control" value="<?= $toko['jam_buka']; ?>"
                            required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Tutup</label>
                        <input type="time" name="jam_tutup" class="form-control" value="<?= $toko['jam_tutup']; ?>"
                            required>
                    </div>
                </div>

                <!-- STATUS -->
                <div class="mb-4">
                    <label class="form-label">Status Halal</label>
                    <select name="status_halal" class="form-select" required>
                        <option value="tersertifikasi" <?= $toko['status_halal']=='tersertifikasi'?'selected':'' ?>>
                            Tersertifikasi
                        </option>

                        <option value="belum tersertifikasi"
                            <?= $toko['status_halal']=='belum tersertifikasi'?'selected':'' ?>>
                            Belum Tersertifikasi
                        </option>

                        <option value="non halal" <?= $toko['status_halal']=='non halal'?'selected':'' ?>>
                            Non Halal
                        </option>
                    </select>
                </div>

                <!-- MITRA -->
                <div class="mb-4">
                    <label class="section-title">Pilih Mitra :</label>

                    <div class="checkbox-container">

                        <?php while($m = mysqli_fetch_assoc($mitra)) { ?>

                        <label class="checkbox-card">
                            <input type="checkbox" name="mitra[]" value="<?= $m['id_mitra']; ?>"
                                <?= in_array($m['id_mitra'],$mitra_selected)?'checked':'' ?>>
                            <span><?= e($m['nama_mitra']); ?></span>
                        </label>
                        <?php } ?>
                    </div>
                </div>

                <!-- METODE -->
                <div class="mb-4">
                    <label class="section-title">Pilih Metode Pembayaran :</label>

                    <div class="checkbox-container">

                        <?php while($b = mysqli_fetch_assoc($metode)) { ?>

                        <label class="checkbox-card">
                            <input type="checkbox" name="metode[]" value="<?= $b['id_metode']; ?>"
                                <?= in_array($b['id_metode'],$metode_selected)?'checked':'' ?>>
                            <span><?= e($b['metode_pembayaran']); ?></span>
                        </label>
                        <?php } ?>
                    </div>
                </div>

                <!-- BUTTON -->
                <div class="form-action">
                    <button type="submit" name="update" class="form-button primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Update
                    </button>

                    <a href="toko.php" class="form-button secondary text-decoration-none">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function previewImage(event) {

    const reader = new FileReader();

    reader.onload = function() {
        document.getElementById('previewFoto').src = reader.result;
    }

    reader.readAsDataURL(event.target.files[0]);
}
</script>

<?php include '../../app/views/layout/footer.php'; ?>