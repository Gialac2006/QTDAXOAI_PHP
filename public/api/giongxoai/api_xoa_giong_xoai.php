<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/../connect.php';

/** Lấy id từ query (?MaGiong=) hoặc từ path .../delete/{id} */
$ma = $_GET['MaGiong'] ?? null;
if (!$ma) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path,'/'));            // .../api/giongxoai/delete/GX001
    $idx = array_search('giongxoai', $parts);
    if ($idx !== false && isset($parts[$idx+2])) $ma = urldecode($parts[$idx+2]); // phần sau 'delete'
}

if (!$ma) { http_response_code(400); echo json_encode(['error'=>'Thiếu MaGiong']); exit; }

$stmt = $conn->prepare("DELETE FROM giongxoai WHERE MaGiong=?");
$stmt->bind_param("s", $ma);
$stmt->execute();

echo json_encode(['deleted' => $stmt->affected_rows, 'MaGiong'=>$ma]);
