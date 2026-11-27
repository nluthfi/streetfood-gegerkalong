<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';

try {
    $id = post_int('id');
    $status = post_text('status', 20);
    $allowedStatuses = ['baru', 'diproses', 'selesai', 'dibatalkan'];
    if (!in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Status tidak valid.');
    }

    $check = $koneksi->prepare('SELECT id_pesanan FROM pesanan WHERE id_pesanan = ? LIMIT 1');
    $check->bind_param('i', $id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        throw new InvalidArgumentException('Pesanan tidak ditemukan.');
    }

    $stmt = $koneksi->prepare('UPDATE pesanan SET status = ? WHERE id_pesanan = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();

    $returnStatus = post_text('return_status', 20, false);
    $returnQ = post_text('return_q', 100, false);
    $query = ['success' => 'status'];
    if (in_array($returnStatus, $allowedStatuses, true)) {
        $query['status'] = $returnStatus;
    }
    if ($returnQ !== '') {
        $query['q'] = $returnQ;
    }

    header('Location: pesanan.php?' . http_build_query($query));
    exit;
} catch (Throwable $e) {
    admin_error($e, 'Status pesanan gagal diperbarui.');
}
