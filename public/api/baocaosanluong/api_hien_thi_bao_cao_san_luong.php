<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../connect.php';

$id    = $_GET['ID'] ?? null;
$page  = max(1, intval($_GET['page']  ?? 1));
$limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

if ($id) {
  $stmt = $conn->prepare("SELECT * FROM bao_cao_san_luong WHERE ID=?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  if ($row) echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
  else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE); }
  exit;
}

$stmt = $conn->prepare("SELECT * FROM bao_cao_san_luong ORDER BY ID DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = $conn->query("SELECT COUNT(*) c FROM bao_cao_san_luong")->fetch_assoc()['c'] ?? 0;

echo json_encode([
  'success'=>true,
  'data'=>$data,
  'pagination'=>['current_page'=>$page,'per_page'=>$limit,'total'=>(int)$total,'total_pages'=>ceil($total/$limit)]
], JSON_UNESCAPED_UNICODE);
