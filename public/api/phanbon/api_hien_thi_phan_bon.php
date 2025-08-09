<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$q = $_GET['q'] ?? null; // lọc nhanh theo tên (tùy chọn)
if ($q) {
  $like = "%$q%";
  $st = $conn->prepare("SELECT TenPhanBon,Loai,DonViTinh,GhiChu FROM phan_bon WHERE TenPhanBon LIKE ? ORDER BY TenPhanBon");
  $st->bind_param('s',$like); $st->execute();
  $rs = $st->get_result();
} else {
  $rs = $conn->query("SELECT TenPhanBon,Loai,DonViTinh,GhiChu FROM phan_bon ORDER BY TenPhanBon");
}
echo json_encode(['success'=>true,'data'=>$rs->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
