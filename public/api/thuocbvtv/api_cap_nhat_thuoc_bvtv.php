<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$pk = $_GET['TenThuoc'] ?? null;
if(!$pk){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenThuoc'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$fields = ['HoatChat','DonViTinh','GhiChu'];
$sets=[]; $vals=[];
foreach($fields as $f) if(array_key_exists($f,$in)){ $sets[]="$f=?"; $vals[]=$in[$f]; }
if(!$sets){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql = "UPDATE thuoc_bvtv SET ".implode(',', $sets)." WHERE TenThuoc=?";
$vals[]=$pk; $types=str_repeat('s', count($vals));
$st=$conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute();

if($st->affected_rows===0){
  $ck=$conn->prepare("SELECT 1 FROM thuoc_bvtv WHERE TenThuoc=?"); $ck->bind_param('s',$pk); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
