<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $kategori = post_text('kategori_makanan', 100);
    $stmt = $koneksi->prepare('INSERT INTO kategori (kategori_makanan) VALUES (?)');
    $stmt->bind_param('s', $kategori);
    $stmt->execute();
    header('Location: ' . app_url('admin/kategori/kategori.php?success=tambah'));
} catch (Throwable $error) {
    admin_error($error, 'Kategori gagal disimpan.');
}