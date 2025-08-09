<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($in['TenThuoc'])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenThuoc'], JSON_UNESCAPED_UNICODE); exit; }

$TenThuoc  = trim($in['TenThuoc']);
$HoatChat  = $in['HoatChat']  ?? null;
$DonViTinh = $in['DonViTinh'] ?? null;
$GhiChu    = $in['GhiChu']    ?? null;

$st = $conn->prepare("INSERT INTO thuoc_bvtv (TenThuoc,HoatChat,DonViTinh,GhiChu) VALUES (?,?,?,?)");
$st->bind_param('ssss',$TenThuoc,$HoatChat,$DonViTinh,$GhiChu);
if(!$st->execute()){ http_response_code($conn->errno==1062?409:500); echo json_encode(['success'=>false,'error'=>$conn->errno==1062?'Trùng TenThuoc':$conn->error], JSON_UNESCAPED_UNICODE); exit; }

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>['TenThuoc'=>$TenThuoc,'HoatChat'=>$HoatChat,'DonViTinh'=>$DonViTinh,'GhiChu'=>$GhiChu]], JSON_UNESCAPED_UNICODE);
