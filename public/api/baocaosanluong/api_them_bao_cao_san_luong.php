<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';  // dùng connect.php cũ của bạn
$conn->set_charset('utf8mb4');

$in = json_decode(file_get_contents('php://input'), true);
if (!$in) $in = $_POST; // cho phép x-www-form-urlencoded

$maVung  = trim($in['MaVung']  ?? '');
$maMuaVu = trim($in['MaMuaVu'] ?? '');
$sanLuong = isset($in['SanLuong']) ? floatval($in['SanLuong']) : null;
$chatLuong = $in['ChatLuong'] ?? null;
$ghiChu    = $in['GhiChu']    ?? null;

if ($maVung==='' || $maMuaVu==='' || $sanLuong===null) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Thiếu MaVung, MaMuaVu hoặc SanLuong'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // chèn
  $stmt = $conn->prepare(
    "INSERT INTO bao_cao_san_luong (MaVung, MaMuaVu, SanLuong, ChatLuong, GhiChu)
     VALUES (?,?,?,?,?)"
  );
  $stmt->bind_param('ssdss', $maVung, $maMuaVu, $sanLuong, $chatLuong, $ghiChu);
  $ok = $stmt->execute();

  if (!$ok || $stmt->affected_rows !== 1) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi thêm', 'details'=>$stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $newId = $conn->insert_id;

  // lấy lại bản ghi vừa thêm
  $sel = $conn->prepare("SELECT * FROM bao_cao_san_luong WHERE ID = ?");
  $sel->bind_param('i', $newId);
  $sel->execute();
  $row = $sel->get_result()->fetch_assoc();

  http_response_code(201);
  echo json_encode(['success'=>true,'message'=>'Thêm thành công','data'=>$row], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Lỗi server','details'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
