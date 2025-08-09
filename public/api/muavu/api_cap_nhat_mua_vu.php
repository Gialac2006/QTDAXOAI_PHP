<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$ma = $_GET['MaMuaVu'] ?? null; // <-- KHÓA CHÍNH
if (!$ma) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu MaMuaVu'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$allow = ['Nam','Dot','NgayBatDau','NgayKetThuc'];
$sets=[]; $vals=[];
foreach ($allow as $f) {
  if (array_key_exists($f,$in)) {
    if (in_array($f,['NgayBatDau','NgayKetThuc']) && $in[$f] && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$in[$f])) {
      http_response_code(400); echo json_encode(['success'=>false,'error'=>"$f phải YYYY-MM-DD"], JSON_UNESCAPED_UNICODE); exit;
    }
    $sets[]="$f=?"; $vals[]=$in[$f];
  }
}
if (!$sets) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql = "UPDATE mua_vu SET ".implode(',',$sets)." WHERE MaMuaVu=?";
$vals[] = $ma; $types = str_repeat('s', count($vals));
$st = $conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute();

if ($st->affected_rows===0) {
  $ck=$conn->prepare("SELECT 1 FROM mua_vu WHERE MaMuaVu=?"); $ck->bind_param('s',$ma); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
