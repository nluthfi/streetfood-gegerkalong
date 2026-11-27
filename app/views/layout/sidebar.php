<?php
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$navItems = [
    ['url' => 'admin/dashboard.php', 'match' => '/admin/dashboard.php', 'icon' => 'fa-house', 'label' => 'Dashboard'],
    ['url' => 'admin/kategori/kategori.php', 'match' => '/admin/kategori/', 'icon' => 'fa-grip', 'label' => 'Kategori'],
    ['url' => 'admin/mitra/mitra.php', 'match' => '/admin/mitra/', 'icon' => 'fa-building-columns', 'label' => 'Mitra'],
    ['url' => 'admin/bayar/bayar.php', 'match' => '/admin/bayar/', 'icon' => 'fa-money-bill-wave', 'label' => 'Metode Pembayaran'],
    ['url' => 'admin/pesanan/pesanan.php', 'match' => '/admin/pesanan/', 'icon' => 'fa-receipt', 'label' => 'Pesanan'],
    ['url' => 'admin/toko/toko.php', 'match' => '/admin/toko/', 'icon' => 'fa-store', 'label' => 'Toko'],
    ['url' => 'admin/menu/menu.php', 'match' => '/admin/menu/', 'icon' => 'fa-clipboard-list', 'label' => 'Menu'],
];
?>
<header class="admin-topbar">
    <a class="admin-brand" href="<?= app_url('admin/dashboard.php') ?>">
        <span class="admin-brand-icon">🍜</span>
        <span class="admin-brand-text">STREET FOOD<small>GEGERKALONG</small></span>
    </a>

    <nav class="admin-nav" aria-label="Navigasi admin">
        <?php foreach ($navItems as $item):
            $isActive = str_contains($currentScript, $item['match']);
        ?>
        <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= app_url($item['url']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <span class="icon">
                <i class="fa-solid <?= e($item['icon']) ?>" aria-hidden="true"></i>
            </span>
            <span class="description"><?= e($item['label']) ?></span>
        </a>
        <?php endforeach; ?>

        <form action="<?= app_url('admin/auth/logout.php') ?>" method="POST" class="admin-logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="admin-logout">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </form>
    </nav>
</header>
