<?php
require_once '../app/config/security.php';
start_secure_session();
require_once '../app/config/database.php';

$id_toko = isset($_GET['id_toko']) ? (int) $_GET['id_toko'] : 0;
if (!$id_toko) {
    header('Location: index.php');
    exit;
}

// Ambil data toko
$stmt = $koneksi->prepare('SELECT * FROM toko WHERE id_toko = ?');
$stmt->bind_param('i', $id_toko);
$stmt->execute();
$toko = $stmt->get_result()->fetch_assoc();
if (!$toko) {
    header('Location: index.php');
    exit;
}

$now_time = date('H:i:s');
$jam_buka = $toko['jam_buka'] ? substr($toko['jam_buka'], 0, 5) : null;
$jam_tutup = $toko['jam_tutup'] ? substr($toko['jam_tutup'], 0, 5) : null;
$is_buka = false;
if ($jam_buka && $jam_tutup) {
    $is_buka = $jam_buka <= $jam_tutup
        ? ($now_time >= $toko['jam_buka'] && $now_time <= $toko['jam_tutup'])
        : ($now_time >= $toko['jam_buka'] || $now_time <= $toko['jam_tutup']);
}

// Ambil data mitra
$mitras = [];
$stmtMitra = $koneksi->prepare(
    'SELECT m.* FROM toko_mitra tm
     JOIN mitra m ON m.id_mitra = tm.id_mitra
     WHERE tm.id_toko = ?'
);
$stmtMitra->bind_param('i', $id_toko);
$stmtMitra->execute();
$r = $stmtMitra->get_result();
while ($row = $r->fetch_assoc()) {
    $mitras[] = $row;
}

// Ambil data metode bayar
$bayars = [];
$stmtBayar = $koneksi->prepare(
    'SELECT b.* FROM metode_toko mt
     JOIN bayar b ON b.id_metode = mt.id_metode
     WHERE mt.id_toko = ?'
);
$stmtBayar->bind_param('i', $id_toko);
$stmtBayar->execute();
$r = $stmtBayar->get_result();
while ($row = $r->fetch_assoc()) {
    $bayars[] = $row;
}

// Initial filter values from query string (synced live by JS)
$search_menu = isset($_GET['search_menu']) ? trim((string) $_GET['search_menu']) : '';
$allowedRasa = ['pedas', 'manis', 'asin', 'berkuah', 'asam'];
$filter_rasa = strtolower(trim((string) ($_GET['rasa'] ?? '')));
if (!in_array($filter_rasa, $allowedRasa, true)) {
    $filter_rasa = '';
}
$filter_kat = isset($_GET['kategori']) ? max(0, (int) $_GET['kategori']) : 0;
$allowedSort = ['harga_asc', 'harga_desc', 'nama_az'];
$sort_menu = trim((string) ($_GET['sort'] ?? ''));
if (!in_array($sort_menu, $allowedSort, true)) {
    $sort_menu = '';
}

// Ambil seluruh menu toko agar pencarian, filter rasa, dan pengurutan berjalan instan
$menu_sql = "SELECT m.*, k.kategori_makanan,
        GROUP_CONCAT(DISTINCT r.nama_rasa ORDER BY r.nama_rasa SEPARATOR ',') AS rasa,
        GROUP_CONCAT(DISTINCT LOWER(r.nama_rasa) ORDER BY r.nama_rasa SEPARATOR ',') AS rasa_keys
    FROM menu m
    LEFT JOIN kategori k ON k.id_kategori = m.id_kategori
    LEFT JOIN menu_rasa mr ON mr.id_menu = m.id_menu
    LEFT JOIN rasa r ON r.id_rasa = mr.id_rasa
    WHERE m.id_toko = ?
    GROUP BY m.id_menu
    ORDER BY m.id_kategori ASC, m.id_menu ASC";

$stmt_m = $koneksi->prepare($menu_sql);
$stmt_m->bind_param('i', $id_toko);
$stmt_m->execute();
$menus = $stmt_m->get_result()->fetch_all(MYSQLI_ASSOC);

// Kelompokkan menu berdasarkan kategori
$grouped = [];
foreach ($menus as $menu) {
    $katId = (int) ($menu['id_kategori'] ?? 0);
    $katName = $menu['kategori_makanan'] ?: 'Lainnya';
    if (!isset($grouped[$katId])) {
        $grouped[$katId] = [
            'id' => $katId,
            'name' => $katName,
            'items' => [],
        ];
    }
    $grouped[$katId]['items'][] = $menu;
}

