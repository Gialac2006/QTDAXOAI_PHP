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

// Kết nối database
$host   = 'localhost';
$db     = 'qlvtxoai';
$user   = 'root';
$pass   = 'password';
$port   = 3307;

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

// GET: lấy 1 hoặc list (pagination)
function handleGet($conn) {
    $key   = $_GET['MaMuaVu'] ?? null;
    $page  = max(1,intval($_GET['page']  ?? 1));
    $limit = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page-1)*$limit;

    if ($key) {
        $stmt = $conn->prepare("SELECT * FROM mua_vu WHERE MaMuaVu = ?");
        $stmt->bind_param('s',$key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy MaMuaVu='$key'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM mua_vu LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM mua_vu")
                      ->fetch_assoc()['cnt'];

        echo json_encode([
            'success'=>true,
            'data'=>$data,
            'pagination'=>[
                'current_page'=>$page,
                'per_page'=>$limit,
                'total'=>(int)$total,
                'total_pages'=>ceil($total/$limit)
            ]
        ]);
    }
}

// POST: thêm mới
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    // Validate bắt buộc
    if (empty($in['MaMuaVu']) || empty($in['Nam'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaMuaVu hoặc Nam']);
        return;
    }
    // Tránh duplicate
    $chk = $conn->prepare("SELECT 1 FROM mua_vu WHERE MaMuaVu = ?");
    $chk->bind_param('s',$in['MaMuaVu']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'MaMuaVu đã tồn tại']);
        return;
    }

    // Gán biến
    $code = $in['MaMuaVu'];
    $year = $in['Nam'];
    $dot  = $in['Dot']         ?? null;
    $bd   = $in['NgayBatDau']  ?? null;
    $kt   = $in['NgayKetThuc'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO mua_vu (MaMuaVu,Nam,Dot,NgayBatDau,NgayKetThuc)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $code, $year, $dot, $bd, $kt);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi
    $sel = $conn->prepare("SELECT * FROM mua_vu WHERE MaMuaVu = ?");
    $sel->bind_param('s',$code);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode([
        'success'=>true,
        'message'=>'Thêm mùa vụ thành công',
        'data'=>$new
    ]);
}

// PUT: cập nhật theo ?MaMuaVu=...
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $key = $q['MaMuaVu'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaMuaVu để cập nhật']);
        return;
    }

    $in = json_decode(file_get_contents('php://input'), true);

    // Gán biến
    $year = $in['Nam']         ?? null;
    $dot  = $in['Dot']         ?? null;
    $bd   = $in['NgayBatDau']  ?? null;
    $kt   = $in['NgayKetThuc'] ?? null;

    $stmt = $conn->prepare("
        UPDATE mua_vu SET
          Nam=?, Dot=?, NgayBatDau=?, NgayKetThuc=?
        WHERE MaMuaVu=?
    ");
    $stmt->bind_param('sssss', $year, $dot, $bd, $kt, $key);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Trả về bản ghi đã cập nhật
    $sel = $conn->prepare("SELECT * FROM mua_vu WHERE MaMuaVu = ?");
    $sel->bind_param('s',$key);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode([
        'success'=>true,
        'message'=>'Cập nhật mùa vụ thành công',
        'data'=>$upd
    ]);
}

// DELETE: xóa theo ?MaMuaVu=...
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $key = $q['MaMuaVu'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaMuaVu để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM mua_vu WHERE MaMuaVu = ?");
    $stmt->bind_param('s',$key);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa mùa vụ '$key' thành công"]);
}
?>
