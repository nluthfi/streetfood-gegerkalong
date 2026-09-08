<?php
require '../../app/config/security.php';
require_admin();
include '../../app/config/database.php';

$keyword = trim((string) ($_POST['keyword'] ?? ''));

$stmt = $koneksi->prepare('SELECT id_toko, nama_toko FROM toko WHERE nama_toko LIKE ? ORDER BY nama_toko ASC LIMIT 20');
$pattern = "%{$keyword}%";
$stmt->bind_param('s', $pattern);
$stmt->execute();
$query = $stmt->get_result();

echo '<option value="">-- Pilih Toko --</option>';

while($row = mysqli_fetch_assoc($query)){
    echo '<option value="' . (int) $row['id_toko'] . '">'
        . htmlspecialchars($row['nama_toko'], ENT_QUOTES, 'UTF-8') .
    '</option>';
}
?>