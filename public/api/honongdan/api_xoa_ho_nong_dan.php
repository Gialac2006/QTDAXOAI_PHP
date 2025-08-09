<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }
require_once __DIR__ . '/../connect.php';
function out($d,$c=200){ http_response_code($c); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$MaHo = isset($_GET['MaHo']) ? trim($_GET['MaHo']) : null;
if (!$MaHo) out(['success'=>false,'error'=>'Thiếu MaHo'],400);

$st = $conn->prepare("DELETE FROM ho_nong_dan WHERE MaHo=?");
$st->bind_param('s',$MaHo);
if (!$st->execute()){
  // rơi vào ràng buộc FK (đang được tham chiếu ở vung_trong)
  if ($conn->errno == 1451){
    out(['success'=>false,'error'=>'Không thể xoá vì đang được liên kết ở bảng vung_trong'],409);
  }
  out(['success'=>false,'error'=>'Lỗi xoá','details'=>$st->error],500);
}
if ($st->affected_rows===0) out(['success'=>false,'error'=>'MaHo không tồn tại'],404);

out(['success'=>true,'message'=>'Xoá thành công']);
