<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$tk = $_GET['TenDangNhap'] ?? null;
if (!$tk) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenDangNhap'], JSON_UNESCAPED_UNICODE); exit; }

$st=$conn->prepare("DELETE FROM nguoi_dung WHERE TenDangNhap=?");
$st->bind_param('s',$tk); $st->execute();

if ($st->affected_rows>0) echo json_encode(['success'=>true,'message'=>'Đã xóa'], JSON_UNESCAPED_UNICODE);
else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); }
