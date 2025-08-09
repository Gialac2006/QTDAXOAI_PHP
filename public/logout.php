<?php
// public/logout.php
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

// Nếu gọi bằng link (GET) -> redirect về trang login
$redirect = $_GET['redirect'] ?? '/QTDAXOAI_PHP/public/login.html';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
  header("Location: $redirect", true, 302);
  exit;
}

// Nếu gọi bằng fetch/AJAX -> trả JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>true, 'message'=>'Đã đăng xuất', 'redirect'=>$redirect], JSON_UNESCAPED_UNICODE);