// Daftar kategori yang tampil di sticky nav
$kat_list = array_values(array_map(
    static fn(array $group): array => ['id_kategori' => $group['id'], 'kategori_makanan' => $group['name']],
    $grouped
));

// Reviews summary & list
$revSummaryStmt = $koneksi->prepare(
    'SELECT COUNT(*) AS total_reviews, ROUND(AVG(rating), 1) AS avg_rating FROM review WHERE id_toko = ?'
);
$revSummaryStmt->bind_param('i', $id_toko);
$revSummaryStmt->execute();
$revSummary = $revSummaryStmt->get_result()->fetch_assoc();
$totalReviews = (int) ($revSummary['total_reviews'] ?? 0);

$reviews = [];
$stmtRev = $koneksi->prepare(
    'SELECT * FROM review
     WHERE id_toko = ?
     ORDER BY tanggal_review DESC, id_review DESC
     LIMIT 20'
);
$stmtRev->bind_param('i', $id_toko);
$stmtRev->execute();
$r = $stmtRev->get_result();
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $reviews[] = $row;
    }
}

$reviewSuccess = ($_GET['review'] ?? '') === 'success';
$reviewErrorMessages = [
    'rate' => 'Terlalu banyak ulasan dalam waktu singkat. Silakan tunggu beberapa menit.',
    'invalid' => 'Pastikan nama, rating (1-5), dan komentar terisi dengan benar.',
];
$reviewError = $reviewErrorMessages[(string) ($_GET['review_error'] ?? '')] ?? '';
$rating_text = $toko['rating'] ? number_format((float) $toko['rating'], 1) : '–';

function fotoUrl(?string $val, string $folder): string
{
    if (!$val) {
        return '';
    }
    if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
        return $val;
    }
    return $folder . rawurlencode($val);
}

