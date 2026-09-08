<?php
require '../../app/config/security.php';
require_admin();
verify_csrf();
require '../../app/config/database.php';
try {
    $id=post_int('id');
    $status=post_text('status',20);
    if(!in_array($status,['baru','diproses','selesai','dibatalkan'],true)) throw new InvalidArgumentException('Status tidak valid.');
    $stmt=$koneksi->prepare('UPDATE pesanan SET status=? WHERE id_pesanan=?');$stmt->bind_param('si',$status,$id);$stmt->execute();
    header('Location: pesanan.php'); exit;
} catch(Throwable $e){admin_error($e,'Status pesanan gagal diperbarui.');}
