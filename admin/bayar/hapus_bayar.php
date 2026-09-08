<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $stmt = $koneksi->prepare('SELECT 1 FROM metode_toko WHERE id_metode = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ' . app_url('admin/bayar/bayar.php?error=used'));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM bayar WHERE id_metode = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . app_url('admin/bayar/bayar.php?success=hapus'));
} catch (Throwable $error) {
    admin_error($error, 'Metode pembayaran gagal dihapus.');
}