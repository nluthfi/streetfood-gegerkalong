<?php
require_once '../../app/config/security.php';
require_admin();
require_once '../../app/config/database.php';

$ratingFilter = filter_input(INPUT_GET, 'rating', FILTER_VALIDATE_INT);
if ($ratingFilter === false || $ratingFilter === null || $ratingFilter < 1 || $ratingFilter > 5) {
    $ratingFilter = 0;
}

$tokoFilter = filter_input(INPUT_GET, 'toko', FILTER_VALIDATE_INT);
if ($tokoFilter === false || $tokoFilter === null || $tokoFilter < 1) {
    $tokoFilter = 0;
}

$keyword = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$successMessages = [
    'hapus' => 'Ulasan berhasil dihapus dan rating toko telah diperbarui.',
];
$errorMessages = [
    'notfound' => 'Ulasan tidak ditemukan atau sudah dihapus.',
];
$successMsg = $successMessages[(string) ($_GET['success'] ?? '')] ?? '';
$errorMsg = $errorMessages[(string) ($_GET['error'] ?? '')] ?? '';

// Store dropdown options
$stores = $koneksi->query('SELECT id_toko, nama_toko FROM toko ORDER BY nama_toko ASC')->fetch_all(MYSQLI_ASSOC);

// Rating distribution counts (scoped to selected store if filtered)
$ratingCounts = [0 => 0, 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$avgRating = null;
if ($tokoFilter > 0) {
    $distStmt = $koneksi->prepare('SELECT rating, COUNT(*) AS cnt FROM review WHERE id_toko = ? GROUP BY rating');
    $distStmt->bind_param('i', $tokoFilter);
    $distStmt->execute();
    $distRows = $distStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $avgStmt = $koneksi->prepare('SELECT ROUND(AVG(rating), 2) AS avg_rating FROM review WHERE id_toko = ?');
    $avgStmt->bind_param('i', $tokoFilter);
    $avgStmt->execute();
    $avgRating = $avgStmt->get_result()->fetch_assoc()['avg_rating'] ?? null;
} else {
    $distRows = $koneksi->query('SELECT rating, COUNT(*) AS cnt FROM review GROUP BY rating')->fetch_all(MYSQLI_ASSOC);
    $avgRating = $koneksi->query('SELECT ROUND(AVG(rating), 2) AS avg_rating FROM review')->fetch_assoc()['avg_rating'] ?? null;
}

foreach ($distRows as $row) {
    $rVal = (int) $row['rating'];
    $cnt = (int) $row['cnt'];
    if (isset($ratingCounts[$rVal])) {
        $ratingCounts[$rVal] = $cnt;
        $ratingCounts[0] += $cnt;
    }
}

// Build filtered review query
$where = [];
$params = [];
$types = '';

if ($ratingFilter > 0) {
    $where[] = 'r.rating = ?';
    $params[] = $ratingFilter;
    $types .= 'i';
}
if ($tokoFilter > 0) {
    $where[] = 'r.id_toko = ?';
    $params[] = $tokoFilter;
    $types .= 'i';
}
if ($keyword !== '') {
    $like = "%{$keyword}%";
    $where[] = '(r.nama_pengulas LIKE ? OR r.komentar LIKE ? OR t.nama_toko LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS total
             FROM review r
             JOIN toko t ON t.id_toko = r.id_toko
             $whereSql";
$countStmt = $koneksi->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $redirectQuery = ['page' => $totalPages];
    if ($ratingFilter > 0) {
        $redirectQuery['rating'] = $ratingFilter;
    }
    if ($tokoFilter > 0) {
        $redirectQuery['toko'] = $tokoFilter;
    }
    if ($keyword !== '') {
        $redirectQuery['q'] = $keyword;
    }
    header('Location: ulasan.php?' . http_build_query($redirectQuery));
    exit;
}

$listSql = "SELECT r.id_review, r.id_toko, r.nama_pengulas, r.rating, r.komentar, r.tanggal_review, t.nama_toko
            FROM review r
            JOIN toko t ON t.id_toko = r.id_toko
            $whereSql
            ORDER BY r.tanggal_review DESC, r.id_review DESC
            LIMIT ? OFFSET ?";
$listStmt = $koneksi->prepare($listSql);
$listTypes = $types . 'ii';
$listParams = [...$params, $perPage, $offset];
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$reviews = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$buildTabHref = static function (int $targetRating) use ($tokoFilter, $keyword): string {
    $args = [];
    if ($targetRating > 0) {
        $args['rating'] = $targetRating;
    }
    if ($tokoFilter > 0) {
        $args['toko'] = $tokoFilter;
    }
    if ($keyword !== '') {
        $args['q'] = $keyword;
    }
    return 'ulasan.php' . ($args ? '?' . http_build_query($args) : '');
};

$buildPageHref = static function (int $targetPage) use ($ratingFilter, $tokoFilter, $keyword): string {
    $args = ['page' => $targetPage];
    if ($ratingFilter > 0) {
        $args['rating'] = $ratingFilter;
    }
    if ($tokoFilter > 0) {
        $args['toko'] = $tokoFilter;
    }
    if ($keyword !== '') {
        $args['q'] = $keyword;
    }
    return 'ulasan.php?' . http_build_query($args);
};

include '../../app/views/layout/header.php';
include '../../app/views/layout/sidebar.php';
?>
<main class="main-content">
    <div class="mb-4"><h2 class="fw-bold">STREET FOOD</h2></div>

    <?php if ($successMsg !== ''): ?>
    <div class="admin-alert-success" role="status">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span><?= e($successMsg) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($errorMsg !== ''): ?>
    <div class="admin-alert-error" role="alert">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span><?= e($errorMsg) ?></span>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header">
            <div>
                <h4>Moderasi Ulasan Pelanggan</h4>
                <p>
                    Menampilkan <?= number_format(count($reviews)) ?> dari <?= number_format($totalRows) ?> ulasan
                    <?php if ($avgRating !== null): ?>
                        · Rata-rata <?= e(number_format((float) $avgRating, 2)) ?> / 5.00
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="status-filter-tabs" role="navigation" aria-label="Filter rating ulasan">
            <a href="<?= e($buildTabHref(0)) ?>" class="status-tab<?= $ratingFilter === 0 ? ' active' : '' ?>">
                <span>Semua</span>
                <span class="status-tab-count"><?= number_format($ratingCounts[0]) ?></span>
            </a>
            <?php for ($star = 5; $star >= 1; $star--): ?>
            <a href="<?= e($buildTabHref($star)) ?>" class="status-tab<?= $ratingFilter === $star ? ' active' : '' ?>">
                <span><?= $star ?> ★</span>
                <span class="status-tab-count"><?= number_format($ratingCounts[$star]) ?></span>
            </a>
            <?php endfor; ?>
        </div>

        <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
            <?php if ($ratingFilter > 0): ?>
            <input type="hidden" name="rating" value="<?= $ratingFilter ?>">
            <?php endif; ?>
            <select name="toko" class="form-select" style="max-width: 280px;" aria-label="Filter berdasarkan toko">
                <option value="0">Semua Toko</option>
                <?php foreach ($stores as $store):
                    $sid = (int) $store['id_toko'];
                ?>
                <option value="<?= $sid ?>"<?= $tokoFilter === $sid ? ' selected' : '' ?>>
                    <?= e($store['nama_toko']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input class="form-control flex-grow-1" style="min-width: 220px;" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Cari nama pengulas, komentar, atau toko...">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
            <?php if ($ratingFilter > 0 || $tokoFilter > 0 || $keyword !== ''): ?>
            <a href="ulasan.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <div class="table-scroll">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Toko</th>
                            <th>Pengulas</th>
                            <th>Rating</th>
                            <th>Komentar</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reviews as $idx => $rev):
                        $rid = (int) $rev['id_review'];
                        $stars = max(1, min(5, (int) $rev['rating']));
                    ?>
                        <tr>
                            <td><?= $offset + $idx + 1 ?></td>
                            <td>
                                <a href="<?= e(app_url('public/page_menu.php?id_toko=' . (int) $rev['id_toko'])) ?>" target="_blank" rel="noopener" class="review-store-link">
                                    <?= e($rev['nama_toko']) ?>
                                </a>
                            </td>
                            <td><?= e($rev['nama_pengulas']) ?></td>
                            <td>
                                <span class="rating-badge" aria-label="Rating <?= $stars ?> dari 5">
                                    <span class="rating-stars" aria-hidden="true"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></span>
                                    <span><?= $stars ?>/5</span>
                                </span>
                            </td>
                            <td>
                                <div class="review-comment-preview"><?= nl2br(e($rev['komentar'])) ?></div>
                            </td>
                            <td><?= e(date('d/m/Y H:i', strtotime($rev['tanggal_review']))) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-detail" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $rid ?>">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i> Lihat
                                    </button>
                                    <form action="hapus_ulasan.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus ulasan ini? Rating toko akan dihitung ulang.')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $rid ?>">
                                        <input type="hidden" name="return_rating" value="<?= $ratingFilter ?>">
                                        <input type="hidden" name="return_toko" value="<?= $tokoFilter ?>">
                                        <input type="hidden" name="return_q" value="<?= e($keyword) ?>">
                                        <input type="hidden" name="return_page" value="<?= $page ?>">
                                        <button type="submit" class="btn-action delete" aria-label="Hapus ulasan dari <?= e($rev['nama_pengulas']) ?>">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reviews): ?>
                        <tr>
                            <td colspan="7" class="text-center">Belum ada ulasan yang sesuai filter.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-3" aria-label="Navigasi halaman ulasan">
            <ul class="pagination flex-wrap">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($buildPageHref($p)) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</main>

<?php foreach ($reviews as $rev):
    $rid = (int) $rev['id_review'];
    $stars = max(1, min(5, (int) $rev['rating']));
?>
<div class="modal fade" id="reviewModal<?= $rid ?>" tabindex="-1" aria-labelledby="reviewTitle<?= $rid ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="reviewTitle<?= $rid ?>">Detail Ulasan #<?= $rid ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="detail-item">
                    <span class="detail-label">Toko</span>
                    <span class="detail-value"><?= e($rev['nama_toko']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Pengulas</span>
                    <span class="detail-value"><?= e($rev['nama_pengulas']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Rating</span>
                    <span class="detail-value">
                        <span class="rating-badge">
                            <span class="rating-stars" aria-hidden="true"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></span>
                            <span><?= $stars ?>/5</span>
                        </span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Waktu</span>
                    <span class="detail-value"><?= e(date('d M Y, H:i', strtotime($rev['tanggal_review']))) ?></span>
                </div>
                <div class="mt-3">
                    <span class="detail-label d-block mb-1">Komentar</span>
                    <p class="mb-0"><?= nl2br(e($rev['komentar'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../../app/views/layout/footer.php'; ?>
