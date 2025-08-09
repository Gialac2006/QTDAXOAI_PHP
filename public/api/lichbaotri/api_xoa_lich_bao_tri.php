<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__.'/../connect.php';

$id = $_GET['ID'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu ID'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$st = $conn->prepare("DELETE FROM lich_bao_tri WHERE ID=?");
$st->bind_param('i',$id);
$st->execute();

if ($st->affected_rows < 1) {
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>'Không tìm thấy hoặc đã xóa'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

echo json_encode(['success'=>true,'message'=>"Đã xóa ID=$id"], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
