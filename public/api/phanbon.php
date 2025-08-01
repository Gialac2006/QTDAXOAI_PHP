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

// Kết nối
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

// GET: ?TenPhanBon=... hoặc danh sách phân trang
function handleGet($conn) {
    $key   = $_GET['TenPhanBon'] ?? null;
    $page  = max(1,intval($_GET['page']  ?? 1));
    $limit = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page-1)*$limit;

    if ($key) {
        $stmt = $conn->prepare("SELECT * FROM phan_bon WHERE TenPhanBon = ?");
        $stmt->bind_param('s',$key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy phân bón '$key'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM phan_bon LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM phan_bon")
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
    if (empty($in['TenPhanBon'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu trường TenPhanBon']);
        return;
    }
    // Tránh duplicate
    $chk = $conn->prepare("SELECT 1 FROM phan_bon WHERE TenPhanBon = ?");
    $chk->bind_param('s',$in['TenPhanBon']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'Tên phân bón đã tồn tại']);
        return;
    }
    // Gán biến để bind_param
    $ten      = $in['TenPhanBon'];
    $loai     = $in['Loai']      ?? null;
    $donVi    = $in['DonViTinh'] ?? null;
    $ghiChu   = $in['GhiChu']    ?? null;

    $stmt = $conn->prepare("INSERT INTO phan_bon (TenPhanBon,Loai,DonViTinh,GhiChu) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss',$ten,$loai,$donVi,$ghiChu);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }
    // Lấy lại bản ghi vừa thêm
    $sel = $conn->prepare("SELECT * FROM phan_bon WHERE TenPhanBon = ?");
    $sel->bind_param('s',$ten);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();
    http_response_code(201);
    echo json_encode(['success'=>true,'message'=>'Thêm phân bón thành công','data'=>$new]);
}

// PUT: cập nhật
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$q);
    $key = $q['TenPhanBon'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp TenPhanBon để cập nhật']);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);
    // Gán biến
    $loai   = $in['Loai']      ?? null;
    $donVi  = $in['DonViTinh'] ?? null;
    $ghiChu = $in['GhiChu']    ?? null;

    $stmt = $conn->prepare("UPDATE phan_bon SET Loai=?,DonViTinh=?,GhiChu=? WHERE TenPhanBon=?");
    $stmt->bind_param('ssss',$loai,$donVi,$ghiChu,$key);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }
    // Trả về bản ghi đã cập nhật
    $sel = $conn->prepare("SELECT * FROM phan_bon WHERE TenPhanBon = ?");
    $sel->bind_param('s',$key);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();
    echo json_encode(['success'=>true,'message'=>'Cập nhật phân bón thành công','data'=>$upd]);
}

// DELETE: xóa
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$q);
    $key = $q['TenPhanBon'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp TenPhanBon để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM phan_bon WHERE TenPhanBon = ?");
    $stmt->bind_param('s',$key);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa phân bón '$key' thành công"]);
}
?>
