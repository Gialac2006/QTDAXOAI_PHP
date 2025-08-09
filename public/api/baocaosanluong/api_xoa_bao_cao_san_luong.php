<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

$id = $_GET['ID'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Cần ID'], JSON_UNESCAPED_UNICODE); exit; }

$stmt = $conn->prepare("DELETE FROM bao_cao_san_luong WHERE ID=?");
$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo json_encode(['success'=>true,'message'=>"Đã xóa ID=$id"], JSON_UNESCAPED_UNICODE);
} else {
  http_response_code(404);
  echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE);
}
