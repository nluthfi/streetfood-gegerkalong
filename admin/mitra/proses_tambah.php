<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
try {
    $nama = post_text('nama_mitra', 255);
    $uploaded = upload_image($_FILES['logo'] ?? [], '../../public/assets/images/logo');
    $stmt = $koneksi->prepare('INSERT INTO mitra (nama_mitra, logo) VALUES (?, ?)');
    $stmt->bind_param('ss', $nama, $uploaded);
    $stmt->execute();
    header('Location: ' . app_url('admin/mitra/mitra.php?success=tambah'));
} catch (Throwable $error) {
    remove_local_upload('../../public/assets/images/logo', $uploaded);
    admin_error($error, 'Mitra gagal disimpan.');
}