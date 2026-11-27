<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $stmt = $koneksi->prepare('SELECT foto_menu FROM menu WHERE id_menu = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) {
        throw new InvalidArgumentException('Menu tidak ditemukan.');
    }

    $usedStmt = $koneksi->prepare('SELECT 1 FROM detail_pesanan WHERE id_menu = ? LIMIT 1');
    $usedStmt->bind_param('i', $id);
    $usedStmt->execute();
    if ($usedStmt->get_result()->num_rows > 0) {
        header('Location: ' . app_url('admin/menu/menu.php?error=used'));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM menu WHERE id_menu = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    remove_local_upload('../../public/assets/images/makanan', $data['foto_menu']);
    header('Location: ' . app_url('admin/menu/menu.php?success=hapus'));
} catch (Throwable $error) {
    admin_error($error, 'Menu gagal dihapus.');
}