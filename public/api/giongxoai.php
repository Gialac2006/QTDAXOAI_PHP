<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cấu hình kết nối
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

// GET: ?MaGiong=... hoặc phân trang
function handleGet($conn) {
    $key   = $_GET['MaGiong'] ?? null;
    $page  = max(1, intval($_GET['page']  ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($key) {
        $stmt = $conn->prepare("SELECT * FROM giong_xoai WHERE MaGiong = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy MaGiong='$key'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM giong_xoai LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM giong_xoai")
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

// POST: thêm mới giống xoài
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    if (empty($in['MaGiong']) || empty($in['TenGiong'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaGiong hoặc TenGiong']);
        return;
    }
    // tránh duplicate
    $chk = $conn->prepare("SELECT 1 FROM giong_xoai WHERE MaGiong = ?");
    $chk->bind_param('s', $in['MaGiong']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'MaGiong đã tồn tại']);
        return;
    }
    // bind biến
    $ma    = $in['MaGiong'];
    $ten   = $in['TenGiong'];
    $tg    = isset($in['ThoiGianTruongThanh']) ? intval($in['ThoiGianTruongThanh']) : null;
    $nsx   = isset($in['NangSuatTrungBinh']) ? floatval($in['NangSuatTrungBinh']) : null;
    $dd    = $in['DacDiem'] ?? null;
    $tt    = $in['TinhTrang'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO giong_xoai
        (MaGiong,TenGiong,ThoiGianTruongThanh,NangSuatTrungBinh,DacDiem,TinhTrang)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param('ssidss', $ma, $ten, $tg, $nsx, $dd, $tt);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }
    // lấy lại
    $sel = $conn->prepare("SELECT * FROM giong_xoai WHERE MaGiong = ?");
    $sel->bind_param('s', $ma);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode(['success'=>true,'message'=>'Thêm giống xoài thành công','data'=>$new]);
}

// PUT: cập nhật ?MaGiong=...
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $key = $q['MaGiong'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaGiong để cập nhật']);
        return;
    }
    // kiểm tra tồn tại
    $chk = $conn->prepare("SELECT 1 FROM giong_xoai WHERE MaGiong = ?");
    $chk->bind_param('s',$key);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['error'=>"MaGiong '$key' không tồn tại"]);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // build dynamic update
    $fields = [
        'TenGiong'=>'s','ThoiGianTruongThanh'=>'i',
        'NangSuatTrungBinh'=>'d','DacDiem'=>'s','TinhTrang'=>'s'
    ];
    $sets = []; $types = ''; $vars = [];
    foreach ($fields as $f => $type) {
        if (isset($in[$f])) {
            $sets[] = "$f = ?";
            $types .= $type;
            $vars[] = $in[$f];
        }
    }
    if (empty($sets)) {
        echo json_encode(['error'=>'Không có trường nào để cập nhật']);
        return;
    }
    $types .= 's';  // cho MaGiong
    $vars[] = $key;

    $sql = "UPDATE giong_xoai SET ".implode(', ',$sets)." WHERE MaGiong = ?";
    $stmt = $conn->prepare($sql);
    array_unshift($vars, $types);
    $bindNames = [];
    foreach ($vars as $i => $v) {
        $bindNames[] = &$vars[$i];
    }
    call_user_func_array([$stmt,'bind_param'], $bindNames);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }
    // lấy lại
    $sel = $conn->prepare("SELECT * FROM giong_xoai WHERE MaGiong = ?");
    $sel->bind_param('s',$key);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd]);
}

// DELETE: ?MaGiong=...
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $key = $q['MaGiong'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaGiong để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM giong_xoai WHERE MaGiong = ?");
    $stmt->bind_param('s',$key);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa giống xoài '$key' thành công"]);
}
?>
