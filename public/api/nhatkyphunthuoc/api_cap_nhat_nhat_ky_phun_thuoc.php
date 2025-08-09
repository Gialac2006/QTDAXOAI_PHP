<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$id = $_GET['ID'] ?? null; // KHÓA CHÍNH
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu ID'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$allow = ['NgayPhun','TenNguoiPhun','MaVung','TenThuoc','LieuLuong','GhiChu'];

$sets=[]; $vals=[]; $types='';
foreach ($allow as $f) if (array_key_exists($f,$in)) {
  if ($f==='NgayPhun' && $in[$f] && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$in[$f])) {
    http_response_code(400); echo json_encode(['success'=>false,'error'=>'NgayPhun phải YYYY-MM-DD'], JSON_UNESCAPED_UNICODE); exit;
  }
  $sets[]="$f=?";
  if ($f==='LieuLuong') { $types.='d'; $vals[]=(float)$in[$f]; }
  else { $types.='s'; $vals[]=$in[$f]; }
}
if (!$sets) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql = "UPDATE nhat_ky_phun_thuoc SET ".implode(',',$sets)." WHERE ID=?";
$types.='i'; $vals[]=(int)$id;

$st=$conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute();
if ($st->errno==1452){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'MaVung hoặc TenThuoc không tồn tại (khóa ngoại)'], JSON_UNESCAPED_UNICODE); exit; }

if ($st->affected_rows===0) {
  $ck=$conn->prepare("SELECT 1 FROM nhat_ky_phun_thuoc WHERE ID=?");
  $ck->bind_param('i',$id); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
