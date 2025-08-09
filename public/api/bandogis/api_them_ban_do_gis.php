<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php'; // từ bandogis/ đi lên api/
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Không tạo được kết nối DB'], JSON_UNESCAPED_UNICODE);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);
if (!$in || !isset($in['MaVung'], $in['ToaDo'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Thiếu MaVung/ToaDo'], JSON_UNESCAPED_UNICODE);
    exit;
}

$MaVung       = trim($in['MaVung']);
$ToaDo        = $in['ToaDo'];
$NhanTen      = $in['NhanTen']      ?? null;
$ThongTinPopup= $in['ThongTinPopup']?? null;

try {
    $chk = $conn->prepare("SELECT 1 FROM ban_do_gis WHERE MaVung=?");
    $chk->bind_param('s', $MaVung);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['success'=>false,'error'=>'MaVung đã tồn tại'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO ban_do_gis (MaVung, ToaDo, NhanTen, ThongTinPopup)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('ssss', $MaVung, $ToaDo, $NhanTen, $ThongTinPopup);
    $stmt->execute();

    $sel = $conn->prepare("SELECT * FROM ban_do_gis WHERE MaVung=?");
    $sel->bind_param('s', $MaVung);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();

    echo json_encode(['success'=>true,'message'=>'Thêm thành công','data'=>$row],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi thêm','details'=>$e->getMessage()],
        JSON_UNESCAPED_UNICODE);
}
