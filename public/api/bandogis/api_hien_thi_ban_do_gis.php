<?php
// File: public/api/bandogis/api_hien_thi_ban_do_gis.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__).'/connect.php';
$conn->set_charset('utf8mb4');

$MaVung = $_GET['MaVung'] ?? null;

if ($MaVung) {
    $stmt = $conn->prepare("SELECT * FROM ban_do_gis WHERE MaVung=?");
    $stmt->bind_param('s', $MaVung);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success'=>true,'data'=>$row],
            JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(404);
        if (ob_get_length()) ob_clean();
        echo json_encode(['success'=>false,'error'=>'Không tìm thấy MaVung'],
            JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    return;
}

// list có phân trang
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
$offset = ($page-1)*$limit;

$stmt = $conn->prepare("SELECT * FROM ban_do_gis LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = $conn->query("SELECT COUNT(*) c FROM ban_do_gis")->fetch_assoc()['c'] ?? 0;

if (ob_get_length()) ob_clean();
echo json_encode([
    'success'=>true,
    'data'=>$data,
    'pagination'=>[
        'current_page'=>$page,
        'per_page'=>$limit,
        'total'=>(int)$total,
        'total_pages'=>ceil($total/$limit)
    ]
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
