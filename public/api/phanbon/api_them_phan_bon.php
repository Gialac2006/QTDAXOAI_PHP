<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($in['TenPhanBon'])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu TenPhanBon'], JSON_UNESCAPED_UNICODE); exit; }

$TenPhanBon = trim($in['TenPhanBon']);
$Loai       = $in['Loai'] ?? null;
$DonViTinh  = $in['DonViTinh'] ?? null;
$GhiChu     = $in['GhiChu'] ?? null;

$st = $conn->prepare("INSERT INTO phan_bon (TenPhanBon,Loai,DonViTinh,GhiChu) VALUES (?,?,?,?)");
$st->bind_param('ssss', $TenPhanBon,$Loai,$DonViTinh,$GhiChu);
if(!$st->execute()){ http_response_code($conn->errno==1062?409:500); echo json_encode(['success'=>false,'error'=>$conn->errno==1062?'Trùng TenPhanBon':$conn->error], JSON_UNESCAPED_UNICODE); exit; }

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>['TenPhanBon'=>$TenPhanBon,'Loai'=>$Loai,'DonViTinh'=>$DonViTinh,'GhiChu'=>$GhiChu]], JSON_UNESCAPED_UNICODE);
