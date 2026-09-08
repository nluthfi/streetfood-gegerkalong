<?php
include '../../app/config/security.php';
require_admin();
include '../../app/config/database.php';
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';

$id = query_int('id');

$stmt = $koneksi->prepare('SELECT * FROM menu WHERE id_menu = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$menu = $stmt->get_result()->fetch_assoc();

$kategori = mysqli_query($koneksi, "SELECT * FROM kategori");
$toko = mysqli_query($koneksi, "SELECT * FROM toko");

$all_rasa = mysqli_query($koneksi, "SELECT * FROM rasa");

$rasa_menu = [];
$stmt = $koneksi->prepare('SELECT id_rasa FROM menu_rasa WHERE id_menu = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$q = $stmt->get_result();

while($r = mysqli_fetch_assoc($q)){
    $rasa_menu[] = $r['id_rasa'];
}

?>

<main class="main-content">

<div class="form-page">
    <div class="form-card">

        <div class="form-title">
            <h2>Edit Menu</h2>
            <p>Ubah data menu street food</p>
        </div>

        <form action="proses_edit.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <input type="hidden" name="id_menu" value="<?= $menu['id_menu']; ?>">

            <!-- NAMA -->
            <div class="mb-3">
                <label class="form-label">Nama Menu</label>

                <input 
                    type="text" 
                    name="nama_menu"
                    value="<?= e($menu['nama_menu']); ?>"
                    class="form-control"
                    required
                >
            </div>

            <!-- FOTO LAMA -->
            <div class="mb-2">
                <label class="form-label">Foto Saat Ini</label>
                <br>

                <img 
                    src="<?= htmlspecialchars(asset_url($menu['foto_menu'], 'makanan'), ENT_QUOTES); ?>"
                    width="120"
                    style="border-radius:10px;"
                >
            </div>

            <!-- FOTO BARU -->
            <div class="mb-3">
                <label class="form-label">Ganti Foto (opsional)</label>

                <input 
                    type="file" 
                    name="foto_menu"
                    class="form-control"
                >
            </div>

            <!-- DESKRIPSI -->
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>

                <textarea 
                    name="deskripsi"
                    class="form-control"
                    rows="3"
                    required
                ><?= e($menu['deskripsi']); ?></textarea>
            </div>

            <!-- HARGA -->
            <div class="mb-3">
                <label class="form-label">Harga</label>

                <input 
                    type="number"
                    name="harga"
                    value="<?= $menu['harga']; ?>"
                    class="form-control"
                    required
                >
            </div>

            <!-- RASA -->
            <div class="mb-3">
                <label class="form-label">Rasa</label>

                <?php while($r = mysqli_fetch_assoc($all_rasa)){ ?>

                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="rasa[]"
                            value="<?= $r['id_rasa']; ?>"
                            <?= in_array($r['id_rasa'], $rasa_menu) ? 'checked' : ''; ?>
                        >

                        <label class="form-check-label">
                            <?= e($r['nama_rasa']); ?>
                        </label>
                    </div>

                <?php } ?>
            </div>

            <!-- KATEGORI -->
            <div class="mb-3">
                <label class="form-label">Kategori</label>

                <select name="id_kategori" class="form-select" required>

                    <?php while($k = mysqli_fetch_assoc($kategori)) { ?>

                        <option 
                            value="<?= $k['id_kategori']; ?>"
                            <?= $k['id_kategori'] == $menu['id_kategori'] ? 'selected' : ''; ?>
                        >
                            <?= e($k['kategori_makanan']); ?>
                        </option>

                    <?php } ?>

                </select>
            </div>

            <!-- TOKO -->
            <div class="mb-3">
                <label class="form-label">Toko</label>

                <select name="id_toko" class="form-select" required>

                    <?php while($t = mysqli_fetch_assoc($toko)) { ?>

                        <option 
                            value="<?= $t['id_toko']; ?>"
                            <?= $t['id_toko'] == $menu['id_toko'] ? 'selected' : ''; ?>
                        >
                            <?= e($t['nama_toko']); ?>
                        </option>

                    <?php } ?>

                </select>
            </div>

            <!-- BUTTON -->
            <div class="form-action">

                <button type="submit" class="form-button primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Update
                </button>

                <a href="menu.php" class="form-button secondary text-decoration-none">
                    Kembali
                </a>

            </div>

        </form>

    </div>
</div>

</main>

<?php include '../../app/views/layout/footer.php'; ?>
