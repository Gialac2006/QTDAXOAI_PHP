<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
foreach(['NgayPhun','MaVung','TenThuoc'] as $f)
  if (empty($in[$f])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>"Thiếu $f"], JSON_UNESCAPED_UNICODE); exit; }

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $in['NgayPhun'])) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>'NgayPhun phải YYYY-MM-DD'], JSON_UNESCAPED_UNICODE); exit;
}

$NgayPhun     = $in['NgayPhun'];
$TenNguoiPhun = $in['TenNguoiPhun'] ?? null;
$MaVung       = $in['MaVung'];
$TenThuoc     = $in['TenThuoc'];
$LieuLuong    = isset($in['LieuLuong']) ? (string)$in['LieuLuong'] : null; // để MySQL tự cast
$GhiChu       = $in['GhiChu'] ?? null;
// auto thêm tên thuốc nếu chưa có
$insThuoc = $conn->prepare("INSERT IGNORE INTO thuoc_bvtv (TenThuoc) VALUES (?)");
$insThuoc->bind_param('s', $TenThuoc); $insThuoc->execute();

$st = $conn->prepare("INSERT INTO nhat_ky_phun_thuoc (NgayPhun,TenNguoiPhun,MaVung,TenThuoc,LieuLuong,GhiChu)
                      VALUES (?,?,?,?,?,?)");
$st->bind_param('ssssss', $NgayPhun, $TenNguoiPhun, $MaVung, $TenThuoc, $LieuLuong, $GhiChu);

if (!$st->execute()) {
  $msg = ($conn->errno==1452) ? 'MaVung/TenThuoc không tồn tại (FK)' : $conn->error;
  http_response_code(400); echo json_encode(['success'=>false,'error'=>$msg,'code'=>$conn->errno], JSON_UNESCAPED_UNICODE); exit;
}

http_response_code(201);
echo json_encode(['success'=>true,'ID'=>$conn->insert_id], JSON_UNESCAPED_UNICODE);
