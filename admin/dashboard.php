<?php
include '../app/config/app.php';
require_admin();

include '../app/views/layout/header.php';
include '../app/views/layout/sidebar.php';
include '../app/config/database.php';

// Total Mitra
$total_mitra = mysqli_fetch_assoc(
    mysqli_query($koneksi, "
        SELECT COUNT(*) AS total_mitra 
        FROM mitra
    ")
);

// Total Toko
$total_toko = mysqli_fetch_assoc(
    mysqli_query($koneksi, "
        SELECT COUNT(*) AS total_toko 
        FROM toko
    ")
);

// Total Menu
$total_menu = mysqli_fetch_assoc(
    mysqli_query($koneksi, "
        SELECT COUNT(*) AS total_menu 
        FROM menu
    ")
);

// Total Pembayaran
$total_pembayaran = mysqli_fetch_assoc(
    mysqli_query($koneksi, "
        SELECT COUNT(*) AS total_pembayaran 
        FROM bayar
    ")
);
?>

<!-- Main Content -->
<main class="main-content dashboard-page">

    <!-- Header -->
    <section class="dashboard-hero">
        <h1>Dashboard Admin</h1>
        <p>Kelola data Street Food Gegerkalong dari satu tempat</p>
    </section>

    <!-- Statistik -->
    <div class="dashboard-stats">

        <!-- TOTAL MITRA -->
        <article class="dashboard-card">

                <div>
                    <h6>Total Mitra</h6>

                    <h3>
                        <?= $total_mitra['total_mitra']; ?>
                    </h3>
                </div>

                <div class="card-icon">
                    <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                </div>

        </article>

        <!-- TOTAL TOKO -->
        <article class="dashboard-card">

                <div>
                    <h6>Total Toko</h6>

                    <h3>
                        <?= $total_toko['total_toko']; ?>
                    </h3>
                </div>

                <div class="card-icon">
                    <i class="fa-solid fa-store" aria-hidden="true"></i>
                </div>

        </article>

        <!-- TOTAL MENU -->
        <article class="dashboard-card">

                <div>
                    <h6>Total Menu</h6>

                    <h3>
                        <?= $total_menu['total_menu']; ?>
                    </h3>
                </div>

                <div class="card-icon">
                    <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                </div>

        </article>

        <!-- TOTAL PEMBAYARAN -->
        <article class="dashboard-card">

                <div>
                    <h6>Pembayaran</h6>

                    <h3>
                        <?= $total_pembayaran['total_pembayaran']; ?>
                    </h3>
                </div>

                <div class="card-icon">
                    <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                </div>

        </article>

    </div>

</main>

<?php include '../app/views/layout/footer.php'; ?>
