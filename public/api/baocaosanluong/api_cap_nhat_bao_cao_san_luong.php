<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
$id = $q['ID'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Cần ID'], JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?? [];

$map = ['MaVung'=>'s','MaMuaVu'=>'s','SanLuong'=>'d','ChatLuong'=>'s','GhiChu'=>'s'];
$sets=[]; $types=''; $vals=[];
foreach ($map as $k=>$t) if (array_key_exists($k,$in)) { $sets[]="$k=?"; $types.=$t; $vals[] = ($t==='d')? floatval($in[$k]) : $in[$k]; }

if (!$sets) { echo json_encode(['success'=>false,'error'=>'Không có trường cập nhật'], JSON_UNESCAPED_UNICODE); exit; }

$sql = "UPDATE bao_cao_san_luong SET ".implode(',',$sets)." WHERE ID=?";
$types .= 'i'; $vals[] = (int)$id;

$stmt = $conn->prepare($sql);
array_unshift($vals, $types);
$refs=[]; foreach ($vals as $i=>&$v) $refs[$i]=&$v;
call_user_func_array([$stmt,'bind_param'],$refs);

try {
  $stmt->execute();
  $r = $conn->query("SELECT * FROM bao_cao_san_luong WHERE ID=".(int)$id)->fetch_assoc();
  echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$r], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Lỗi cập nhật','details'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
