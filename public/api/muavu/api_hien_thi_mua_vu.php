<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$ma  = $_GET['MaMuaVu'] ?? null;   // <-- dùng khóa chính
$nam = $_GET['nam']      ?? null;

if ($ma) {
  $st = $conn->prepare("SELECT MaMuaVu,Nam,Dot,NgayBatDau,NgayKetThuc FROM mua_vu WHERE MaMuaVu=?");
  $st->bind_param('s',$ma); $st->execute();
  $r = $st->get_result()->fetch_assoc();
  echo json_encode(['success'=>(bool)$r,'data'=>$r,'error'=>$r?null:'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
if ($nam) {
  $st = $conn->prepare("SELECT MaMuaVu,Nam,Dot,NgayBatDau,NgayKetThuc FROM mua_vu WHERE Nam=? ORDER BY MaMuaVu DESC");
  $st->bind_param('s',$nam); $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
  $rows = $conn->query("SELECT MaMuaVu,Nam,Dot,NgayBatDau,NgayKetThuc FROM mua_vu ORDER BY Nam DESC, MaMuaVu DESC")->fetch_all(MYSQLI_ASSOC);
}
echo json_encode(['success'=>true,'total'=>count($rows),'data'=>$rows], JSON_UNESCAPED_UNICODE);
