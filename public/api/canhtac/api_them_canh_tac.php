<?php
// POST body JSON: NgayThucHien (YYYY-MM-DD HH:MM:SS), LoaiCongViec, NguoiThucHien, MaVung, TenPhanBon, LieuLuong, GhiChu?
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';


$in = json_decode(file_get_contents('php://input'), true);

// validate bắt buộc (tùy bạn nới lỏng thêm nếu muốn)
$required = ['NgayThucHien','LoaiCongViec','NguoiThucHien','MaVung','TenPhanBon','LieuLuong'];
foreach ($required as $f) {
    if (!isset($in[$f]) || trim((string)$in[$f])==='') {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>"Thiếu trường $f"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$ngay  = $in['NgayThucHien']; // 'YYYY-MM-DD HH:MM:SS'
$loai  = $in['LoaiCongViec'];
$nguoi = $in['NguoiThucHien'];
$vung  = $in['MaVung'];
$pb    = $in['TenPhanBon'];
$lieu  = floatval($in['LieuLuong']);
$ghi   = $in['GhiChu'] ?? null;

$stmt = $conn->prepare("
    INSERT INTO canh_tac (NgayThucHien, LoaiCongViec, NguoiThucHien, MaVung, TenPhanBon, LieuLuong, GhiChu)
    VALUES (?,?,?,?,?,?,?)
");
$stmt->bind_param('sssssds', $ngay, $loai, $nguoi, $vung, $pb, $lieu, $ghi);

if (!$stmt->execute()) {
    // lỗi FK (vùng/ phân bón chưa có) hay lỗi khác
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi thêm', 'details'=>$stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$newId = $conn->insert_id;
$r = $conn->prepare("SELECT * FROM canh_tac WHERE ID=?");
$r->bind_param('i', $newId);
$r->execute();
$new = $r->get_result()->fetch_assoc();

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Thêm thành công','data'=>$new], JSON_UNESCAPED_UNICODE);
