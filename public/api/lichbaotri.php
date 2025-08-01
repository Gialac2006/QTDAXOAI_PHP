<?php
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

// Kiểm tra thiet_bi_may_moc tồn tại
function existsThietBi($conn, $ma) {
    $stmt = $conn->prepare("SELECT 1 FROM thiet_bi_may_moc WHERE MaThietBi = ?");
    $stmt->bind_param('s',$ma);
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

// ============= GET =============
// ?ID=... lấy 1, ngược lại phân trang
function handleGet($conn) {
    $id     = $_GET['ID'] ?? null;
    $page   = max(1,intval($_GET['page']  ?? 1));
    $limit  = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page-1)*$limit;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM lich_bao_tri WHERE ID = ?");
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
        $stmt = $conn->prepare("SELECT * FROM lich_bao_tri LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM lich_bao_tri")
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

// ============= POST =============
// :contentReference[oaicite:1]{index=1}
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    // Validate bắt buộc
    if (empty($in['MaThietBi']) || empty($in['NgayBaoTri'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaThietBi hoặc NgayBaoTri']);
        return;
    }
    // Kiểm tra FK
    if (!existsThietBi($conn, $in['MaThietBi'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaThietBi '{$in['MaThietBi']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $ma   = $in['MaThietBi'];
    $ngay = $in['NgayBaoTri'];
    $noi  = $in['NoiDung']  ?? null;
    $tt   = $in['TrangThai'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO lich_bao_tri
          (MaThietBi,NgayBaoTri,NoiDung,TrangThai)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param('ssss',$ma,$ngay,$noi,$tt);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    // Lấy lại
    $newId = $conn->insert_id;
    $sel = $conn->prepare("SELECT * FROM lich_bao_tri WHERE ID = ?");
    $sel->bind_param('i',$newId);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode([
        'success'=>true,
        'message'=>'Thêm lịch bảo trì thành công',
        'data'=>$new
    ]);
}

// ============= PUT =============
// Cập nhật ?ID=...
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để cập nhật']);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // Nếu đổi MaThietBi thì kiểm tra tồn tại
    if (isset($in['MaThietBi']) && !existsThietBi($conn, $in['MaThietBi'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaThietBi '{$in['MaThietBi']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $ma   = $in['MaThietBi']    ?? null;
    $ngay = $in['NgayBaoTri']   ?? null;
    $noi  = $in['NoiDung']      ?? null;
    $tt   = $in['TrangThai']    ?? null;

    $stmt = $conn->prepare("
        UPDATE lich_bao_tri SET
          MaThietBi=?, NgayBaoTri=?, NoiDung=?, TrangThai=?
        WHERE ID=?
    ");
    $stmt->bind_param('ssssi',$ma,$ngay,$noi,$tt,$id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Lấy lại
    $sel = $conn->prepare("SELECT * FROM lich_bao_tri WHERE ID = ?");
    $sel->bind_param('i',$id);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode([
        'success'=>true,
        'message'=>'Cập nhật lịch bảo trì thành công',
        'data'=>$upd
    ]);
}

// ============= DELETE =============
// Xóa ?ID=...
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM lich_bao_tri WHERE ID = ?");
    $stmt->bind_param('i',$id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa lịch bảo trì ID='$id' thành công"]);
}
?>
