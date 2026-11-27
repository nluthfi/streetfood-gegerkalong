<?php
require_once '../app/config/app.php';
require_once '../app/config/security.php';
start_secure_session();
require_once '../app/config/database.php';

function normalize_phone_digits(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?? '';
}

function phone_variants(string $digits): array
{
    if ($digits === '') {
        return [];
    }

    $variants = [$digits];
    if (str_starts_with($digits, '62') && strlen($digits) > 4) {
        $variants[] = '0' . substr($digits, 2);
    } elseif (str_starts_with($digits, '0') && strlen($digits) > 4) {
        $variants[] = '62' . substr($digits, 1);
    }

    return array_values(array_unique($variants));
}

function phones_match(string $inputPhone, string $storedPhone): bool
{
    $inputDigits = normalize_phone_digits($inputPhone);
    $storedDigits = normalize_phone_digits($storedPhone);

    if (strlen($inputDigits) < 6 || strlen($storedDigits) < 6) {
        return false;
    }

    foreach (phone_variants($inputDigits) as $a) {
        foreach (phone_variants($storedDigits) as $b) {
            if (hash_equals($b, $a)) {
                return true;
            }
        }
    }

    return false;
}

$prefillId = '';
$rawGetId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($rawGetId && $rawGetId > 0) {
    $prefillId = (string) $rawGetId;
}

$inputOrderId = $prefillId;
$inputPhone = '';
$order = null;
$details = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $rawOrderInput = trim((string) ($_POST['id_pesanan'] ?? ''));
    $cleanOrderInput = ltrim($rawOrderInput, "# \t\n\r\0\x0B");
    $inputOrderId = $cleanOrderInput;
    $inputPhone = trim((string) ($_POST['no_telepon'] ?? ''));

    $lookupWindow = 10 * 60;
    if (is_rate_limited('order_lookup', 15, $lookupWindow)) {
        $error = 'Terlalu banyak percobaan pencarian. Silakan tunggu beberapa menit.';
    } else {
        $orderId = filter_var($cleanOrderInput, FILTER_VALIDATE_INT);
        try {
            if (!$orderId || $orderId < 1) {
                throw new InvalidArgumentException('Nomor pesanan tidak valid.');
            }
            $validatedPhone = validate_phone(post_text('no_telepon', 30));

            $stmt = $koneksi->prepare(
                'SELECT p.*, t.nama_toko, t.lokasi AS lokasi_toko, t.no_telepon AS telepon_toko
                 FROM pesanan p
                 JOIN toko t ON t.id_toko = p.id_toko
                 WHERE p.id_pesanan = ?'
            );
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $found = $stmt->get_result()->fetch_assoc();

            if ($found && phones_match($validatedPhone, (string) $found['no_telepon'])) {
                clear_rate_limit('order_lookup');
                $order = $found;

                $detailStmt = $koneksi->prepare(
                    'SELECT nama_menu, harga_satuan, jumlah, subtotal
                     FROM detail_pesanan
                     WHERE id_pesanan = ?
                     ORDER BY id_detail ASC'
                );
                $detailStmt->bind_param('i', $orderId);
                $detailStmt->execute();
                $details = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                record_rate_attempt('order_lookup', $lookupWindow);
                $error = 'Pesanan tidak ditemukan atau nomor telepon tidak sesuai.';
            }
        } catch (Throwable $e) {
            record_rate_attempt('order_lookup', $lookupWindow);
            $error = 'Masukkan nomor pesanan dan nomor telepon yang valid.';
        }
    }
}

