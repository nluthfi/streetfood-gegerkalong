<header class="admin-topbar">
    <a class="admin-brand" href="<?= app_url('admin/dashboard.php') ?>">
        <span class="admin-brand-icon">🍜</span>
        <span class="admin-brand-text">STREET FOOD<small>GEGERKALONG</small></span>
    </a>

    <nav class="admin-nav">

        <a class="nav-link" href="<?= app_url('admin/dashboard.php') ?>">
            <span class="icon">
                <i class="fa-solid fa-house"></i>
            </span>
            <span class="description">Dashboard</span>
        </a>

        <a class="nav-link" href="<?= app_url('admin/kategori/kategori.php') ?>">
            <span class="icon">
                <i class="fa-solid fa-grip"></i>
            </span>
            <span class="description">Kategori</span>
        </a>

        <a class="nav-link" href="<?= app_url('admin/mitra/mitra.php') ?>">
            <span class="icon">
                <i class="fa-solid fa-building-columns"></i>
            </span>
            <span class="description">Mitra</span>
        </a>

        <a class="nav-link" href="<?= app_url('admin/bayar/bayar.php') ?>">
            <span class="icon">
                <i class="fa-solid fa-money-bill-wave"></i>
            </span>
            <span class="description">Metode Pembayaran</span>
        </a>

        <a class="nav-link" href="<?= app_url('admin/pesanan/pesanan.php') ?>">
            <span class="icon"><i class="fa-solid fa-receipt"></i></span>
            <span class="description">Pesanan</span>
        </a>

        <a class="nav-link" href="<?= app_url('admin/toko/toko.php') ?>">
            <span class="icon"><i class="fa-solid fa-store"></i></span>
            <span class="description">Toko</span>
        </a>

        <a class="nav-link" href="<?= app_url('admin/menu/menu.php') ?>">
            <span class="icon"><i class="fa-solid fa-clipboard-list"></i></span>
            <span class="description">Menu</span>
        </a>

        <form action="<?= app_url('admin/auth/logout.php') ?>" method="POST" class="admin-logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="admin-logout">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </form>

    </nav>
</header>