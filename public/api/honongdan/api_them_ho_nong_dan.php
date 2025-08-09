<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }
require_once __DIR__ . '/../connect.php';
function out($d,$c=200){ http_response_code($c); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$need = ['MaHo','TenChuHo','CCCD'];
foreach ($need as $f) if (empty($in[$f])) out(['success'=>false,'error'=>"Thiếu $f"],400);

$MaHo = trim($in['MaHo']);
$Ten  = trim($in['TenChuHo']);
$CCCD = trim($in['CCCD']);
$NgaySinh = $in['NgaySinh'] ?? null;
$SDT  = $in['SoDienThoai'] ?? null;
$DiaChi = $in['DiaChi'] ?? null;
$SoTV = isset($in['SoThanhVien']) ? intval($in['SoThanhVien']) : null;
$LoaiDat = $in['LoaiDat'] ?? null;
$DienTich= isset($in['DienTich']) ? floatval($in['DienTich']) : null;

// trùng MaHo
$ck = $conn->prepare("SELECT 1 FROM ho_nong_dan WHERE MaHo=?");
$ck->bind_param('s',$MaHo); $ck->execute();
if ($ck->get_result()->fetch_row()) out(['success'=>false,'error'=>'MaHo đã tồn tại'],409);

// trùng CCCD (unique)
$ck = $conn->prepare("SELECT 1 FROM ho_nong_dan WHERE CCCD=?");
$ck->bind_param('s',$CCCD); $ck->execute();
if ($ck->get_result()->fetch_row()) out(['success'=>false,'error'=>'CCCD đã tồn tại'],409);

$st = $conn->prepare("INSERT INTO ho_nong_dan
 (MaHo,TenChuHo,CCCD,NgaySinh,SoDienThoai,DiaChi,SoThanhVien,LoaiDat,DienTich)
 VALUES (?,?,?,?,?,?,?,?,?)");
$st->bind_param('ssssssisd', $MaHo,$Ten,$CCCD,$NgaySinh,$SDT,$DiaChi,$SoTV,$LoaiDat,$DienTich);

if (!$st->execute()) out(['success'=>false,'error'=>'Lỗi thêm','details'=>$st->error],500);

$r = $conn->prepare("SELECT * FROM ho_nong_dan WHERE MaHo=?");
$r->bind_param('s',$MaHo); $r->execute();
out(['success'=>true,'message'=>'Thêm thành công','data'=>$r->get_result()->fetch_assoc()],201);
