<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
foreach (['MaVung','TenVung'] as $f)
  if (empty($in[$f])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>"Thiếu $f"], JSON_UNESCAPED_UNICODE); exit; }
if (!empty($in['NgayBatDau']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $in['NgayBatDau'])) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>'NgayBatDau phải YYYY-MM-DD'], JSON_UNESCAPED_UNICODE); exit;
}

$MaVung     = trim($in['MaVung']);
$TenVung    = trim($in['TenVung']);
$DiaChi     = $in['DiaChi']     ?? null;
$DienTich   = isset($in['DienTich']) ? (string)$in['DienTich'] : null; // để MySQL tự cast
$TinhTrang  = $in['TinhTrang']  ?? null;   // null -> dùng DEFAULT
$NgayBatDau = $in['NgayBatDau'] ?? null;
$MaHo       = $in['MaHo']       ?? null;
$MaGiong    = $in['MaGiong']    ?? null;

$st = $conn->prepare("INSERT INTO vung_trong (MaVung,TenVung,DiaChi,DienTich,TinhTrang,NgayBatDau,MaHo,MaGiong)
                      VALUES (?,?,?,?,?,?,?,?)");
$st->bind_param('ssssssss', $MaVung,$TenVung,$DiaChi,$DienTich,$TinhTrang,$NgayBatDau,$MaHo,$MaGiong);
if (!$st->execute()) {
  $msg = $conn->errno==1062 ? 'Trùng MaVung' : ($conn->errno==1452 ? 'MaHo/MaGiong không tồn tại (FK)' : $conn->error);
  http_response_code(400); echo json_encode(['success'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit;
}
http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>['MaVung'=>$MaVung]], JSON_UNESCAPED_UNICODE);
