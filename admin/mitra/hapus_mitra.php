<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $stmt = $koneksi->prepare('SELECT logo FROM mitra WHERE id_mitra = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $mitra = $stmt->get_result()->fetch_assoc();
    if (!$mitra) {
        header('Location: ' . app_url('admin/mitra/mitra.php?error=notfound'));
        exit;
    }

    $usedStmt = $koneksi->prepare('SELECT 1 FROM toko_mitra WHERE id_mitra = ? LIMIT 1');
    $usedStmt->bind_param('i', $id);
    $usedStmt->execute();
    if ($usedStmt->get_result()->num_rows > 0) {
        header('Location: ' . app_url('admin/mitra/mitra.php?error=used'));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM mitra WHERE id_mitra = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    remove_local_upload('../../public/assets/images/logo', $mitra['logo']);
    header('Location: ' . app_url('admin/mitra/mitra.php?success=hapus'));
} catch (Throwable $error) {
    admin_error($error, 'Mitra gagal dihapus.');
}
