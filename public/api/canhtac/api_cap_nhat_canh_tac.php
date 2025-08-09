<?php
// PUT ?ID=...  body JSON: gửi các field cần sửa (không bắt buộc gửi hết)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

$id = isset($_GET['ID']) ? intval($_GET['ID']) : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Cần ID']); exit; }

$check = $conn->prepare("SELECT 1 FROM canh_tac WHERE ID=?");
$check->bind_param('i',$id);
$check->execute();
if (!$check->get_result()->fetch_row()) {
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>"ID '$id' không tồn tại"], JSON_UNESCAPED_UNICODE);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);
$fields = [
    'NgayThucHien'=>'s', 'LoaiCongViec'=>'s', 'NguoiThucHien'=>'s',
    'MaVung'=>'s', 'TenPhanBon'=>'s', 'LieuLuong'=>'d', 'GhiChu'=>'s'
];

$sets=[]; $types=''; $vals=[];
foreach ($fields as $f=>$t) {
    if (array_key_exists($f, $in)) {
        $sets[]  = "$f = ?";
        $types  .= $t;
        $vals[]  = ($t==='d') ? floatval($in[$f]) : $in[$f];
    }
}

if (!$sets) { echo json_encode(['success'=>false,'error'=>'Không có trường cập nhật']); exit; }

$types .= 'i';
$vals[]  = $id;

$sql = "UPDATE canh_tac SET ".implode(', ',$sets)." WHERE ID = ?";
$stmt = $conn->prepare($sql);

// bind_param động
$params = [];
$params[] = &$types;
foreach ($vals as $k=>$v) { $params[] = &$vals[$k]; }
call_user_func_array([$stmt,'bind_param'],$params);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi cập nhật','details'=>$stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$r = $conn->prepare("SELECT * FROM canh_tac WHERE ID=?");
$r->bind_param('i',$id);
$r->execute();
$upd = $r->get_result()->fetch_assoc();

echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd], JSON_UNESCAPED_UNICODE);
