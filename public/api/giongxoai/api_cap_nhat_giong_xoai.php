<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

$ma = $_GET['MaGiong'] ?? null;
if (!$ma){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Cần MaGiong để cập nhật']); exit; }

$chk = $conn->prepare("SELECT 1 FROM giong_xoai WHERE MaGiong=?");
$chk->bind_param('s',$ma); $chk->execute();
if (!$chk->get_result()->fetch_row()){ http_response_code(404); echo json_encode(['success'=>false,'error'=>'MaGiong không tồn tại']); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$fields = [
  'TenGiong'=>'s','ThoiGianTruongThanh'=>'i','NangSuatTrungBinh'=>'d','DacDiem'=>'s','TinhTrang'=>'s'
];
$sets=[]; $types=''; $vals=[];
foreach ($fields as $f=>$t){ if(isset($in[$f])){ $sets[]="$f=?"; $types.=$t; $vals[]=$in[$f]; } }
if (!$sets){ echo json_encode(['success'=>false,'error'=>'Không có trường để cập nhật']); exit; }

$sql = "UPDATE giong_xoai SET ".implode(',', $sets)." WHERE MaGiong=?";
$types .= 's'; $vals[] = $ma;
$st = $conn->prepare($sql);
$bind = array_merge([$types], $vals); // bind_param by ref
$tmp = [];
foreach ($bind as $k=>$v) $tmp[$k] =& $bind[$k];
call_user_func_array([$st,'bind_param'],$tmp);

if(!$st->execute()){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'Lỗi cập nhật','details'=>$st->error]); exit; }

$r = $conn->prepare("SELECT * FROM giong_xoai WHERE MaGiong=?");
$r->bind_param('s',$ma); $r->execute();
echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$r->get_result()->fetch_assoc()]);
