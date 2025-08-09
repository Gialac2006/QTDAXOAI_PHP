<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__.'/../connect.php';

parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
$id = $q['ID'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Thiếu ID'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$fields = ['MaThietBi'=>'s','NgayBaoTri'=>'s','NoiDung'=>'s','TrangThai'=>'s'];

$sets=[]; $types=''; $vals=[];
foreach ($fields as $k=>$t) {
    if (array_key_exists($k,$in)) {
        $sets[] = "$k=?";
        $types .= $t;
        $vals[] = $in[$k];
    }
}

if (!$sets) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Không có dữ liệu cập nhật'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$sql = "UPDATE lich_bao_tri SET ".implode(',',$sets)." WHERE ID=?";
$st = $conn->prepare($sql);
$types .= 'i'; $vals[] = (int)$id;

$bind = array_merge([$types], $vals);
$refs = [];
foreach($bind as $i=>$v){ $refs[$i] = &$bind[$i]; }
call_user_func_array([$st,'bind_param'], $refs);

if (!$st->execute()){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi cập nhật','details'=>$st->error], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

$g = $conn->prepare("SELECT * FROM lich_bao_tri WHERE ID=?");
$g->bind_param('i',$id);
$g->execute();
$row = $g->get_result()->fetch_assoc();

if (!$row){ http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
