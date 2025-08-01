<?php
// File: baocaosanluong.php
// Cấu trúc bảng bao_cao_san_luong :contentReference[oaicite:1]{index=1}

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

// Kiểm tra FK tồn tại
function existsVung($conn, $maVung) {
    $stmt = $conn->prepare("SELECT 1 FROM vung_trong WHERE MaVung = ?");
    $stmt->bind_param('s',$maVung);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}
function existsMuaVu($conn, $maMuaVu) {
    $stmt = $conn->prepare("SELECT 1 FROM mua_vu WHERE MaMuaVu = ?");
    $stmt->bind_param('s',$maMuaVu);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
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

// GET: lấy 1 hoặc danh sách phân trang
function handleGet($conn) {
    $id     = $_GET['ID'] ?? null;
    $page   = max(1, intval($_GET['page']  ?? 1));
    $limit  = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM bao_cao_san_luong WHERE ID = ?");
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy ID='$id'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM bao_cao_san_luong LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM bao_cao_san_luong")
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

// POST: thêm mới báo cáo sản lượng
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    // Validate bắt buộc
    if (empty($in['MaVung']) || empty($in['MaMuaVu']) || !isset($in['SanLuong'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaVung, MaMuaVu hoặc SanLuong']);
        return;
    }
    // Kiểm tra FK
    if (!existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    if (!existsMuaVu($conn, $in['MaMuaVu'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaMuaVu '{$in['MaMuaVu']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $maVung    = $in['MaVung'];
    $maMuaVu   = $in['MaMuaVu'];
    $sanLuong  = floatval($in['SanLuong']);
    $chatLuong = $in['ChatLuong'] ?? null;
    $ghiChu    = $in['GhiChu']    ?? null;

    $stmt = $conn->prepare("
        INSERT INTO bao_cao_san_luong
        (MaVung,MaMuaVu,SanLuong,ChatLuong,GhiChu)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param('ssdds',$maVung,$maMuaVu,$sanLuong,$chatLuong,$ghiChu);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi vừa thêm
    $newId = $conn->insert_id;
    $sel = $conn->prepare("SELECT * FROM bao_cao_san_luong WHERE ID = ?");
    $sel->bind_param('i',$newId);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode([
        'success'=>true,
        'message'=>'Thêm báo cáo sản lượng thành công',
        'data'=>$new
    ]);
}

// PUT: cập nhật theo ?ID=...
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để cập nhật']);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // Kiểm tra tồn tại bản ghi
    $chk = $conn->prepare("SELECT 1 FROM bao_cao_san_luong WHERE ID = ?");
    $chk->bind_param('i',$id);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['error'=>"ID '$id' không tồn tại"]);
        return;
    }
    // Kiểm tra FK nếu có
    if (isset($in['MaVung']) && !existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    if (isset($in['MaMuaVu']) && !existsMuaVu($conn, $in['MaMuaVu'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaMuaVu '{$in['MaMuaVu']}' không tồn tại"]);
        return;
    }

    // Build dynamic update
    $fields = [
        'MaVung'=>'s','MaMuaVu'=>'s','SanLuong'=>'d',
        'ChatLuong'=>'s','GhiChu'=>'s'
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
    $types .= 'i'; $vars[] = $id;

    $sql = "UPDATE bao_cao_san_luong SET ".implode(', ',$sets)." WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    array_unshift($vars, $types);
    $refs = [];
    foreach ($vars as $k => $v) {
        $refs[] = &$vars[$k];
    }
    call_user_func_array([$stmt,'bind_param'],$refs);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi đã cập nhật
    $sel = $conn->prepare("SELECT * FROM bao_cao_san_luong WHERE ID = ?");
    $sel->bind_param('i',$id);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode([
        'success'=>true,
        'message'=>'Cập nhật báo cáo sản lượng thành công',
        'data'=>$upd
    ]);
}

// DELETE: xóa theo ?ID=...
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM bao_cao_san_luong WHERE ID = ?");
    $stmt->bind_param('i',$id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa báo cáo ID='$id' thành công"]);
}
?>
