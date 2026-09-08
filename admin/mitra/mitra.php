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
                <h4>Data Mitra</h4>
                <p>Mitra Street Food yang tersedia</p>
            </div>

            
            <a href="tambah_mitra.php">
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
                            <th>Nama Mitra</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                            $query = "SELECT * FROM mitra";

                            $result = mysqli_query($koneksi, $query);
                            $i = 1;

                            while($row = mysqli_fetch_assoc($result)){
                        ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($row['nama_mitra'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form action="hapus_mitra.php" method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id_mitra']; ?>">
                                    <button type="submit" class="btn-action delete" aria-label="Hapus mitra">
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

