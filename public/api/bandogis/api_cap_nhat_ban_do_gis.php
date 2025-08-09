<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
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

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = [];

$set = [];
$types = '';
$params = [];

// Chỉ cập nhật 3 field này nếu có gửi lên
if (array_key_exists('ToaDo', $in))          { $set[]="ToaDo=?";          $types.='s'; $params[]=$in['ToaDo']; }
if (array_key_exists('NhanTen', $in))        { $set[]="NhanTen=?";        $types.='s'; $params[]=$in['NhanTen']; }
if (array_key_exists('ThongTinPopup', $in))  { $set[]="ThongTinPopup=?";  $types.='s'; $params[]=$in['ThongTinPopup']; }

if (!$set) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Không có trường nào để cập nhật'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "UPDATE ban_do_gis SET ".implode(', ', $set)." WHERE MaVung=?";
$types .= 's';
$params[] = $MaVung;

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    // trả về bản ghi sau cập nhật
    $sel = $conn->prepare("SELECT * FROM ban_do_gis WHERE MaVung=?");
    $sel->bind_param('s', $MaVung);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();

    echo json_encode([
        'success'=>true,
        'message'=>'Cập nhật thành công',
        'data'=>$row
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi cập nhật','details'=>$e->getMessage()],
        JSON_UNESCAPED_UNICODE);
}
