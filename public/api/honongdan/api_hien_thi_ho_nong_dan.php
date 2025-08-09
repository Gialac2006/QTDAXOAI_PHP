<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__ . '/../connect.php';

function out($data,$code=200){ http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit;
}

$ma = isset($_GET['MaHo']) ? trim($_GET['MaHo']) : null;

if ($ma) {
  $st = $conn->prepare("SELECT * FROM ho_nong_dan WHERE MaHo=?");
  $st->bind_param('s',$ma); $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if (!$row) out(['success'=>false,'error'=>'Không tìm thấy'],404);
  out(['success'=>true,'data'=>$row]);
}

$page  = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
$offset= ($page-1)*$limit;
$q     = trim($_GET['q'] ?? '');

if ($q!=='') {
  $like = "%$q%";
  $c = $conn->prepare("SELECT COUNT(*) c FROM ho_nong_dan WHERE TenChuHo LIKE ? OR CCCD LIKE ?");
  $c->bind_param('ss',$like,$like); $c->execute();
  $total = $c->get_result()->fetch_assoc()['c'] ?? 0;

  $st = $conn->prepare("SELECT * FROM ho_nong_dan WHERE TenChuHo LIKE ? OR CCCD LIKE ? ORDER BY MaHo LIMIT ? OFFSET ?");
  $st->bind_param('ssii',$like,$like,$limit,$offset);
} else {
  $total = $conn->query("SELECT COUNT(*) c FROM ho_nong_dan")->fetch_assoc()['c'] ?? 0;
  $st = $conn->prepare("SELECT * FROM ho_nong_dan ORDER BY MaHo LIMIT ? OFFSET ?");
  $st->bind_param('ii',$limit,$offset);
}
$st->execute();
$data = $st->get_result()->fetch_all(MYSQLI_ASSOC);

out([
  'success'=>true,
  'data'=>$data,
  'meta'=>['page'=>$page,'limit'=>$limit,'total'=>$total]
]);
