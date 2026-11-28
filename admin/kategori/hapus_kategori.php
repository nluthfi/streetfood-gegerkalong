<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');

    $existsStmt = $koneksi->prepare('SELECT 1 FROM kategori WHERE id_kategori = ? LIMIT 1');
    $existsStmt->bind_param('i', $id);
    $existsStmt->execute();
    if ($existsStmt->get_result()->num_rows === 0) {
        header('Location: ' . app_url('admin/kategori/kategori.php?error=notfound'));
        exit;
    }

    $stmt = $koneksi->prepare('SELECT 1 FROM menu WHERE id_kategori = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ' . app_url('admin/kategori/kategori.php?error=used'));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM kategori WHERE id_kategori = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . app_url('admin/kategori/kategori.php?success=hapus'));
} catch (Throwable $error) {
    admin_error($error, 'Kategori gagal dihapus.');
}
