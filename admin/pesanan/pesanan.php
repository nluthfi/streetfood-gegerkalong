<?php
include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
include '../../app/config/database.php';

$allowedStatuses = ['baru', 'diproses', 'selesai', 'dibatalkan'];
$statusFilter = trim((string) ($_GET['status'] ?? ''));
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$keyword = trim((string) ($_GET['q'] ?? ''));
$success = ($_GET['success'] ?? '') === 'status';

$statusCounts = [
    'all' => 0,
    'baru' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'dibatalkan' => 0,
];
$countResult = $koneksi->query('SELECT status, COUNT(*) AS cnt FROM pesanan GROUP BY status');
while ($row = $countResult->fetch_assoc()) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int) $row['cnt'];
        $statusCounts['all'] += (int) $row['cnt'];
    }
}

$where = [];
$params = [];
$types = '';
if ($statusFilter !== '') {
    $where[] = 'p.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
if ($keyword !== '') {
    $cleanId = ltrim($keyword, '#');
    $idMatch = ctype_digit($cleanId) ? (int) $cleanId : 0;
    $like = "%{$keyword}%";
    if ($idMatch > 0) {
        $where[] = '(p.id_pesanan = ? OR p.nama_pemesan LIKE ? OR p.no_telepon LIKE ? OR t.nama_toko LIKE ?)';
        $params[] = $idMatch;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'isss';
    } else {
        $where[] = '(p.nama_pemesan LIKE ? OR p.no_telepon LIKE ? OR t.nama_toko LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT p.*, t.nama_toko
        FROM pesanan p
        JOIN toko t ON t.id_toko = p.id_toko
        $whereSql
        ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC
        LIMIT 200";
$stmt = $koneksi->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$detailsByOrder = [];
if ($orders) {
    $orderIds = array_map(static fn(array $o): int => (int) $o['id_pesanan'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $detailTypes = str_repeat('i', count($orderIds));
    $detailStmt = $koneksi->prepare(
        "SELECT id_pesanan, nama_menu, harga_satuan, jumlah, subtotal
         FROM detail_pesanan
         WHERE id_pesanan IN ($placeholders)
         ORDER BY id_detail ASC"
    );
    $detailStmt->bind_param($detailTypes, ...$orderIds);
    $detailStmt->execute();
    foreach ($detailStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $detail) {
        $detailsByOrder[(int) $detail['id_pesanan']][] = $detail;
    }
}

$statusLabels = [
    '' => 'Semua',
    'baru' => 'Baru',
    'diproses' => 'Diproses',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
?>
<main class="main-content">
    <div class="mb-4"><h2 class="fw-bold">STREET FOOD</h2></div>

    <?php if ($success): ?>
    <div class="admin-alert-success" role="status">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span>Status pesanan berhasil diperbarui.</span>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header">
            <div>
                <h4>Pesanan</h4>
                <p>Menampilkan <?= number_format(count($orders)) ?> pesanan (maksimal 200 terbaru)</p>
            </div>
        </div>

        <div class="status-filter-tabs" role="navigation" aria-label="Filter status pesanan">
            <?php foreach ($statusLabels as $key => $label):
                $countKey = $key === '' ? 'all' : $key;
                $queryArgs = [];
                if ($key !== '') {
                    $queryArgs['status'] = $key;
                }
                if ($keyword !== '') {
                    $queryArgs['q'] = $keyword;
                }
                $tabHref = 'pesanan.php' . ($queryArgs ? '?' . http_build_query($queryArgs) : '');
            ?>
            <a href="<?= e($tabHref) ?>" class="status-tab<?= $statusFilter === $key ? ' active' : '' ?>">
                <span><?= e($label) ?></span>
                <span class="status-tab-count"><?= number_format($statusCounts[$countKey]) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
            <?php if ($statusFilter !== ''): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <input class="form-control flex-grow-1" style="min-width: 220px;" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Cari ID (#12), pemesan, telepon, atau toko...">
            <button type="submit" class="btn btn-outline-secondary">Cari</button>
            <?php if ($statusFilter !== '' || $keyword !== ''): ?>
            <a href="pesanan.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <div class="table-scroll">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Toko</th>
                            <th>Pemesan</th>
                            <th>Item</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order):
                        $oid = (int) $order['id_pesanan'];
                        $details = $detailsByOrder[$oid] ?? [];
                        $itemCount = array_sum(array_map(static fn(array $d): int => (int) $d['jumlah'], $details));
                    ?>
                        <tr>
                            <td>#<?= $oid ?></td>
                            <td><?= e($order['nama_toko']) ?></td>
                            <td><?= e($order['nama_pemesan']) ?><br><small class="text-muted"><?= e($order['no_telepon']) ?></small></td>
                            <td><?= number_format($itemCount) ?> item</td>
                            <td>Rp <?= number_format((int) $order['total'], 0, ',', '.') ?></td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    <span class="order-status-badge order-status-<?= e($order['status']) ?> align-self-start">
                                        <?= e($statusLabels[$order['status']] ?? $order['status']) ?>
                                    </span>
                                    <form action="update_status.php" method="POST" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $oid ?>">
                                        <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="return_q" value="<?= e($keyword) ?>">
                                        <select name="status" class="form-select form-select-sm" aria-label="Status pesanan #<?= $oid ?>">
                                            <option value="baru" <?= $order['status'] === 'baru' ? 'selected' : '' ?>>Baru</option>
                                            <option value="diproses" <?= $order['status'] === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                            <option value="selesai" <?= $order['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                            <option value="dibatalkan" <?= $order['status'] === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                    </form>
                                </div>
                            </td>
                            <td><?= e(date('d/m/Y H:i', strtotime($order['tanggal_pesanan']))) ?></td>
                            <td>
                                <button type="button" class="btn btn-detail" data-bs-toggle="modal" data-bs-target="#order<?= $oid ?>">
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Detail
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orders): ?>
                        <tr><td colspan="8" class="text-center">Belum ada pesanan yang sesuai filter.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php foreach ($orders as $order):
    $oid = (int) $order['id_pesanan'];
    $details = $detailsByOrder[$oid] ?? [];
?>
<div class="modal fade" id="order<?= $oid ?>" tabindex="-1" aria-labelledby="orderTitle<?= $oid ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="orderTitle<?= $oid ?>">Pesanan #<?= $oid ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong><?= e($order['nama_toko']) ?></strong>
                    <span class="order-status-badge order-status-<?= e($order['status']) ?>">
                        <?= e($statusLabels[$order['status']] ?? $order['status']) ?>
                    </span>
                </div>
                <p class="mb-1"><strong><?= e($order['nama_pemesan']) ?></strong> · <?= e($order['no_telepon']) ?></p>
                <p class="text-muted small mb-3"><?= e(date('d M Y, H:i', strtotime($order['tanggal_pesanan']))) ?></p>
                <?php foreach ($details as $d): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <div>
                        <div><?= e($d['nama_menu']) ?> × <?= (int) $d['jumlah'] ?></div>
                        <small class="text-muted">@ Rp <?= number_format((int) $d['harga_satuan'], 0, ',', '.') ?></small>
                    </div>
                    <strong>Rp <?= number_format((int) $d['subtotal'], 0, ',', '.') ?></strong>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between pt-3 fw-bold">
                    <span>Total</span>
                    <span>Rp <?= number_format((int) $order['total'], 0, ',', '.') ?></span>
                </div>
                <p class="mt-3 mb-0"><strong>Catatan:</strong> <?= e($order['catatan'] ?: '-') ?></p>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../../app/views/layout/footer.php'; ?>