$statusMeta = [
    'baru' => [
        'label' => 'Menunggu Konfirmasi',
        'desc' => 'Pesanan Anda sudah diterima dan sedang menunggu konfirmasi penjual.',
        'badge' => 'status-badge-baru',
        'step' => 1,
    ],
    'diproses' => [
        'label' => 'Sedang Diproses',
        'desc' => 'Penjual sedang menyiapkan pesanan Anda.',
        'badge' => 'status-badge-diproses',
        'step' => 2,
    ],
    'selesai' => [
        'label' => 'Selesai',
        'desc' => 'Pesanan Anda telah selesai diproses. Terima kasih telah memesan!',
        'badge' => 'status-badge-selesai',
        'step' => 3,
    ],
    'dibatalkan' => [
        'label' => 'Dibatalkan',
        'desc' => 'Pesanan ini telah dibatalkan.',
        'badge' => 'status-badge-dibatalkan',
        'step' => 0,
    ],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pesanan – Street Food Gegerkalong</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="topbar">
        <a href="index.php" class="logo">
            <div class="logo-icon">🍜</div>
            <div class="logo-text">STREET FOOD<small>Gegerkalong</small></div>
        </a>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i> Daftar Toko</a>
    </header>

    <main class="track-wrap">
        <section class="track-card">
            <div class="track-header">
                <h1><i class="fas fa-receipt" aria-hidden="true"></i> Cek Status Pesanan</h1>
                <p>Masukkan nomor pesanan dan nomor telepon yang digunakan saat checkout untuk melihat status terbaru.</p>
            </div>

            <?php if ($error !== ''): ?>
            <div class="review-alert review-alert-error" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="cek_pesanan.php" class="track-form">
                <?= csrf_field() ?>
                <div class="track-field">
                    <label for="id_pesanan">Nomor Pesanan</label>
                    <input id="id_pesanan" name="id_pesanan" type="text" inputmode="numeric"
                        placeholder="Contoh: 12 atau #12" maxlength="15"
                        value="<?= e($inputOrderId) ?>" required>
                </div>
                <div class="track-field">
                    <label for="no_telepon">Nomor Telepon Pemesan</label>
                    <input id="no_telepon" name="no_telepon" type="tel"
                        placeholder="Contoh: 081234567890" maxlength="30"
                        pattern="[0-9+() \-]{6,30}"
                        value="<?= e($inputPhone) ?>" required>
                </div>
                <button type="submit" class="btn-track-submit">
                    <i class="fas fa-search" aria-hidden="true"></i> Lacak Pesanan
                </button>
            </form>
        </section>

        <?php if ($order):
            $statusKey = (string) ($order['status'] ?? 'baru');
            $meta = $statusMeta[$statusKey] ?? [
                'label' => ucfirst($statusKey),
                'desc' => '',
                'badge' => 'status-badge-baru',
                'step' => 1,
            ];
            $currentStep = (int) $meta['step'];
        ?>
        <section class="track-card track-result" aria-live="polite">
            <div class="track-result-top">
                <div>
                    <div class="track-order-id">Pesanan #<?= (int) $order['id_pesanan'] ?></div>
                    <h2><?= e($order['nama_toko']) ?></h2>
                    <p class="track-date">
                        <i class="far fa-clock" aria-hidden="true"></i>
                        <?= e(date('d M Y, H:i', strtotime((string) $order['tanggal_pesanan']))) ?> WIB
                    </p>
                </div>
                <span class="track-status-pill <?= e($meta['badge']) ?>">
                    <?= e($meta['label']) ?>
                </span>
            </div>

            <?php if ($meta['desc'] !== ''): ?>
            <p class="track-status-desc"><?= e($meta['desc']) ?></p>
            <?php endif; ?>

            <?php if ($statusKey !== 'dibatalkan'): ?>
            <div class="track-stepper" aria-label="Progres pesanan">
                <?php
                $steps = [
                    1 => ['icon' => 'fa-clipboard-check', 'label' => 'Diterima'],
                    2 => ['icon' => 'fa-fire-burner', 'label' => 'Diproses'],
                    3 => ['icon' => 'fa-circle-check', 'label' => 'Selesai'],
                ];
                foreach ($steps as $num => $step):
                    $isDone = $currentStep >= $num;
                    $isCurrent = $currentStep === $num;
                ?>
                <div class="track-step<?= $isDone ? ' is-done' : '' ?><?= $isCurrent ? ' is-current' : '' ?>">
                    <div class="track-step-dot">
                        <i class="fas <?= e($step['icon']) ?>" aria-hidden="true"></i>
                    </div>
                    <span><?= e($step['label']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="review-alert review-alert-error" style="margin-bottom:18px">
                <i class="fas fa-ban" aria-hidden="true"></i>
                <span>Pesanan dibatalkan. Silakan hubungi pihak toko jika membutuhkan informasi lebih lanjut.</span>
            </div>
            <?php endif; ?>

            <div class="track-meta">
                <div class="track-meta-row">
                    <span>Nama Pemesan</span>
                    <strong><?= e($order['nama_pemesan']) ?></strong>
                </div>
                <div class="track-meta-row">
                    <span>Nomor Telepon</span>
                    <strong><?= e($order['no_telepon']) ?></strong>
                </div>
                <?php if (!empty($order['lokasi_toko'])): ?>
                <div class="track-meta-row">
                    <span>Lokasi Toko</span>
                    <span><?= e($order['lokasi_toko']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['catatan'])): ?>
                <div class="track-meta-row">
                    <span>Catatan</span>
                    <span><?= e($order['catatan']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($details): ?>
            <div class="track-items">
                <h3>Rincian Menu</h3>
                <?php foreach ($details as $item): ?>
                <div class="track-item-row">
                    <span><?= e($item['nama_menu']) ?> × <?= (int) $item['jumlah'] ?></span>
                    <strong>Rp <?= number_format((int) $item['subtotal'], 0, ',', '.') ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="track-total">
                <span>Total Pembayaran</span>
                <span>Rp <?= number_format((int) $order['total'], 0, ',', '.') ?></span>
            </div>

            <div class="track-actions">
                <form method="POST" action="cek_pesanan.php" style="flex:1;min-width:160px;display:flex">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                    <input type="hidden" name="no_telepon" value="<?= e($inputPhone) ?>">
                    <button type="submit" class="track-btn-secondary" style="width:100%">
                        <i class="fas fa-rotate-right" aria-hidden="true"></i> Muat Ulang Status
                    </button>
                </form>
                <a class="track-btn-primary" href="page_menu.php?id_toko=<?= (int) $order['id_toko'] ?>">
                    <i class="fas fa-store" aria-hidden="true"></i> Kunjungi Toko
                </a>
            </div>
        </section>
        <?php endif; ?>
    </main>
</body>

</html>
