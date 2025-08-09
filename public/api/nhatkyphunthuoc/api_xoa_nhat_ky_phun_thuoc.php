<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$id = $_GET['ID'] ?? null; // KHÓA CHÍNH
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu ID'], JSON_UNESCAPED_UNICODE); exit; }

$st=$conn->prepare("DELETE FROM nhat_ky_phun_thuoc WHERE ID=?");
$st->bind_param('i',$id); $st->execute();

if ($st->affected_rows>0) echo json_encode(['success'=>true,'message'=>'Đã xóa'], JSON_UNESCAPED_UNICODE);
else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); }
