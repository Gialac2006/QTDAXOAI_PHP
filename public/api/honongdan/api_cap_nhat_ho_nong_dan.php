<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }
require_once __DIR__ . '/../connect.php';
function out($d,$c=200){ http_response_code($c); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$MaHo = isset($_GET['MaHo']) ? trim($_GET['MaHo']) : null;
if (!$MaHo) out(['success'=>false,'error'=>'Thiếu MaHo'],400);

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$allow = ['TenChuHo','CCCD','NgaySinh','SoDienThoai','DiaChi','SoThanhVien','LoaiDat','DienTich'];
$set=[]; $vals=[]; $types='';
foreach ($allow as $f){
  if (array_key_exists($f,$in)){
    $set[] = "$f=?";
    if ($f==='SoThanhVien') { $types.='i'; $vals[] = $in[$f]!=='' ? intval($in[$f]) : null; }
    elseif ($f==='DienTich') { $types.='d'; $vals[] = $in[$f]!=='' ? floatval($in[$f]) : null; }
    else { $types.='s'; $vals[] = $in[$f]!=='' ? $in[$f] : null; }
  }
}
if (!$set) out(['success'=>false,'error'=>'Không có gì để cập nhật'],400);

// nếu cập nhật CCCD -> kiểm tra trùng
if (isset($in['CCCD'])){
  $ck = $conn->prepare("SELECT 1 FROM ho_nong_dan WHERE CCCD=? AND MaHo<>?");
  $ck->bind_param('ss',$in['CCCD'],$MaHo); $ck->execute();
  if ($ck->get_result()->fetch_row()) out(['success'=>false,'error'=>'CCCD đã tồn tại'],409);
}

$sql = "UPDATE ho_nong_dan SET ".implode(',',$set)." WHERE MaHo=?";
$types .= 's'; $vals[] = $MaHo;

$st = $conn->prepare($sql);
$st->bind_param($types, ...$vals);
if (!$st->execute()) out(['success'=>false,'error'=>'Lỗi cập nhật','details'=>$st->error],500);

$r = $conn->prepare("SELECT * FROM ho_nong_dan WHERE MaHo=?");
$r->bind_param('s',$MaHo); $r->execute();
out(['success'=>true,'message'=>'Cập nhật thành công','data'=>$r->get_result()->fetch_assoc()]);
