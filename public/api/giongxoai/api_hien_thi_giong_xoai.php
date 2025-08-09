<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../connect.php';

$ma = $_GET['MaGiong'] ?? null;
$page  = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

if ($ma) {
    $st = $conn->prepare("SELECT * FROM giong_xoai WHERE MaGiong=?");
    $st->bind_param('s', $ma); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) echo json_encode(['success'=>true,'data'=>$row]);
    else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy']); }
    exit;
}

$st = $conn->prepare("SELECT * FROM giong_xoai LIMIT ? OFFSET ?");
$st->bind_param('ii',$limit,$offset); $st->execute();
$data = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$total = $conn->query("SELECT COUNT(*) c FROM giong_xoai")->fetch_assoc()['c'] ?? 0;

echo json_encode([
  'success'=>true,
  'data'=>$data,
  'pagination'=>[
    'current_page'=>$page,'per_page'=>$limit,'total'=>(int)$total,
    'total_pages'=>ceil($total/$limit)
  ]
]);
