<?php
// File: ban_do_gis.php
// Bảng ban_do_gis cấu trúc:
//   MaVung VARCHAR(20) PK, ToaDo TEXT, NhanTen VARCHAR(100), ThongTinPopup TEXT
//   FK: MaVung → vung_trong.MaVung :contentReference[oaicite:1]{index=1}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Xử lý preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cấu hình kết nối MySQL
$host = 'localhost';
$db   = 'qlvtxoai';
$user = 'root';
$pass = 'password';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Kết nối database thất bại']);
    exit;
}

// Kiểm tra tồn tại vung_trong.MaVung
function existsVung($conn, $maVung) {
    $stmt = $conn->prepare("SELECT 1 FROM vung_trong WHERE MaVung = ?");
    $stmt->bind_param('s', $maVung);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

// Router
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':    handleGet($conn);    break;
    case 'POST':   handlePost($conn);   break;
    case 'PUT':    handlePut($conn);    break;
    case 'DELETE': handleDelete($conn); break;
    default:
        http_response_code(405);
        echo json_encode(['error'=>'Method not allowed']);
}
$conn->close();

// GET: /ban_do_gis.php?MaVung=... hoặc list với phân trang
function handleGet($conn) {
    $maVung = $_GET['MaVung'] ?? null;
    $page   = max(1, intval($_GET['page']  ?? 1));
    $limit  = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($maVung) {
        $stmt = $conn->prepare("SELECT * FROM ban_do_gis WHERE MaVung = ?");
        $stmt->bind_param('s', $maVung);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy MaVung='$maVung'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM ban_do_gis LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM ban_do_gis")
                      ->fetch_assoc()['cnt'];

        echo json_encode([
            'success'=>true,
            'data'=>$data,
            'pagination'=>[
                'current_page'=>$page,
                'per_page'=>$limit,
                'total'=> (int)$total,
                'total_pages'=> ceil($total/$limit)
            ]
        ]);
    }
}

// POST: Thêm mới bản đồ GIS
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    // Validate bắt buộc
    if (empty($in['MaVung']) || !isset($in['ToaDo']) || !isset($in['NhanTen'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaVung, ToaDo hoặc NhanTen']);
        return;
    }
    // Kiểm tra FK vung_trong
    if (!existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    // Tránh duplicate khóa chính
    $chk = $conn->prepare("SELECT 1 FROM ban_do_gis WHERE MaVung = ?");
    $chk->bind_param('s', $in['MaVung']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'MaVung đã tồn tại']);
        return;
    }

    // Gán biến
    $ma        = $in['MaVung'];
    $toaDo     = $in['ToaDo'];
    $nhanTen   = $in['NhanTen'];
    $popupInfo = $in['ThongTinPopup'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO ban_do_gis
        (MaVung,ToaDo,NhanTen,ThongTinPopup)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param('ssss', $ma, $toaDo, $nhanTen, $popupInfo);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi vừa thêm
    $sel = $conn->prepare("SELECT * FROM ban_do_gis WHERE MaVung = ?");
    $sel->bind_param('s', $ma);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode([
        'success'=>true,
        'message'=>'Thêm bản đồ GIS thành công',
        'data'=>$new
    ]);
}

// PUT: Cập nhật theo ?MaVung=...
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $maVung = $q['MaVung'] ?? null;
    if (!$maVung) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaVung để cập nhật']);
        return;
    }
    // Kiểm tra tồn tại bản ghi
    $chk = $conn->prepare("SELECT 1 FROM ban_do_gis WHERE MaVung = ?");
    $chk->bind_param('s', $maVung);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['error'=>"MaVung '$maVung' không tồn tại"]);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // Build dynamic update
    $fields = [
        'ToaDo'         => 's',
        'NhanTen'       => 's',
        'ThongTinPopup' => 's'
    ];
    $sets = []; $types = ''; $vars = [];
    foreach ($fields as $f => $t) {
        if (isset($in[$f])) {
            $sets[] = "$f = ?";
            $types .= $t;
            $vars[] = $in[$f];
        }
    }
    if (empty($sets)) {
        echo json_encode(['error'=>'Không có trường nào để cập nhật']);
        return;
    }
    // Thêm khóa chính cuối
    $types .= 's'; 
    $vars[] = $maVung;

    $sql = "UPDATE ban_do_gis SET " . implode(', ', $sets) . " WHERE MaVung = ?";
    $stmt = $conn->prepare($sql);
    array_unshift($vars, $types);
    $refs = [];
    foreach ($vars as $i => $v) {
        $refs[] = &$vars[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi đã cập nhật
    $sel = $conn->prepare("SELECT * FROM ban_do_gis WHERE MaVung = ?");
    $sel->bind_param('s', $maVung);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode([
        'success'=>true,
        'message'=>'Cập nhật bản đồ GIS thành công',
        'data'=>$upd
    ]);
}

// DELETE: Xóa theo ?MaVung=...
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $maVung = $q['MaVung'] ?? null;
    if (!$maVung) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaVung để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM ban_do_gis WHERE MaVung = ?");
    $stmt->bind_param('s', $maVung);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode([
        'success'=>true,
        'message'=>"Xóa bản đồ GIS MaVung='$maVung' thành công"
    ]);
}
?>
