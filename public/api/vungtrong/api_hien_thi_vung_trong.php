<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$q   = $_GET['q']      ?? null;   // lọc nhanh theo tên
$ho  = $_GET['MaHo']   ?? null;   // tùy chọn
$giong = $_GET['MaGiong'] ?? null;

$sql = "SELECT MaVung,TenVung,DiaChi,DienTich,TinhTrang,NgayBatDau,MaHo,MaGiong FROM vung_trong";
$cond=[]; $types=''; $vals=[];
if ($q)     { $cond[]="TenVung LIKE ?"; $types.='s'; $vals[]='%'.$q.'%'; }
if ($ho)    { $cond[]="MaHo=?";         $types.='s'; $vals[]=$ho; }
if ($giong) { $cond[]="MaGiong=?";      $types.='s'; $vals[]=$giong; }
if ($cond)  $sql .= " WHERE ".implode(' AND ', $cond);
$sql .= " ORDER BY MaVung";

if ($cond){ $st=$conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute(); $rs=$st->get_result(); }
else { $rs=$conn->query($sql); }

echo json_encode(['success'=>true,'data'=>$rs->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
