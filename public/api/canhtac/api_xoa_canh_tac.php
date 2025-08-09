<?php
// DELETE ?ID=...
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

$id = isset($_GET['ID']) ? intval($_GET['ID']) : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Cần ID']); exit; }

$stmt = $conn->prepare("DELETE FROM canh_tac WHERE ID = ?");
$stmt->bind_param('i',$id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi xóa','details'=>$stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success'=>true,'message'=>"Đã xóa ID='$id'"], JSON_UNESCAPED_UNICODE);
