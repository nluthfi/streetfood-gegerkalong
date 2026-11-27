<?php
require_once '../app/config/security.php';
start_secure_session();
verify_csrf();
require_once '../app/config/database.php';

$idToko = filter_input(INPUT_POST, 'id_toko', FILTER_VALIDATE_INT);

try {
    $idToko = post_int('id_toko');
    $reviewWindow = 10 * 60;
    if (is_rate_limited('review_submit', 5, $reviewWindow)) {
        header('Location: page_menu.php?id_toko=' . $idToko . '&review_error=rate#reviews');
        exit;
    }

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
    record_rate_attempt('review_submit', $reviewWindow);

    header('Location: page_menu.php?id_toko=' . $idToko . '&review=success#reviews');
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    if ($idToko && $idToko > 0) {
        header('Location: page_menu.php?id_toko=' . (int) $idToko . '&review_error=invalid#reviews');
        exit;
    }
    http_response_code(400);
    exit('Ulasan gagal dikirim.');
}
