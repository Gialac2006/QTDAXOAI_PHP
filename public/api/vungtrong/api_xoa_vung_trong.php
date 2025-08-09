<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$pk = $_GET['MaVung'] ?? null;
if (!$pk) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu MaVung'], JSON_UNESCAPED_UNICODE); exit; }

$st = $conn->prepare("DELETE FROM vung_trong WHERE MaVung=?");
$st->bind_param('s',$pk); $st->execute();

if ($st->affected_rows>0) echo json_encode(['success'=>true,'message'=>'Đã xóa'], JSON_UNESCAPED_UNICODE);
else {
  if ($st->errno==1451) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không thể xóa: đang được tham chiếu (FK)'], JSON_UNESCAPED_UNICODE); }
  else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); }
}
