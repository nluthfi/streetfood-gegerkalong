<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
$logoDir = '../../public/assets/images/logo';

try {
    $id = post_int('id_metode');
    $nama = post_text('metode_pembayaran', 255);

    $stmt = $koneksi->prepare('SELECT logo FROM bayar WHERE id_metode = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    if (!$old) {
        header('Location: ' . app_url('admin/bayar/bayar.php?error=notfound'));
        exit;
    }

    $uploaded = optional_upload_image('logo', $logoDir);

    if ($uploaded !== null) {
        $stmt = $koneksi->prepare('UPDATE bayar SET metode_pembayaran = ?, logo = ? WHERE id_metode = ?');
        $stmt->bind_param('ssi', $nama, $uploaded, $id);
    } else {
        $stmt = $koneksi->prepare('UPDATE bayar SET metode_pembayaran = ? WHERE id_metode = ?');
        $stmt->bind_param('si', $nama, $id);
    }
    $stmt->execute();

    if ($uploaded !== null) {
        remove_local_upload($logoDir, $old['logo']);
    }

    header('Location: ' . app_url('admin/bayar/bayar.php?success=edit'));
} catch (Throwable $error) {
    remove_local_upload($logoDir, $uploaded);
    admin_error($error, 'Metode pembayaran gagal diperbarui.');
}
