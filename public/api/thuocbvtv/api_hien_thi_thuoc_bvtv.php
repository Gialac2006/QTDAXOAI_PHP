<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$q = $_GET['q'] ?? null;
if ($q !== null && $q !== '') {
  $like = '%'.$q.'%';
  $st = $conn->prepare("SELECT TenThuoc,HoatChat,DonViTinh,GhiChu
                        FROM thuoc_bvtv
                        WHERE TenThuoc LIKE ? OR HoatChat LIKE ?
                        ORDER BY TenThuoc");
  $st->bind_param('ss',$like,$like); $st->execute(); $rs=$st->get_result();
} else {
  $rs = $conn->query("SELECT TenThuoc,HoatChat,DonViTinh,GhiChu FROM thuoc_bvtv ORDER BY TenThuoc");
}
echo json_encode(['success'=>true,'data'=>$rs->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
