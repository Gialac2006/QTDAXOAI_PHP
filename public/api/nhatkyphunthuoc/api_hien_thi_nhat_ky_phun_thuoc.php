<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

// (tuỳ chọn) filter: ?MaVung=...&TuNgay=YYYY-MM-DD&DenNgay=YYYY-MM-DD
$mv = $_GET['MaVung']  ?? null;
$tu = $_GET['TuNgay']  ?? null;
$den= $_GET['DenNgay'] ?? null;

$sql = "SELECT ID,NgayPhun,TenNguoiPhun,MaVung,TenThuoc,LieuLuong,GhiChu
        FROM nhat_ky_phun_thuoc";
$types=''; $vals=[];
$cond=[];
if ($mv)  { $cond[]="MaVung=?";   $types.='s'; $vals[]=$mv; }
if ($tu)  { $cond[]="NgayPhun>=?";$types.='s'; $vals[]=$tu; }
if ($den) { $cond[]="NgayPhun<=?";$types.='s'; $vals[]=$den; }
if ($cond) $sql .= " WHERE ".implode(' AND ',$cond);
$sql .= " ORDER BY NgayPhun DESC, ID DESC";

if ($cond) { $st=$conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute(); $rs=$st->get_result(); }
else { $rs=$conn->query($sql); }

$rows = $rs->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success'=>true,'total'=>count($rows),'data'=>$rows], JSON_UNESCAPED_UNICODE);
