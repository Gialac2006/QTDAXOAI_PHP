<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
foreach (['MaThietBi','TenThietBi'] as $f)
  if (empty($in[$f])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>"Thiếu $f"], JSON_UNESCAPED_UNICODE); exit; }
if (!empty($in['NgayLapDat']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$in['NgayLapDat'])) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>'NgayLapDat phải YYYY-MM-DD'], JSON_UNESCAPED_UNICODE); exit;
}

$MaThietBi = trim($in['MaThietBi']);
$TenThietBi= trim($in['TenThietBi']);
$NgayLapDat= $in['NgayLapDat'] ?? null;
$TinhTrang = $in['TinhTrang']  ?? null;
$MaVung    = $in['MaVung']     ?? null;
$MaKetNoi  = $in['MaKetNoi']   ?? null;

$st = $conn->prepare("INSERT INTO thiet_bi_iot (MaThietBi,TenThietBi,NgayLapDat,TinhTrang,MaVung,MaKetNoi)
                      VALUES (?,?,?,?,?,?)");
$st->bind_param('ssssss', $MaThietBi,$TenThietBi,$NgayLapDat,$TinhTrang,$MaVung,$MaKetNoi);
if (!$st->execute()) {
  $msg = $conn->errno==1062 ? 'Trùng MaThietBi' : ($conn->errno==1452 ? 'MaVung không tồn tại (FK)' : $conn->error);
  http_response_code(400); echo json_encode(['success'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit;
}
http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>['MaThietBi'=>$MaThietBi]], JSON_UNESCAPED_UNICODE);
