<?php
require_once '../app/config/security.php';
start_secure_session();
verify_csrf();
require_once '../app/config/database.php';

try {
    $idToko = post_int('id_toko');
    $nama = post_text('nama_pengulas', 100);
    $rating = post_int('rating', 1, 5);
    $komentar = post_text('komentar', 1000);

    $stmt = $koneksi->prepare('SELECT id_toko FROM toko WHERE id_toko = ?');
    $stmt->bind_param('i', $idToko);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new InvalidArgumentException('Toko tidak ditemukan.');
    }

    $stmt = $koneksi->prepare('INSERT INTO review (id_toko, nama_pengulas, rating, komentar) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isis', $idToko, $nama, $rating, $komentar);
    $stmt->execute();

    header('Location: page_menu.php?id_toko=' . $idToko . '&review=success#reviews');
    exit;
} catch (Throwable $e) {
    admin_error($e, 'Ulasan gagal dikirim.');
}
