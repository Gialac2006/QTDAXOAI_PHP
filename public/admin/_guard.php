<?php
// Fixed _guard.php - Bảo vệ khu vực admin với debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Kiểm tra session
error_log('Session data: ' . print_r($_SESSION, true));

// Kiểm tra đăng nhập
if (empty($_SESSION['user'])) {
    error_log('Guard: Không có session user');
    header('Location: ../../login.html?error=not_logged_in');
    exit;
}

$userRole = $_SESSION['user']['VaiTro'] ?? '';
error_log('Guard: User role = ' . $userRole);

// Kiểm tra quyền admin
if (strtolower(trim($userRole)) !== 'admin') {
    error_log('Guard: User không phải admin, role = ' . $userRole);
    header('Location: ../../login.html?error=access_denied');
    exit;
}

// Nếu đến đây thì user đã đăng nhập và là admin
error_log('Guard: Admin access granted for user: ' . ($_SESSION['user']['TenDangNhap'] ?? 'unknown'));
?>