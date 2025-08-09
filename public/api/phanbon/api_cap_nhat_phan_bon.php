<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$pk = $_GET['TenPhanBon'] ?? null;
if(!$pk){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenPhanBon'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$fields = ['Loai','DonViTinh','GhiChu'];
$sets=[]; $vals=[];
foreach($fields as $f) if(array_key_exists($f,$in)){ $sets[]="$f=?"; $vals[]=$in[$f]; }
if(!$sets){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql = "UPDATE phan_bon SET ".implode(',', $sets)." WHERE TenPhanBon=?";
$vals[]=$pk; $types=str_repeat('s', count($vals));
$st=$conn->prepare($sql); $st->bind_param($types, ...$vals); $st->execute();

if($st->affected_rows===0){
  $ck=$conn->prepare("SELECT 1 FROM phan_bon WHERE TenPhanBon=?"); $ck->bind_param('s',$pk); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
