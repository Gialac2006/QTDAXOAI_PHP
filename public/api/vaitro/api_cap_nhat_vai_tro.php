<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$pk = $_GET['TenVaiTro'] ?? null;
if(!$pk){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenVaiTro'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (!array_key_exists('MoTa',$in)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$st = $conn->prepare("UPDATE vai_tro SET MoTa=? WHERE TenVaiTro=?");
$st->bind_param('ss', $in['MoTa'], $pk); $st->execute();

if($st->affected_rows===0){
  $ck=$conn->prepare("SELECT 1 FROM vai_tro WHERE TenVaiTro=?"); $ck->bind_param('s',$pk); $ck->execute();
  echo json_encode($ck->get_result()->fetch_row()?['success'=>true,'message'=>'Không có thay đổi']:['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(['success'=>true,'message'=>'Đã cập nhật'], JSON_UNESCAPED_UNICODE);
