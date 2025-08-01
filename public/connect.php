<?php
$servername = "localhost";
$database = "qlvtxoai";
$username = "root";
$password = "password";  // Sử dụng mật khẩu nếu bạn đã đặt

// Tạo kết nối với MySQL
$conn = mysqli_connect($servername, $username, $password, $database, 3307);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

echo "Kết nối thành công!";

// Đóng kết nối
mysqli_close($conn);
?>
