<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/connect.php';
session_start();

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$u = trim($in['TenDangNhap'] ?? ''); $p = (string)($in['MatKhau'] ?? '');
if ($u===''||$p===''){ echo json_encode(['success'=>false,'error'=>'Thiếu TenDangNhap/MatKhau']); exit; }

$q = $conn->prepare("SELECT TenDangNhap,MatKhau,HoTen,Email,VaiTro FROM nguoi_dung WHERE TenDangNhap=? LIMIT 1");
$q->bind_param("s",$u); $q->execute(); $user=$q->get_result()->fetch_assoc(); $q->close();
if(!$user){ echo json_encode(['success'=>false,'error'=>'Sai tài khoản hoặc mật khẩu']); exit; }

$ok = password_verify($p,$user['MatKhau']);
if(!$ok && hash_equals($user['MatKhau'],$p)){ // nâng cấp plaintext -> hash
  $new = password_hash($p,PASSWORD_DEFAULT);
  $up = $conn->prepare("UPDATE nguoi_dung SET MatKhau=? WHERE TenDangNhap=?");
  $up->bind_param("ss",$new,$u); $up->execute(); $up->close();
  $ok = true;
}
if(!$ok){ echo json_encode(['success'=>false,'error'=>'Sai tài khoản hoặc mật khẩu']); exit; }

$_SESSION['user']=['TenDangNhap'=>$user['TenDangNhap'],'HoTen'=>$user['HoTen'],'Email'=>$user['Email'],'VaiTro'=>$user['VaiTro']];
echo json_encode(['success'=>true,'message'=>'Đăng nhập thành công','token'=>session_id(),'user'=>$_SESSION['user']], JSON_UNESCAPED_UNICODE);
