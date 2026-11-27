<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

$uploaded = null;
try {
    $nama = post_text('nama_menu', 255);
    $deskripsi = post_text('deskripsi', 5000);
    $harga = validate_price(post_int('harga'));
    $kategori = post_int('id_kategori');
    $toko = post_int('id_toko');
    $rasa = post_int_list('rasa');

    $checkKat = $koneksi->prepare('SELECT 1 FROM kategori WHERE id_kategori = ? LIMIT 1');
    $checkKat->bind_param('i', $kategori);
    $checkKat->execute();
    if ($checkKat->get_result()->num_rows === 0) {
        throw new InvalidArgumentException('Kategori tidak ditemukan.');
    }

    $checkToko = $koneksi->prepare('SELECT 1 FROM toko WHERE id_toko = ? LIMIT 1');
    $checkToko->bind_param('i', $toko);
    $checkToko->execute();
    if ($checkToko->get_result()->num_rows === 0) {
        throw new InvalidArgumentException('Toko tidak ditemukan.');
    }

    $uploaded = upload_image($_FILES['foto_menu'] ?? [], '../../public/assets/images/makanan');

    mysqli_begin_transaction($koneksi);
    $stmt = $koneksi->prepare('INSERT INTO menu (nama_menu, foto_menu, deskripsi, harga, id_kategori, id_toko) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssiii', $nama, $uploaded, $deskripsi, $harga, $kategori, $toko);
    $stmt->execute();
    $idMenu = $koneksi->insert_id;

    $relation = $koneksi->prepare('INSERT INTO menu_rasa (id_menu, id_rasa) VALUES (?, ?)');
    foreach ($rasa as $idRasa) {
        if ($idRasa > 0) {
            $relation->bind_param('ii', $idMenu, $idRasa);
            $relation->execute();
        }
    }
    mysqli_commit($koneksi);
    header('Location: ' . app_url('admin/menu/menu.php?success=tambah'));
} catch (Throwable $error) {
    if ($koneksi->in_transaction) {
        mysqli_rollback($koneksi);
    }
    remove_local_upload('../../public/assets/images/makanan', $uploaded);
    admin_error($error, 'Menu gagal disimpan.');
}
