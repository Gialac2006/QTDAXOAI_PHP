<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$pk = $_GET['MaVung'] ?? null;
if (!$pk) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu MaVung'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (isset($in['NgayBatDau']) && $in['NgayBatDau'] && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$in['NgayBatDau'])) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>'NgayBatDau phải YYYY-MM-DD'], JSON_UNESCAPED_UNICODE); exit;
}

$allow = ['TenVung','DiaChi','DienTich','TinhTrang','NgayBatDau','MaHo','MaGiong'];
$sets=[]; $vals=[];
foreach ($allow as $f) if (array_key_exists($f,$in)) { $sets[]="$f=?"; $vals[]=(string)$in[$f]; }
if (!$sets) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql = "UPDATE vung_trong SET ".implode(',', $sets)." WHERE MaVung=?";
$vals[]=$pk; $st=$conn->prepare($sql); $st->bind_param(str_repeat('s', count($vals)), ...$vals); $st->execute();

if ($st->errno==1452){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'MaHo/MaGiong không tồn tại (FK)'], JSON_UNESCAPED_UNICODE); exit; }
if ($st->affected_rows===0){
  $ck=$conn->prepare("SELECT 1 FROM vung_trong WHERE MaVung=?"); $ck->bind_param('s',$pk); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
