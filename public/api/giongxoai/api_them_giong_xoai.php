<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in) || !$in) $in = $_POST;

$required = ['MaGiong','TenGiong'];
foreach ($required as $f) {
    if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>"Thiếu $f"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$ma  = trim($in['MaGiong']);
$ten = trim($in['TenGiong']);
$tg  = isset($in['ThoiGianTruongThanh']) ? (int)$in['ThoiGianTruongThanh'] : null;
$ns  = isset($in['NangSuatTrungBinh'])   ? (float)$in['NangSuatTrungBinh']   : null;
$dd  = $in['DacDiem']   ?? null;
$tt  = $in['TinhTrang'] ?? 'Còn dùng';

// trùng mã?
$ck = $conn->prepare("SELECT 1 FROM giong_xoai WHERE MaGiong=?");
$ck->bind_param('s', $ma);
$ck->execute();
if ($ck->get_result()->fetch_row()) {
    http_response_code(409);
    echo json_encode(['success'=>false,'error'=>'MaGiong đã tồn tại'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "INSERT INTO giong_xoai
        (MaGiong, TenGiong, ThoiGianTruongThanh, NangSuatTrungBinh, DacDiem, TinhTrang)
        VALUES (?,?,?,?,?,?)";
$st = $conn->prepare($sql);
$st->bind_param('ssids' . 's', $ma, $ten, $tg, $ns, $dd, $tt); // s s i d s s

if (!$st->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi thêm','details'=>$st->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$sel = $conn->prepare("SELECT * FROM giong_xoai WHERE MaGiong=?");
$sel->bind_param('s', $ma);
$sel->execute();
$data = $sel->get_result()->fetch_assoc();

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Thêm thành công','data'=>$data], JSON_UNESCAPED_UNICODE);
