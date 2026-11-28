<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
$logoDir = '../../public/assets/images/logo';

try {
    $id = post_int('id_mitra');
    $nama = post_text('nama_mitra', 255);

    $stmt = $koneksi->prepare('SELECT logo FROM mitra WHERE id_mitra = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    if (!$old) {
        header('Location: ' . app_url('admin/mitra/mitra.php?error=notfound'));
        exit;
    }

    $uploaded = optional_upload_image('logo', $logoDir);

    if ($uploaded !== null) {
        $stmt = $koneksi->prepare('UPDATE mitra SET nama_mitra = ?, logo = ? WHERE id_mitra = ?');
        $stmt->bind_param('ssi', $nama, $uploaded, $id);
    } else {
        $stmt = $koneksi->prepare('UPDATE mitra SET nama_mitra = ? WHERE id_mitra = ?');
        $stmt->bind_param('si', $nama, $id);
    }
    $stmt->execute();

    if ($uploaded !== null) {
        remove_local_upload($logoDir, $old['logo']);
    }

    header('Location: ' . app_url('admin/mitra/mitra.php?success=edit'));
} catch (Throwable $error) {
    remove_local_upload($logoDir, $uploaded);
    admin_error($error, 'Mitra gagal diperbarui.');
}
