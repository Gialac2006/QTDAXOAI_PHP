<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($in['TenVaiTro'])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenVaiTro'], JSON_UNESCAPED_UNICODE); exit; }

$Ten = trim($in['TenVaiTro']); $MoTa = $in['MoTa'] ?? null;
$st = $conn->prepare("INSERT INTO vai_tro (TenVaiTro, MoTa) VALUES (?,?)");
$st->bind_param('ss',$Ten,$MoTa);
if(!$st->execute()){ http_response_code($conn->errno==1062?409:500); echo json_encode(['success'=>false,'error'=>$conn->errno==1062?'Trùng TenVaiTro':$conn->error], JSON_UNESCAPED_UNICODE); exit; }

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>['TenVaiTro'=>$Ten,'MoTa'=>$MoTa]], JSON_UNESCAPED_UNICODE);
