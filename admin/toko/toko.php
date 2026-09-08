<?php include '../../app/views/layout/header.php'; ?>
<?php include '../../app/views/layout/sidebar.php'; ?>
<?php include '../../app/config/database.php'; ?>

<?php
$result = mysqli_query($koneksi, "
    SELECT * FROM toko
");

$toko = [];
while($row = mysqli_fetch_assoc($result)){
    $toko[] = $row;
}

$i = 1;
?>

<!-- MAIN CONTENT -->
<main class="main-content">

    <div class="mb-4">
        <h2 class="fw-bold">STREET FOOD</h2>
    </div>

    <div class="table-container">

        <!-- HEADER -->
        <div class="table-header">
            <div>
                <h4>Data Toko</h4>
                <p>Street Food yang terdaftar</p>
            </div>

            <a href="tambah_toko.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Data
            </a>
        </div>

        <!-- TABLE -->
        <div class="table-responsive">
            <div class="table-scroll">

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Toko</th>
                            <th>Jam Operasional</th>
                            <th>Detail</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach($toko as $row) { ?>

                        <tr>
                            <td><?= $i++; ?></td>

                            <td><?= e($row['nama_toko']); ?></td>

                            <td>
                                <?= e($row['jam_buka']); ?> - <?= e($row['jam_tutup']); ?>
                            </td>

                            <!-- DETAIL -->
                            <td>
                                <button class="btn btn-detail" data-bs-toggle="modal"
                                    data-bs-target="#detail<?= $row['id_toko']; ?>">
                                    <i class="fa-solid fa-circle-info"></i> Detail
                                </button>
                            </td>

                            <!-- ACTION -->
                            <td>
                                <a href="edit_toko.php?id=<?= $row['id_toko']; ?>">
                                    <button class="btn-action edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </a>

                                <form action="hapus_toko.php" method="POST" style="display:inline"
                                    onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id_toko']; ?>">
                                    <button type="submit" class="btn-action delete" aria-label="Hapus toko">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <?php } ?>

                    </tbody>
                </table>

            </div>
        </div>

    </div>

</main>

<?php foreach($toko as $row) { ?>

<?php
$id = (int) $row['id_toko'];

$mitraStmt = $koneksi->prepare('SELECT m.nama_mitra FROM toko_mitra tm JOIN mitra m ON tm.id_mitra = m.id_mitra WHERE tm.id_toko = ?');
$mitraStmt->bind_param('i', $id);
$mitraStmt->execute();
$mitraQuery = $mitraStmt->get_result();

$metodeStmt = $koneksi->prepare('SELECT mp.metode_pembayaran FROM metode_toko mt JOIN bayar mp ON mt.id_metode = mp.id_metode WHERE mt.id_toko = ?');
$metodeStmt->bind_param('i', $id);
$metodeStmt->execute();
$metodeQuery = $metodeStmt->get_result();
?>
<div class="modal fade" id="detail<?= $row['id_toko']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Detail Toko</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="detail-item">
                    <span class="detail-label">Nama Toko</span>
                    <span class="detail-value"><?= e($row['nama_toko']); ?></span>
                </div>

                <div class="detail-item text-center mb-3">

                    <img src="<?= e(asset_url($row['foto_outlet'], 'pict')); ?>" alt="<?= e($row['nama_toko']); ?>" style="
                        width:100%;
                        max-height:220px;
                        object-fit:cover;
                        border-radius:16px;
                    ">

                </div>


                <div class="detail-item">
                    <span class="detail-label">Lokasi</span>
                    <span class="detail-value"><?= e($row['lokasi']); ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Nomor Telepon</span>
                    <span class="detail-value"><?= e($row['no_telepon']); ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Mitra</span>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php while($m = mysqli_fetch_assoc($mitraQuery)) { ?>
                        <span class="custom-badge">
                            <?= e($m['nama_mitra']); ?>
                        </span>
                        <?php } ?>
                    </div>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Metode Pembayaran</span>
                    <div class="d-flex gap-2 flex-wrap">

                        <?php while($mp = mysqli_fetch_assoc($metodeQuery)) { ?>
                        <span class="custom-badge">
                            <?= $mp['metode_pembayaran']; ?>
                        </span>
                        <?php } ?>

                    </div>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Status Halal</span>

                    <?php if($row['status_halal'] == 'tersertifikasi') { ?>
                    <span class="badge bg-success">Tersertifikasi</span>

                    <?php } else if($row['status_halal'] == 'belum tersertifikasi') { ?>
                    <span class="badge bg-warning">Belum Tersertifikasi</span>

                    <?php } else { ?>
                    <span class="badge bg-danger">Non Halal</span>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php include '../../app/views/layout/footer.php'; ?>