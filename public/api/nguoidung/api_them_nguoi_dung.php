<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$in = json_decode(file_get_contents('php://input'), true) ?: [];

foreach (['TenDangNhap','MatKhau','HoTen'] as $f) {
  if (empty($in[$f])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>"Thiếu $f"], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

$Ten   = trim($in['TenDangNhap']);
$Pass  = (string)$in['MatKhau'];     // có thể đổi sang password_hash nếu muốn
$HoTen = trim($in['HoTen']);
$Email = $in['Email'] ?? null;
$VaiTro= $in['VaiTro'] ?? null;

if ($Email && !filter_var($Email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Email không hợp lệ'], JSON_UNESCAPED_UNICODE);
  exit;
}

$st = $conn->prepare(
  "INSERT INTO nguoi_dung (TenDangNhap,MatKhau,HoTen,Email,VaiTro) VALUES (?,?,?,?,?)"
);
$st->bind_param('sssss', $Ten,$Pass,$HoTen,$Email,$VaiTro);

if (!$st->execute()) {
  // Trả chi tiết mã lỗi để biết đúng nguyên nhân
  $code = $conn->errno;  // ví dụ: 1062 (trùng), 1452 (FK fail), 1364 (NOT NULL)...
  $msg  = 'Lỗi hệ thống';
  if ($code == 1062) $msg = 'Trùng TenDangNhap hoặc Email';
  else if ($code == 1452) $msg = 'VaiTro không tồn tại (khóa ngoại)';
  else if ($code == 1364) $msg = 'Thiếu giá trị bắt buộc (NOT NULL)';
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>$msg,'code'=>$code,'detail'=>$conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(201);
echo json_encode([
  'success'=>true,'message'=>'Đã tạo',
  'data'=>['TenDangNhap'=>$Ten,'HoTen'=>$HoTen,'Email'=>$Email,'VaiTro'=>$VaiTro]
], JSON_UNESCAPED_UNICODE);
