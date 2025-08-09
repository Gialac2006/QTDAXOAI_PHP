<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__.'/../connect.php';
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (empty($in['MaThietBi']) || empty($in['NgayBaoTri'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Thiếu MaThietBi hoặc NgayBaoTri'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

$ma = trim($in['MaThietBi']);
$ngay = $in['NgayBaoTri'];
$nd = $in['NoiDung'] ?? null;
$tt = $in['TrangThai'] ?? 'Đang xử lí';

$st = $conn->prepare("INSERT INTO lich_bao_tri (MaThietBi, NgayBaoTri, NoiDung, TrangThai) VALUES (?,?,?,?)");
$st->bind_param('ssss', $ma,$ngay,$nd,$tt);

if (!$st->execute()){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi thêm','details'=>$st->error], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

$newId = $conn->insert_id;
$g = $conn->prepare("SELECT * FROM lich_bao_tri WHERE ID=?");
$g->bind_param('i',$newId);
$g->execute();
$row = $g->get_result()->fetch_assoc();

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Thêm thành công','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
