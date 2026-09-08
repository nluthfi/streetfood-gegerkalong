<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
try {
    $id = post_int('id_menu');
    $nama = post_text('nama_menu', 255);
    $deskripsi = post_text('deskripsi', 5000);
    $harga = validate_price(post_int('harga'));
    $kategori = post_int('id_kategori');
    $toko = post_int('id_toko');
    $rasa = array_map('intval', (array) ($_POST['rasa'] ?? []));

    $stmt = $koneksi->prepare('SELECT foto_menu FROM menu WHERE id_menu = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    if (!$old) {
        throw new InvalidArgumentException('Menu tidak ditemukan.');
    }

    $hasUpload = isset($_FILES['foto_menu']) && ($_FILES['foto_menu']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload) {
        $uploaded = upload_image($_FILES['foto_menu'], '../../public/assets/images/makanan');
    }

    mysqli_begin_transaction($koneksi);
    if ($uploaded) {
        $stmt = $koneksi->prepare('UPDATE menu SET nama_menu = ?, foto_menu = ?, deskripsi = ?, harga = ?, id_kategori = ?, id_toko = ? WHERE id_menu = ?');
        $stmt->bind_param('sssiiii', $nama, $uploaded, $deskripsi, $harga, $kategori, $toko, $id);
    } else {
        $stmt = $koneksi->prepare('UPDATE menu SET nama_menu = ?, deskripsi = ?, harga = ?, id_kategori = ?, id_toko = ? WHERE id_menu = ?');
        $stmt->bind_param('ssiiii', $nama, $deskripsi, $harga, $kategori, $toko, $id);
    }
    $stmt->execute();

    $stmt = $koneksi->prepare('DELETE FROM menu_rasa WHERE id_menu = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $relation = $koneksi->prepare('INSERT INTO menu_rasa (id_menu, id_rasa) VALUES (?, ?)');
    foreach ($rasa as $idRasa) {
        if ($idRasa > 0) {
            $relation->bind_param('ii', $id, $idRasa);
            $relation->execute();
        }
    }
    mysqli_commit($koneksi);

    if ($uploaded) {
        remove_local_upload('../../public/assets/images/makanan', $old['foto_menu']);
    }
    header('Location: ' . app_url('admin/menu/menu.php?success=edit'));
} catch (Throwable $error) {
    if ($koneksi->in_transaction) {
        mysqli_rollback($koneksi);
    }
    remove_local_upload('../../public/assets/images/makanan', $uploaded);
    admin_error($error, 'Menu gagal diperbarui.');
}
