<?php include '../../app/views/layout/header.php'; ?>
<?php include '../../app/views/layout/sidebar.php'; ?>
<?php include '../../app/config/database.php';?>


<!-- Main Content -->
<main class="main-content">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="fw-bold">STREET FOOD</h2>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div>
                <h4>Data Metode Pembayaran</h4>
                <p>Metode pembayaran yang tersedia</p>
            </div>

            <a href="tambah_bayar.php">
            <button class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Data
            </button>
            </a>

        </div>

        <!-- Table -->
        <div class="table-responsive">
            <div class="table-scroll">
                <table class="custom-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Metode Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                            $query = "SELECT * FROM bayar";
                            $result = mysqli_query($koneksi, $query);
                            $i = 1;
                            while($row = mysqli_fetch_assoc($result)){
                        ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($row['metode_pembayaran'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form action="hapus_bayar.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id_metode']; ?>">
                                    <button type="submit" class="btn-action delete" aria-label="Hapus metode pembayaran">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>

                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>

                </table>

            </div>
        </div>

</div>
</main>

<?php include '../../app/views/layout/footer.php'; ?>

