<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
try {
    $id = post_int('id_toko');
    $nama = post_text('nama_toko', 255);
    $telepon = validate_phone(post_text('no_telepon', 30));
    $lokasi = post_text('lokasi', 255);
    $jamBuka = post_text('jam_buka', 8);
    $jamTutup = post_text('jam_tutup', 8);
    $status = validate_halal(post_text('status_halal', 40));
    validate_time($jamBuka);
    validate_time($jamTutup);
    $mitra = post_int_list('mitra');
    $metode = post_int_list('metode');

    $stmt = $koneksi->prepare('SELECT foto_outlet FROM toko WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    if (!$old) {
        throw new InvalidArgumentException('Toko tidak ditemukan.');
    }
    $hasUpload = isset($_FILES['foto_outlet']) && ($_FILES['foto_outlet']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload) {
        $uploaded = upload_image($_FILES['foto_outlet'], '../../public/assets/images/pict');
    }

    mysqli_begin_transaction($koneksi);
    if ($uploaded) {
        $stmt = $koneksi->prepare('UPDATE toko SET nama_toko = ?, no_telepon = ?, lokasi = ?, jam_buka = ?, jam_tutup = ?, status_halal = ?, foto_outlet = ? WHERE id_toko = ?');
        $stmt->bind_param('sssssssi', $nama, $telepon, $lokasi, $jamBuka, $jamTutup, $status, $uploaded, $id);
    } else {
        $stmt = $koneksi->prepare('UPDATE toko SET nama_toko = ?, no_telepon = ?, lokasi = ?, jam_buka = ?, jam_tutup = ?, status_halal = ? WHERE id_toko = ?');
        $stmt->bind_param('ssssssi', $nama, $telepon, $lokasi, $jamBuka, $jamTutup, $status, $id);
    }
    $stmt->execute();

    $stmt = $koneksi->prepare('DELETE FROM toko_mitra WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $relation = $koneksi->prepare('INSERT INTO toko_mitra (id_toko, id_mitra) VALUES (?, ?)');
    foreach ($mitra as $idMitra) {
        $relation->bind_param('ii', $id, $idMitra);
        $relation->execute();
    }
    $stmt = $koneksi->prepare('DELETE FROM metode_toko WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $relation = $koneksi->prepare('INSERT INTO metode_toko (id_toko, id_metode) VALUES (?, ?)');
    foreach ($metode as $idMetode) {
        $relation->bind_param('ii', $id, $idMetode);
        $relation->execute();
    }
    mysqli_commit($koneksi);

    if ($uploaded) {
        remove_local_upload('../../public/assets/images/pict', $old['foto_outlet']);
    }
    header('Location: ' . app_url('admin/toko/toko.php?success=edit'));
} catch (Throwable $error) {
    if ($koneksi->in_transaction) {
        mysqli_rollback($koneksi);
    }
    remove_local_upload('../../public/assets/images/pict', $uploaded);
    admin_error($error, 'Toko gagal diperbarui.');
}