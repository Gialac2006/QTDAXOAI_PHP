<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Chỉ cho phép POST request']);
    exit;
}

// Kết nối database
require_once '../connect.php';

// Lấy dữ liệu JSON từ client
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['username']) || !isset($input['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin đăng nhập']);
    exit;
}

$username = mysqli_real_escape_string($conn, trim($input['username']));
$password = $input['password'];

// Validate dữ liệu
if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// Tìm user trong database
$query = "SELECT TenDangNhap, MatKhau, HoTen, Email, VaiTro FROM nguoi_dung WHERE TenDangNhap = '$username'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập không tồn tại']);
    mysqli_close($conn);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Kiểm tra mật khẩu
if (!password_verify($password, $user['MatKhau'])) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không chính xác']);
    mysqli_close($conn);
    exit;
}

// Đăng nhập thành công - lưu session
$_SESSION['user_id'] = $user['TenDangNhap'];
$_SESSION['user_name'] = $user['HoTen'];
$_SESSION['user_role'] = $user['VaiTro'];
$_SESSION['user_email'] = $user['Email'];

// Trả về thông tin user (không bao gồm mật khẩu)
echo json_encode([
    'status' => 'success',
    'message' => 'Đăng nhập thành công!',
    'user' => [
        'username' => $user['TenDangNhap'],
        'name' => $user['HoTen'],
        'email' => $user['Email'],
        'role' => $user['VaiTro']
    ]
]);

// Đóng kết nối
mysqli_close($conn);
?>