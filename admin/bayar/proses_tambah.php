<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
try {
    $nama = post_text('metode_pembayaran', 255);
    $uploaded = upload_image($_FILES['logo'] ?? [], '../../public/assets/images/logo');
    $stmt = $koneksi->prepare('INSERT INTO bayar (metode_pembayaran, logo) VALUES (?, ?)');
    $stmt->bind_param('ss', $nama, $uploaded);
    $stmt->execute();
    header('Location: ' . app_url('admin/bayar/bayar.php?success=tambah'));
} catch (Throwable $error) {
    remove_local_upload('../../public/assets/images/logo', $uploaded);
    admin_error($error, 'Metode pembayaran gagal disimpan.');
}