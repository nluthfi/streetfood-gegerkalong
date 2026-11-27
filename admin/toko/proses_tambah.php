<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
try {
    $nama = post_text('nama_toko', 255);
    $lokasi = post_text('lokasi', 255);
    $jamBuka = post_text('jam_buka', 8);
    $jamTutup = post_text('jam_tutup', 8);
    $status = validate_halal(post_text('status_halal', 40));
    $telepon = validate_phone(post_text('no_telepon', 30));
    validate_time($jamBuka);
    validate_time($jamTutup);
    $mitra = post_int_list('mitra');
    $metode = post_int_list('metode');
    $uploaded = upload_image($_FILES['foto_outlet'] ?? [], '../../public/assets/images/pict');

    mysqli_begin_transaction($koneksi);
    $stmt = $koneksi->prepare('INSERT INTO toko (nama_toko, foto_outlet, lokasi, jam_buka, jam_tutup, status_halal, no_telepon, rating) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)');
    $stmt->bind_param('sssssss', $nama, $uploaded, $lokasi, $jamBuka, $jamTutup, $status, $telepon);
    $stmt->execute();
    $idToko = $koneksi->insert_id;

    $relation = $koneksi->prepare('INSERT INTO toko_mitra (id_toko, id_mitra) VALUES (?, ?)');
    foreach ($mitra as $idMitra) {
        $relation->bind_param('ii', $idToko, $idMitra);
        $relation->execute();
    }
    $relation = $koneksi->prepare('INSERT INTO metode_toko (id_toko, id_metode) VALUES (?, ?)');
    foreach ($metode as $idMetode) {
        $relation->bind_param('ii', $idToko, $idMetode);
        $relation->execute();
    }
    mysqli_commit($koneksi);
    header('Location: ' . app_url('admin/toko/toko.php?success=tambah'));
} catch (Throwable $error) {
    if ($koneksi->in_transaction) {
        mysqli_rollback($koneksi);
    }
    remove_local_upload('../../public/assets/images/pict', $uploaded);
    admin_error($error, 'Toko gagal disimpan.');
}