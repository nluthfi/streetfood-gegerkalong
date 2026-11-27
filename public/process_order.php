<?php
require_once '../app/config/security.php';
start_secure_session();
verify_csrf();
require_once '../app/config/database.php';

try {
    $idToko = post_int('id_toko');
    $nama = post_text('nama', 100);
    $telepon = post_text('telepon', 30);
    $catatan = post_text('catatan', 500, false);
    if (!preg_match('/^[0-9+() -]{6,30}$/', $telepon)) throw new InvalidArgumentException('Nomor telepon tidak valid.');
    $cart = json_decode((string)($_POST['cart'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($cart) || !$cart || count($cart) > 50) throw new InvalidArgumentException('Keranjang tidak valid.');

    $ids=[];
    foreach ($cart as $id=>$item) {
        $menuId=filter_var($id,FILTER_VALIDATE_INT); $qty=is_array($item)?filter_var($item['qty']??null,FILTER_VALIDATE_INT):false;
        if(!$menuId || $qty===false || $qty<1 || $qty>99) throw new InvalidArgumentException('Isi keranjang tidak valid.');
        $ids[]=$menuId;
    }
    $ph=implode(',',array_fill(0,count($ids),'?')); $types=str_repeat('i',count($ids)).'i'; $bind=$ids; $bind[]=$idToko;
    $stmt=$koneksi->prepare("SELECT id_menu,nama_menu,harga,id_toko FROM menu WHERE id_menu IN ($ph) AND id_toko=?"); $stmt->bind_param($types,...$bind); $stmt->execute(); $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if(count($rows)!==count($ids)) throw new InvalidArgumentException('Ada menu yang sudah tidak tersedia.');
    $total=0; foreach($rows as $r){$total+=(int)$r['harga']*(int)$cart[$r['id_menu']]['qty'];}

    $koneksi->begin_transaction();
    $stmt=$koneksi->prepare('INSERT INTO pesanan (id_toko,nama_pemesan,no_telepon,catatan,total,status) VALUES (?,?,?,?,?,?)');
    $status='baru'; $stmt->bind_param('isssis',$idToko,$nama,$telepon,$catatan,$total,$status); $stmt->execute(); $idPesanan=$koneksi->insert_id;
    $detail=$koneksi->prepare('INSERT INTO detail_pesanan (id_pesanan,id_menu,nama_menu,harga_satuan,jumlah,subtotal) VALUES (?,?,?,?,?,?)');
    foreach($rows as $r){$qty=(int)$cart[$r['id_menu']]['qty'];$sub=(int)$r['harga']*$qty;$detail->bind_param('iisiii',$idPesanan,$r['id_menu'],$r['nama_menu'],$r['harga'],$qty,$sub);$detail->execute();}
    $koneksi->commit();
    header('Location: order_success.php?id=' . $idPesanan . '&toko=' . $idToko); exit;
} catch(Throwable $e){if($koneksi->in_transaction)$koneksi->rollback(); http_response_code(400); exit(e($e->getMessage()));}
