<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$u = trim($in['TenDangNhap'] ?? ''); $p = (string)($in['MatKhau'] ?? '');
$h = trim($in['HoTen'] ?? '');      $e = trim($in['Email'] ?? '');
$r = trim($in['VaiTro'] ?? 'User');

if ($u===''||$p===''||$h===''){ echo json_encode(['success'=>false,'error'=>'Thiếu trường bắt buộc']); exit; }
if ($e!=='' && !filter_var($e, FILTER_VALIDATE_EMAIL)){ echo json_encode(['success'=>false,'error'=>'Email không hợp lệ']); exit; }

$role = $conn->prepare("SELECT 1 FROM vai_tro WHERE TenVaiTro=? LIMIT 1");
$role->bind_param("s",$r); $role->execute();
if(!$role->get_result()->num_rows) $r='User'; $role->close();

$dup = $conn->prepare("SELECT 1 FROM nguoi_dung WHERE TenDangNhap=? OR Email=? LIMIT 1");
$dup->bind_param("ss",$u,$e); $dup->execute();
if($dup->get_result()->num_rows){ echo json_encode(['success'=>false,'error'=>'TenDangNhap hoặc Email đã tồn tại']); exit; }
$dup->close();

$hash = password_hash($p, PASSWORD_DEFAULT);
$ins = $conn->prepare("INSERT INTO nguoi_dung(TenDangNhap,MatKhau,HoTen,Email,VaiTro) VALUES (?,?,?,?,?)");
$ins->bind_param("sssss",$u,$hash,$h,$e,$r);
if(!$ins->execute()){ echo json_encode(['success'=>false,'error'=>$conn->error]); exit; }
echo json_encode(['success'=>true,'message'=>'Đăng ký thành công','data'=>['TenDangNhap'=>$u,'HoTen'=>$h,'Email'=>$e,'VaiTro'=>$r]], JSON_UNESCAPED_UNICODE);
