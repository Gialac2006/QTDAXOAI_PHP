<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$tk = $_GET['TenDangNhap'] ?? null;
if (!$tk) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenDangNhap'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$allow = ['MatKhau','HoTen','Email','VaiTro'];
$sets=[]; $vals=[];
foreach ($allow as $f) if (array_key_exists($f,$in)) {
  if ($f==='Email' && $in[$f] && !filter_var($in[$f], FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Email không hợp lệ'], JSON_UNESCAPED_UNICODE); exit; }
  $sets[]="$f=?"; $vals[]=$in[$f];
}
if (!$sets) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql="UPDATE nguoi_dung SET ".implode(',',$sets)." WHERE TenDangNhap=?";
$vals[]=$tk; $types=str_repeat('s',count($vals));
$st=$conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute();

if ($st->errno==1062){ http_response_code(409); echo json_encode(['success'=>false,'error'=>'Email đã tồn tại'], JSON_UNESCAPED_UNICODE); exit; }
if ($st->affected_rows===0){
  $ck=$conn->prepare("SELECT 1 FROM nguoi_dung WHERE TenDangNhap=?"); $ck->bind_param('s',$tk); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
