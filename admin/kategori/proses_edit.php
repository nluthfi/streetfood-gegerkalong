<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id_kategori');
    $kategori = post_text('kategori_makanan', 100);
    $stmt = $koneksi->prepare('UPDATE kategori SET kategori_makanan = ? WHERE id_kategori = ?');
    $stmt->bind_param('si', $kategori, $id);
    $stmt->execute();
    header('Location: ' . app_url('admin/kategori/kategori.php?success=edit'));
} catch (Throwable $error) {
    admin_error($error, 'Kategori gagal diperbarui.');
}