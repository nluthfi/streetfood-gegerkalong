<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $stmt = $koneksi->prepare('SELECT foto_outlet FROM toko WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $toko = $stmt->get_result()->fetch_assoc();
    if (!$toko) {
        throw new InvalidArgumentException('Toko tidak ditemukan.');
    }

    foreach (['menu' => 'id_toko', 'review' => 'id_toko', 'pesanan' => 'id_toko'] as $table => $column) {
        $stmt = $koneksi->prepare("SELECT 1 FROM `$table` WHERE `$column` = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            header('Location: ' . app_url('admin/toko/toko.php?error=used'));
            exit;
        }
    }

    mysqli_begin_transaction($koneksi);
    $stmt = $koneksi->prepare('DELETE FROM toko_mitra WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt = $koneksi->prepare('DELETE FROM metode_toko WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt = $koneksi->prepare('DELETE FROM toko WHERE id_toko = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    mysqli_commit($koneksi);

    remove_local_upload('../../public/assets/images/pict', $toko['foto_outlet']);
    header('Location: ' . app_url('admin/toko/toko.php?success=hapus'));
} catch (Throwable $error) {
    if ($koneksi->in_transaction) {
        mysqli_rollback($koneksi);
    }
    admin_error($error, 'Toko gagal dihapus.');
}