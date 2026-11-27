<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');

    $returnRating = filter_input(INPUT_POST, 'return_rating', FILTER_VALIDATE_INT);
    $returnToko = filter_input(INPUT_POST, 'return_toko', FILTER_VALIDATE_INT);
    $returnPage = filter_input(INPUT_POST, 'return_page', FILTER_VALIDATE_INT);
    $returnQ = post_text('return_q', 100, false);

    $query = [];
    if ($returnRating !== false && $returnRating !== null && $returnRating >= 1 && $returnRating <= 5) {
        $query['rating'] = $returnRating;
    }
    if ($returnToko !== false && $returnToko !== null && $returnToko >= 1) {
        $query['toko'] = $returnToko;
    }
    if ($returnQ !== '') {
        $query['q'] = $returnQ;
    }
    if ($returnPage !== false && $returnPage !== null && $returnPage >= 2) {
        $query['page'] = $returnPage;
    }

    $check = $koneksi->prepare('SELECT id_review FROM review WHERE id_review = ? LIMIT 1');
    $check->bind_param('i', $id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        $query['error'] = 'notfound';
        header('Location: ' . app_url('admin/ulasan/ulasan.php?' . http_build_query($query)));
        exit;
    }

    $stmt = $koneksi->prepare('DELETE FROM review WHERE id_review = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $query['success'] = 'hapus';
    header('Location: ' . app_url('admin/ulasan/ulasan.php?' . http_build_query($query)));
    exit;
} catch (Throwable $error) {
    admin_error($error, 'Ulasan gagal dihapus.');
}
