<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$ma = $_GET['MaMuaVu'] ?? null; // <-- KHÓA CHÍNH
if (!$ma) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu MaMuaVu'], JSON_UNESCAPED_UNICODE); exit; }

$st = $conn->prepare("DELETE FROM mua_vu WHERE MaMuaVu=?");
$st->bind_param('s',$ma); $st->execute();

if ($st->affected_rows>0) echo json_encode(['success'=>true,'message'=>'Đã xóa'], JSON_UNESCAPED_UNICODE);
else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); }
