<?php
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
if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

$username = mysqli_real_escape_string($conn, trim($input['username']));
$email = mysqli_real_escape_string($conn, trim($input['email']));
$password = $input['password'];

// Validate dữ liệu
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập phải có ít nhất 3 ký tự']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
    exit;
}

// Kiểm tra username đã tồn tại
$check_username = "SELECT TenDangNhap FROM nguoi_dung WHERE TenDangNhap = '$username'";
$result_username = mysqli_query($conn, $check_username);
if (mysqli_num_rows($result_username) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập đã tồn tại']);
    mysqli_close($conn);
    exit;
}

// Kiểm tra email đã tồn tại
$check_email = "SELECT Email FROM nguoi_dung WHERE Email = '$email'";
$result_email = mysqli_query($conn, $check_email);
if (mysqli_num_rows($result_email) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email đã được sử dụng']);
    mysqli_close($conn);
    exit;
}

// Mã hóa mật khẩu
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$hashedPassword = mysqli_real_escape_string($conn, $hashedPassword);

// Thêm user mới vào database
$insert_query = "INSERT INTO nguoi_dung (TenDangNhap, MatKhau, HoTen, Email, VaiTro) 
                VALUES ('$username', '$hashedPassword', '$username', '$email', 'User')";

if (mysqli_query($conn, $insert_query)) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Đăng ký thành công! Vui lòng đăng nhập.'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra khi đăng ký: ' . mysqli_error($conn)]);
}

// Đóng kết nối
mysqli_close($conn);
?>