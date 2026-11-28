<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $stmt = $koneksi->prepare('SELECT logo FROM bayar WHERE id_metode = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $bayar = $stmt->get_result()->fetch_assoc();
    if (!$bayar) {
        header('Location: ' . app_url('admin/bayar/bayar.php?error=notfound'));
        exit;
    }

    $usedStmt = $koneksi->prepare('SELECT 1 FROM metode_toko WHERE id_metode = ? LIMIT 1');
    $usedStmt->bind_param('i', $id);
    $usedStmt->execute();
    if ($usedStmt->get_result()->num_rows > 0) {
        header('Location: ' . app_url('admin/bayar/bayar.php?error=used'));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM bayar WHERE id_metode = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    remove_local_upload('../../public/assets/images/logo', $bayar['logo']);
    header('Location: ' . app_url('admin/bayar/bayar.php?success=hapus'));
} catch (Throwable $error) {
    admin_error($error, 'Metode pembayaran gagal dihapus.');
}
