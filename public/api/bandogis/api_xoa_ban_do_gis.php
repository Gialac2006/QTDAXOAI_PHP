<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Không tạo được kết nối DB'], JSON_UNESCAPED_UNICODE);
    exit;
}

parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
$MaVung = $q['MaVung'] ?? null;
if (!$MaVung) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Thiếu tham số MaVung'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra tồn tại
$chk = $conn->prepare("SELECT 1 FROM ban_do_gis WHERE MaVung=?");
$chk->bind_param('s', $MaVung);
$chk->execute();
if (!$chk->get_result()->fetch_row()) {
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>"MaVung '$MaVung' không tồn tại"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $del = $conn->prepare("DELETE FROM ban_do_gis WHERE MaVung=?");
    $del->bind_param('s', $MaVung);
    $del->execute();

    echo json_encode([
        'success'=>true,
        'message'=>"Đã xóa bản ghi MaVung='$MaVung'"
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi xóa','details'=>$e->getMessage()],
        JSON_UNESCAPED_UNICODE);
}