$totalMenuCount = count($menus);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($toko['nama_toko']) ?> – Menu</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- TOPBAR -->
    <header class="topbar">
        <a href="index.php" class="logo">
            <div class="logo-icon">🍜</div>
            <div class="logo-text">STREET FOOD<small>Gegerkalong</small></div>
        </a>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali</a>
        <div class="search-box">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="text" id="searchMenuInput" class="has-clear" placeholder="Cari menu atau deskripsi..."
                value="<?= e($search_menu) ?>" aria-label="Cari menu">
            <button type="button" id="searchMenuClear" class="search-clear<?= $search_menu !== '' ? ' show' : '' ?>"
                aria-label="Hapus pencarian menu">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="topbar-right">
            <a class="btn-nav-link" href="cek_pesanan.php">
                <i class="fas fa-receipt" aria-hidden="true"></i>
                <span>Cek Pesanan</span>
            </a>
        </div>
    </header>

    <div class="content">
        <!-- HERO BANNER -->
        <div class="store-hero">
            <?php $heroFoto = fotoUrl($toko['foto_outlet'] ?? '', 'assets/images/pict/'); ?>
            <?php if ($heroFoto): ?>
            <img src="<?= e($heroFoto) ?>" class="hero-banner"
                alt="<?= e($toko['nama_toko']) ?>">
            <?php else: ?>
            <div class="hero-banner-placeholder">🍜</div>
            <?php endif; ?>
            <div class="hero-overlay"></div>
            <div class="hero-info">
                <div class="hero-name">
                    <?= e($toko['nama_toko']) ?>
                    <span class="status-pill <?= $is_buka ? 'status-buka' : 'status-tutup' ?>">
                        <?= $is_buka ? '● Buka' : '● Tutup' ?>
                    </span>
                    <?php if ($toko['status_halal'] === 'tersertifikasi'): ?>
                    <span class="halal-tag"><i class="fas fa-leaf" aria-hidden="true"></i> Halal</span>
                    <?php endif; ?>
                </div>
                <div class="hero-meta">
                    <?php if ($toko['rating']): ?>
                    <div class="meta-item rating-item"><i class="fas fa-star" aria-hidden="true"></i> <?= e($rating_text) ?></div>
                    <?php endif; ?>
                    <?php if ($toko['lokasi']): ?>
                    <div class="meta-item"><i class="fas fa-map-marker-alt" style="color:var(--accent2)" aria-hidden="true"></i>
                        <?= e($toko['lokasi']) ?></div>
                    <?php endif; ?>
                    <?php if ($jam_buka): ?>
                    <div class="meta-item"><i class="fas fa-clock" style="color:var(--blue)" aria-hidden="true"></i> <?= e($jam_buka) ?> –
                        <?= e($jam_tutup) ?></div>
                    <?php endif; ?>
                    <?php if ($toko['no_telepon']): ?>
                    <div class="meta-item"><i class="fas fa-phone" style="color:var(--green)" aria-hidden="true"></i>
                        <?= e($toko['no_telepon']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="hero-badges">
                    <?php foreach ($mitras as $m): ?>
                    <span class="mitra-badge">
                        <i class="fas fa-motorcycle" style="font-size:11px" aria-hidden="true"></i>
                        <?= e($m['nama_mitra']) ?>
                    </span>
                    <?php endforeach; ?>
                    <?php foreach ($bayars as $b): ?>
                    <span class="bayar-badge"><i class="fas fa-wallet" style="font-size:11px" aria-hidden="true"></i>
                        <?= e($b['metode_pembayaran']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($jam_buka && $jam_tutup && !$is_buka): ?>
        <div class="store-closed-banner" role="status">
            <i class="fas fa-clock" aria-hidden="true"></i>
            <span>Toko sedang tutup saat ini (Jam operasional <?= e($jam_buka) ?> – <?= e($jam_tutup) ?>). Anda masih dapat melihat daftar menu.</span>
        </div>
        <?php endif; ?>

        <!-- STICKY KATEGORI NAV -->
        <?php if ($kat_list): ?>
        <nav class="kat-nav" id="katNav" aria-label="Kategori menu">
            <button type="button" class="kat-nav-item <?= $filter_kat === 0 ? 'active' : '' ?>" data-kat="0"
                onclick="selectKategori(0)">Semua</button>
            <?php foreach ($kat_list as $k): ?>
            <button type="button" class="kat-nav-item <?= $filter_kat === (int) $k['id_kategori'] ? 'active' : '' ?>"
                data-kat="<?= (int) $k['id_kategori'] ?>"
                onclick="selectKategori(<?= (int) $k['id_kategori'] ?>)"><?= e($k['kategori_makanan']) ?></button>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <!-- MENU SECTION -->
        <div class="menu-section">
            <div class="menu-filter-bar">
                <button type="button" class="rasa-chip <?= $filter_rasa === '' ? 'active' : '' ?>"
                    data-rasa="" onclick="selectRasa('')">Semua Rasa</button>
                <?php foreach ($allowedRasa as $r): ?>
                <button type="button" class="rasa-chip <?= $filter_rasa === $r ? 'active' : '' ?>"
                    data-rasa="<?= e($r) ?>" onclick="selectRasa('<?= e($r) ?>')"><?= ucfirst($r) ?></button>
                <?php endforeach; ?>

                <select class="sort-wrap" id="menuSortSelect" aria-label="Urutkan menu" onchange="selectSort(this.value)">
                    <option value="" <?= $sort_menu === '' ? 'selected' : '' ?>>Urutkan</option>
                    <option value="harga_asc" <?= $sort_menu === 'harga_asc' ? 'selected' : '' ?>>Harga Terendah</option>
                    <option value="harga_desc" <?= $sort_menu === 'harga_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
                    <option value="nama_az" <?= $sort_menu === 'nama_az' ? 'selected' : '' ?>>Nama A-Z</option>
                </select>
            </div>

            <?php if ($totalMenuCount > 0): ?>
            <div class="menu-result-meta">
                <span>Menampilkan <strong id="visibleMenuCount"><?= $totalMenuCount ?></strong> dari <?= $totalMenuCount ?> menu</span>
            </div>
            <?php endif; ?>

            <!-- MENU LIST -->
            <?php if ($totalMenuCount === 0): ?>
            <div class="empty-menu">
                <i class="fas fa-utensils" aria-hidden="true"></i>
                <h3>Belum ada menu tersedia</h3>
                <p>Toko ini belum menambahkan daftar menu.</p>
            </div>
            <?php else: ?>
            <div class="menu-list" id="menuFlatList" hidden></div>

            <div id="menuGroupedList">
                <?php
                $orderIdx = 0;
                foreach ($grouped as $group):
                    $kat_id = (int) $group['id'];
                    $kat_name = $group['name'];
                    $kat_menus = $group['items'];
                ?>
                <div class="kategori-section" id="kat-<?= $kat_id ?>" data-kat-id="<?= $kat_id ?>">
                    <div class="kategori-title">
                        <span><?= e($kat_name) ?></span>
                        <span class="kat-count" style="font-size:12px;font-weight:500;color:var(--text-muted)">(<?= count($kat_menus) ?> item)</span>
                    </div>
                    <div class="menu-list" id="kat-list-<?= $kat_id ?>">
                        <?php foreach ($kat_menus as $menu):
                            $fotoMenu = fotoUrl($menu['foto_menu'] ?? '', 'assets/images/makanan/');
                            $searchHaystack = mb_strtolower(trim($menu['nama_menu'] . ' ' . ($menu['deskripsi'] ?? '')), 'UTF-8');
                            $rasaItems = array_values(array_filter(array_map('trim', explode(',', (string) ($menu['rasa'] ?? '')))));
                        ?>
                        <div class="menu-item"
                            data-id="<?= (int) $menu['id_menu'] ?>"
                            data-nama="<?= e($menu['nama_menu']) ?>"
                            data-title="<?= e(mb_strtolower($menu['nama_menu'], 'UTF-8')) ?>"
                            data-search="<?= e($searchHaystack) ?>"
                            data-rasa="<?= e((string) ($menu['rasa_keys'] ?? '')) ?>"
                            data-kat-id="<?= $kat_id ?>"
                            data-harga="<?= (int) $menu['harga'] ?>"
                            data-foto="<?= e($fotoMenu) ?>"
                            data-order="<?= $orderIdx++ ?>">
                            <div class="menu-item-img">
                                <?php if ($fotoMenu): ?>
                                <img src="<?= e($fotoMenu) ?>"
                                    alt="<?= e($menu['nama_menu']) ?>" loading="lazy"
                                    onerror="this.parentElement.innerHTML='<i class=\'fas fa-utensils\' aria-hidden=\'true\'></i>'">
                                <?php else: ?><i class="fas fa-utensils" aria-hidden="true"></i><?php endif; ?>
                            </div>
                            <div class="menu-item-body">
                                <h4><?= e($menu['nama_menu']) ?></h4>
                                <?php if (!empty($menu['deskripsi'])): ?>
                                <p class="desc"><?= e(trim($menu['deskripsi'])) ?></p>
                                <?php endif; ?>
                                <div class="menu-item-footer">
                                    <span class="menu-price">Rp <?= number_format((int) $menu['harga'], 0, ',', '.') ?></span>
                                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                        <?php if ($rasaItems): ?>
                                        <div class="menu-rasa-group">
                                            <?php foreach ($rasaItems as $rasaLabel):
                                                $rasaKey = strtolower($rasaLabel);
                                                $rasaClass = in_array($rasaKey, $allowedRasa, true) ? ' rasa-' . $rasaKey : '';
                                            ?>
                                            <span class="rasa-tag<?= $rasaClass ?>"><?= e(ucfirst($rasaLabel)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="qty-control" id="qty-<?= (int) $menu['id_menu'] ?>">
                                            <button type="button" class="btn-add" onclick="addToCart(<?= (int) $menu['id_menu'] ?>)">
                                                <i class="fas fa-plus" aria-hidden="true"></i> Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="empty-menu" id="emptyMenu" hidden>
                <i class="fas fa-utensils" aria-hidden="true"></i>
                <h3>Menu tidak ditemukan</h3>
                <p>Coba ubah filter rasa, kategori, atau kata kunci pencarian</p>
                <button type="button" class="btn-reset-inline" onclick="resetMenuFilters()">
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    <span>Reset Filter</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- REVIEW FORM -->
        <div class="reviews-section" id="reviews">
            <div class="reviews-header">Tulis Ulasan</div>
            <?php if ($reviewSuccess): ?>
            <div class="review-alert" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Ulasan Anda berhasil dikirim. Terima kasih!</span>
            </div>
            <?php endif; ?>
            <?php if ($reviewError !== ''): ?>
            <div class="review-alert review-alert-error" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span><?= e($reviewError) ?></span>
            </div>
            <?php endif; ?>
            <form action="submit_review.php" method="POST" class="review-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id_toko" value="<?= (int) $id_toko ?>">
                <div class="filter-section">
                    <label for="nama_pengulas">Nama</label>
                    <input id="nama_pengulas" name="nama_pengulas" type="text" maxlength="100" placeholder="Nama Anda..." required>
                </div>
                <fieldset class="star-picker-fieldset">
                    <legend>Rating</legend>
                    <div class="star-picker-row">
                        <div class="star-picker" role="radiogroup" aria-label="Pilih rating bintang">
                            <?php for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" id="star-<?= $s ?>" name="rating" value="<?= $s ?>" <?= $s === 5 ? 'required' : '' ?>>
                            <label for="star-<?= $s ?>" title="<?= $s ?> bintang" aria-label="<?= $s ?> bintang">★</label>
                            <?php endfor; ?>
                        </div>
                        <span class="star-picker-hint" id="starPickerHint">Pilih jumlah bintang</span>
                    </div>
                </fieldset>
                <div class="filter-section">
                    <label for="komentar">Komentar</label>
                    <textarea id="komentar" name="komentar" maxlength="1000" placeholder="Bagikan pengalaman Anda di toko ini..." required></textarea>
                </div>
                <button type="submit" class="btn-checkout">Kirim Ulasan</button>
            </form>
        </div>

        <!-- REVIEWS -->
        <div class="reviews-section">
            <div class="reviews-header">
                <span>Ulasan Pelanggan</span>
                <?php if ($totalReviews > 0): ?>
                <span class="review-summary-pill">
                    <i class="fas fa-star" aria-hidden="true"></i>
                    <span><?= e(number_format((float) $revSummary['avg_rating'], 1)) ?> (<?= number_format($totalReviews) ?> ulasan)</span>
                </span>
                <?php endif; ?>
            </div>
            <?php if (empty($reviews)): ?>
            <div class="no-review"><i class="far fa-comment-dots" aria-hidden="true"
                    style="font-size:32px;opacity:.4;display:block;margin-bottom:8px"></i> Belum ada ulasan</div>
            <?php else: ?>
            <?php foreach ($reviews as $idx => $rv):
                $starCount = max(0, min(5, (int) ($rv['rating'] ?? 0)));
            ?>
            <div class="review-card<?= $idx >= 5 ? ' is-hidden-review' : '' ?>">
                <div class="review-top">
                    <div class="review-avatar"><?= e(mb_strtoupper(mb_substr((string) $rv['nama_pengulas'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                    <div>
                        <div class="review-name"><?= e($rv['nama_pengulas']) ?></div>
                        <div class="review-stars" aria-label="Rating <?= $starCount ?> dari 5">
                            <?= str_repeat('★', $starCount) ?><?= str_repeat('☆', 5 - $starCount) ?></div>
                    </div>
                    <div class="review-date"><?= e(date('d M Y', strtotime($rv['tanggal_review']))) ?></div>
                </div>
                <div class="review-text"><?= e($rv['komentar']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (count($reviews) > 5): ?>
            <button type="button" class="btn-more-reviews" id="toggleReviewsBtn" onclick="toggleMoreReviews()">
                Lihat <?= count($reviews) - 5 ?> ulasan lainnya
            </button>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- FLOATING CART -->
    <div class="cart-float" id="cartFloat" role="button" tabindex="0" aria-label="Buka keranjang"
        onclick="openCart()" onkeydown="if(event.key==='Enter'||event.key===' ')openCart()">
        <div class="cart-float-left">
            <div class="cart-count-badge" id="cartCount">0</div>
            <div class="cart-float-label">
                Lihat Keranjang
                <small id="cartItems">0 item</small>
            </div>
        </div>
        <div class="cart-float-total" id="cartTotal">Rp 0</div>
    </div>

    <!-- CART DRAWER -->
    <div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
    <div class="cart-drawer" id="cartDrawer" role="dialog" aria-modal="true" aria-label="Keranjang belanja">
        <div class="cart-drawer-handle"></div>
        <div class="cart-drawer-header">
            <h3>🛒 Keranjang</h3>
            <div class="cart-drawer-actions">
                <button type="button" class="cart-clear-btn" id="cartClearBtn" onclick="clearCart()" hidden>Kosongkan</button>
                <button type="button" class="cart-close" aria-label="Tutup keranjang" onclick="closeCart()"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="cart-items" id="cartItemsList"></div>
        <div class="cart-footer">
            <div class="cart-summary">
                <span>Total Pesanan</span>
                <span class="total" id="cartTotalDrawer">Rp 0</span>
            </div>
            <button class="btn-checkout" id="checkoutButton" type="button" onclick="goCheckout()">
                <i class="fas fa-receipt" aria-hidden="true"></i> Checkout
            </button>
        </div>
    </div>

    <script>
    const STORE_ID = <?= (int) $id_toko ?>;
    const menuState = {
        search: <?= json_encode($search_menu, JSON_UNESCAPED_UNICODE) ?>,
        rasa: <?= json_encode($filter_rasa, JSON_UNESCAPED_UNICODE) ?>,
        kat: <?= (int) $filter_kat ?>,
        sort: <?= json_encode($sort_menu, JSON_UNESCAPED_UNICODE) ?>,
    };

    let cart = {};
    const CART_KEY = 'streetfood_cart_toko_' + STORE_ID;
    try {
        const parsed = JSON.parse(localStorage.getItem(CART_KEY) || '{}');
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
            Object.entries(parsed).forEach(([id, item]) => {
                const qty = parseInt(item?.qty, 10);
                const harga = parseInt(item?.harga, 10);
                if (/^\d+$/.test(id) && item && qty > 0 && Number.isFinite(harga) && harga >= 0) {
                    cart[id] = {
                        nama: String(item.nama || ''),
                        harga,
                        foto: String(item.foto || ''),
                        qty: Math.min(qty, 99)
                    };
                }
            });
        }
    } catch (_) {
        cart = {};
    }

    function fmt(n) {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function cartTotal() {
        return Object.values(cart).reduce((s, i) => s + i.harga * i.qty, 0);
    }

    function cartCount() {
        return Object.values(cart).reduce((s, i) => s + i.qty, 0);
    }

    function updateCartFloat() {
        const count = cartCount();
        const total = cartTotal();
        document.getElementById('cartCount').textContent = count;
        document.getElementById('cartItems').textContent = count + ' item';
        document.getElementById('cartTotal').textContent = fmt(total);
        document.getElementById('cartTotalDrawer').textContent = fmt(total);
        const clearBtn = document.getElementById('cartClearBtn');
        if (clearBtn) clearBtn.hidden = count === 0;
        const checkoutBtn = document.getElementById('checkoutButton');
        if (checkoutBtn) checkoutBtn.disabled = count === 0;
        const el = document.getElementById('cartFloat');
        count > 0 ? el.classList.add('visible') : el.classList.remove('visible');
    }

    function renderQtyControl(id) {
        const qtyEl = document.getElementById('qty-' + id);
        if (!qtyEl) return;
        const item = cart[id];
        if (!item || item.qty === 0) {
            qtyEl.innerHTML = '<button type="button" class="btn-add" aria-label="Tambah menu" onclick="addToCart(' + id +
                ')"><i class="fas fa-plus" aria-hidden="true"></i> Tambah</button>';
        } else {
            qtyEl.innerHTML =
                '<div style="display:flex;align-items:center;gap:6px">' +
                '<button type="button" class="btn-qty btn-qty-minus" aria-label="Kurangi jumlah" onclick="changeQty(' + id +
                ',-1)"><i class="fas fa-minus" aria-hidden="true" style="font-size:11px"></i></button>' +
                '<span class="qty-num">' + item.qty + '</span>' +
                '<button type="button" class="btn-qty btn-qty-plus" aria-label="Tambah jumlah" onclick="changeQty(' + id +
                ',1)"><i class="fas fa-plus" aria-hidden="true" style="font-size:11px"></i></button>' +
                '</div>';
        }
    }

    function addToCart(id) {
        const el = document.querySelector('.menu-item[data-id="' + id + '"]');
        if (!el) return;
        const nama = el.dataset.nama;
        const harga = parseInt(el.dataset.harga, 10);
        const foto = el.dataset.foto;
        if (!cart[id]) {
            cart[id] = { nama, harga, foto, qty: 0 };
        }
        if (cart[id].qty < 99) {
            cart[id].qty++;
        }
        renderQtyControl(id);
        persistCart();
        updateCartFloat();
        renderCartDrawer();
        const btn = document.getElementById('cartFloat');
        btn.style.transform = 'translateX(-50%) translateY(0) scale(1.04)';
        setTimeout(() => btn.style.transform = 'translateX(-50%) translateY(0) scale(1)', 180);
    }

    function changeQty(id, delta) {
        if (!cart[id]) return;
        cart[id].qty = Math.min(99, cart[id].qty + delta);
        if (cart[id].qty <= 0) delete cart[id];
        renderQtyControl(id);
        persistCart();
        updateCartFloat();
        renderCartDrawer();
    }

    function clearCart() {
        const ids = Object.keys(cart);
        cart = {};
        persistCart();
        ids.forEach(id => renderQtyControl(id));
        updateCartFloat();
        renderCartDrawer();
    }

    function persistCart() {
        try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); } catch (_) {}
    }

    function goCheckout() {
        if (!Object.keys(cart).length) return;
        const payload = encodeURIComponent(JSON.stringify(Object.fromEntries(
            Object.entries(cart).map(([id, item]) => [id, { qty: item.qty }])
        )));
        window.location.href = 'checkout.php?id_toko=' + STORE_ID + '&cart=' + payload;
    }

    function renderCartDrawer() {
        const list = document.getElementById('cartItemsList');
        const items = Object.entries(cart);
        if (items.length === 0) {
            list.innerHTML =
                '<div style="text-align:center;padding:40px;color:var(--text-muted)"><i class="fas fa-shopping-cart" aria-hidden="true" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px"></i><p>Keranjang masih kosong</p></div>';
            return;
        }
        const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[char]));
        list.innerHTML = items.map(([id, item]) =>
            '<div class="cart-item">' +
            '<div class="cart-item-img">' +
            (item.foto ? '<img src="' + escapeHtml(item.foto) + '" alt="" onerror="this.style.display=\'none\'">' : '') +
            '</div>' +
            '<div class="cart-item-info">' +
            '<h5>' + escapeHtml(item.nama) + '</h5>' +
            '<div class="price">' + fmt(item.harga) + '</div>' +
            '<div class="cart-item-sub">Subtotal: ' + fmt(item.harga * item.qty) + '</div>' +
            '</div>' +
            '<div class="cart-item-qty">' +
            '<button type="button" class="btn-qty btn-qty-minus" aria-label="Kurangi jumlah" onclick="changeQty(' + id +
            ',-1)"><i class="fas fa-minus" aria-hidden="true" style="font-size:11px"></i></button>' +
            '<span class="qty-num">' + item.qty + '</span>' +
            '<button type="button" class="btn-qty btn-qty-plus" aria-label="Tambah jumlah" onclick="changeQty(' + id +
            ',1)"><i class="fas fa-plus" aria-hidden="true" style="font-size:11px"></i></button>' +
            '</div>' +
            '</div>'
        ).join('');
    }

    function openCart() {
        renderCartDrawer();
        document.getElementById('cartOverlay').classList.add('show');
        document.getElementById('cartDrawer').classList.add('show');
    }

    function closeCart() {
        document.getElementById('cartOverlay').classList.remove('show');
        document.getElementById('cartDrawer').classList.remove('show');
    }

    // Instant menu search, rasa filter, category filter, and sort
    const menuItems = [...document.querySelectorAll('.menu-item')];

    function syncMenuUrl() {
        const params = new URLSearchParams();
        params.set('id_toko', String(STORE_ID));
        if (menuState.search) params.set('search_menu', menuState.search);
        if (menuState.rasa) params.set('rasa', menuState.rasa);
        if (menuState.kat > 0) params.set('kategori', String(menuState.kat));
        if (menuState.sort) params.set('sort', menuState.sort);
        const newUrl = window.location.pathname + '?' + params.toString() + window.location.hash;
        window.history.replaceState(null, '', newUrl);
    }

    function applyMenuFilters(syncUrl = true) {
        if (!menuItems.length) return;

        const q = menuState.search.trim().toLowerCase();
        const flatList = document.getElementById('menuFlatList');
        const groupedList = document.getElementById('menuGroupedList');
        const emptyEl = document.getElementById('emptyMenu');
        const countEl = document.getElementById('visibleMenuCount');
        const katNav = document.getElementById('katNav');

        const matching = [];
        const visibleByKat = {};

        menuItems.forEach(item => {
            const haystack = item.dataset.search || '';
            const rasaList = item.dataset.rasa ? item.dataset.rasa.split(',') : [];
            const katId = parseInt(item.dataset.katId || '0', 10);

            let ok = true;
            if (q && !haystack.includes(q)) ok = false;
            if (ok && menuState.rasa && !rasaList.includes(menuState.rasa)) ok = false;
            if (ok && menuState.kat > 0 && katId !== menuState.kat) ok = false;

            item.hidden = !ok;
            if (ok) {
                matching.push(item);
                visibleByKat[katId] = (visibleByKat[katId] || 0) + 1;
            }
        });

        if (menuState.sort !== '') {
            const sorted = [...matching].sort((a, b) => {
                if (menuState.sort === 'harga_asc') {
                    return parseInt(a.dataset.harga, 10) - parseInt(b.dataset.harga, 10);
                }
                if (menuState.sort === 'harga_desc') {
                    return parseInt(b.dataset.harga, 10) - parseInt(a.dataset.harga, 10);
                }
                if (menuState.sort === 'nama_az') {
                    return (a.dataset.title || '').localeCompare(b.dataset.title || '', 'id');
                }
                return parseInt(a.dataset.order, 10) - parseInt(b.dataset.order, 10);
            });

            sorted.forEach(item => flatList.appendChild(item));
            groupedList.hidden = true;
            flatList.hidden = matching.length === 0;
        } else {
            const byOrder = [...menuItems].sort((a, b) =>
                parseInt(a.dataset.order, 10) - parseInt(b.dataset.order, 10)
            );
            byOrder.forEach(item => {
                const target = document.getElementById('kat-list-' + item.dataset.katId);
                if (target) target.appendChild(item);
            });

            flatList.hidden = true;
            groupedList.hidden = matching.length === 0;

            document.querySelectorAll('.kategori-section').forEach(section => {
                const katId = parseInt(section.dataset.katId || '0', 10);
                const visibleInSection = visibleByKat[katId] || 0;
                section.hidden = visibleInSection === 0;
                const countLabel = section.querySelector('.kat-count');
                if (countLabel) countLabel.textContent = '(' + visibleInSection + ' item)';
            });
        }

        if (katNav) {
            katNav.hidden = menuState.sort !== '' || matching.length === 0;
            document.querySelectorAll('.kat-nav-item').forEach(btn => {
                const kId = parseInt(btn.dataset.kat || '0', 10);
                btn.classList.toggle('active', kId === menuState.kat);
                if (kId > 0) {
                    const hasMatchingWithoutKatFilter = menuItems.some(item => {
                        const haystack = item.dataset.search || '';
                        const rasaList = item.dataset.rasa ? item.dataset.rasa.split(',') : [];
                        const itemKat = parseInt(item.dataset.katId || '0', 10);
                        return itemKat === kId &&
                            (!q || haystack.includes(q)) &&
                            (!menuState.rasa || rasaList.includes(menuState.rasa));
                    });
                    btn.hidden = !hasMatchingWithoutKatFilter;
                }
            });
        }

        document.querySelectorAll('.rasa-chip').forEach(btn => {
            btn.classList.toggle('active', (btn.dataset.rasa || '') === menuState.rasa);
        });

        const clearBtn = document.getElementById('searchMenuClear');
        if (clearBtn) clearBtn.classList.toggle('show', q !== '');
        if (countEl) countEl.textContent = String(matching.length);
        if (emptyEl) emptyEl.hidden = matching.length > 0;

        if (syncUrl) syncMenuUrl();
    }

    function selectRasa(rasa) {
        menuState.rasa = menuState.rasa === rasa && rasa !== '' ? '' : rasa;
        applyMenuFilters();
    }

    function selectKategori(katId) {
        menuState.kat = katId;
        applyMenuFilters();
        if (katId > 0) {
            const el = document.getElementById('kat-' + katId);
            if (el && !el.hidden) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function selectSort(sortVal) {
        menuState.sort = sortVal;
        applyMenuFilters();
    }

    function resetMenuFilters() {
        menuState.search = '';
        menuState.rasa = '';
        menuState.kat = 0;
        menuState.sort = '';
        const input = document.getElementById('searchMenuInput');
        if (input) input.value = '';
        const sortSelect = document.getElementById('menuSortSelect');
        if (sortSelect) sortSelect.value = '';
        applyMenuFilters();
    }

    const searchInput = document.getElementById('searchMenuInput');
    const searchClear = document.getElementById('searchMenuClear');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            menuState.search = this.value;
            applyMenuFilters();
        });
    }
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            menuState.search = '';
            applyMenuFilters();
        });
    }

    // Interactive star picker hint
    const ratingLabels = {
        1: '1 - Buruk',
        2: '2 - Kurang',
        3: '3 - Cukup',
        4: '4 - Bagus',
        5: '5 - Sangat bagus'
    };
    const starHint = document.getElementById('starPickerHint');
    document.querySelectorAll('.star-picker input[name="rating"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (starHint) starHint.textContent = ratingLabels[radio.value] || 'Pilih jumlah bintang';
        });
    });

    // Expandable reviews
    function toggleMoreReviews() {
        const hiddenCards = document.querySelectorAll('.review-card.is-hidden-review');
        const btn = document.getElementById('toggleReviewsBtn');
        if (!hiddenCards.length || !btn) return;
        const isExpanded = btn.dataset.expanded === '1';
        hiddenCards.forEach(card => {
            card.style.display = isExpanded ? 'none' : 'block';
        });
        btn.dataset.expanded = isExpanded ? '0' : '1';
        btn.textContent = isExpanded
            ? 'Lihat ' + hiddenCards.length + ' ulasan lainnya'
            : 'Sembunyikan ulasan';
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeCart();
    });

    updateCartFloat();
    Object.keys(cart).forEach(id => renderQtyControl(id));
    applyMenuFilters(false);
    </script>
</body>

</html>
