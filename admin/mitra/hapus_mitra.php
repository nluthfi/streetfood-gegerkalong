<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $stmt = $koneksi->prepare('SELECT 1 FROM toko_mitra WHERE id_mitra = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ' . app_url('admin/mitra/mitra.php?error=used'));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM mitra WHERE id_mitra = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . app_url('admin/mitra/mitra.php?success=hapus'));
} catch (Throwable $error) {
    admin_error($error, 'Mitra gagal dihapus.');
}