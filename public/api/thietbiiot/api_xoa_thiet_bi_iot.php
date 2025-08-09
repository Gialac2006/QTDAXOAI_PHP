<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$pk = $_GET['MaThietBi'] ?? null;
if (!$pk) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu MaThietBi'], JSON_UNESCAPED_UNICODE); exit; }

$st = $conn->prepare("DELETE FROM thiet_bi_iot WHERE MaThietBi=?");
$st->bind_param('s', $pk); $st->execute();

if ($st->affected_rows>0) echo json_encode(['success'=>true,'message'=>'Đã xóa'], JSON_UNESCAPED_UNICODE);
else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); }
