<?php
// File: nhatkyphunthuoc.php
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
$host     = 'localhost';
$db       = 'qlvtxoai';
$user     = 'root';
$pass     = 'password';
$port     = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Kết nối database thất bại']);
    exit;
}

// Kiểm tra MaVung có tồn tại?
function existsVung($conn, $maVung) {
    $stmt = $conn->prepare("SELECT 1 FROM vung_trong WHERE MaVung = ?");
    $stmt->bind_param('s', $maVung);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}

// Kiểm tra TenThuoc có tồn tại?
function existsThuoc($conn, $tenThuoc) {
    $stmt = $conn->prepare("SELECT 1 FROM thuoc_bvtv WHERE TenThuoc = ?");
    $stmt->bind_param('s', $tenThuoc);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}

// Router theo phương thức
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
// Nếu có ?ID=... thì lấy 1, không thì list phân trang
function handleGet($conn) {
    $id     = $_GET['ID'] ?? null;
    $page   = max(1,intval($_GET['page']  ?? 1));
    $limit  = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM nhat_ky_phun_thuoc WHERE ID = ?");
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
        $stmt = $conn->prepare("SELECT * FROM nhat_ky_phun_thuoc LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM nhat_ky_phun_thuoc")
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
// Thêm nhật ký phun thuốc, kiểm tra MaVung và TenThuoc
// ============= POST =============
// Thêm nhật ký phun thuốc, kiểm tra MaVung và TenThuoc
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    if (empty($in['NgayPhun']) || empty($in['TenNguoiPhun'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu NgayPhun hoặc TenNguoiPhun']);
        return;
    }

    // Kiểm tra MaVung
    if (!empty($in['MaVung']) && !existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    // Kiểm tra TenThuoc
    if (!empty($in['TenThuoc']) && !existsThuoc($conn, $in['TenThuoc'])) {
        http_response_code(400);
        echo json_encode(['error'=>"TenThuoc '{$in['TenThuoc']}' không tồn tại"]);
        return;
    }

    // Gán vào biến tạm để bind_param
    $ngayPhun   = $in['NgayPhun'];
    $tenNguoi   = $in['TenNguoiPhun'];
    $maVung     = $in['MaVung']   ?? null;
    $tenThuoc   = $in['TenThuoc'] ?? null;
    $lieuLuong  = isset($in['LieuLuong']) ? floatval($in['LieuLuong']) : null;
    $ghiChu     = $in['GhiChu']   ?? null;

    $stmt = $conn->prepare("
        INSERT INTO nhat_ky_phun_thuoc
          (NgayPhun, TenNguoiPhun, MaVung, TenThuoc, LieuLuong, GhiChu)
        VALUES (?,?,?,?,?,?)
    ");
    // bind_param phải là biến, không phải biểu thức
    $stmt->bind_param(
        'ssssds',
        $ngayPhun,
        $tenNguoi,
        $maVung,
        $tenThuoc,
        $lieuLuong,
        $ghiChu
    );

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi vừa thêm
    $newId = $conn->insert_id;
    $sel   = $conn->prepare("SELECT * FROM nhat_ky_phun_thuoc WHERE ID = ?");
    $sel->bind_param('i', $newId);
    $sel->execute();
    $new   = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode([
      'success'=>true,
      'message'=>'Thêm nhật ký phun thuốc thành công',
      'data'=>$new
    ]);
}

// ============= PUT =============
// Cập nhật theo ?ID=...
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để cập nhật']);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // Kiểm tra MaVung nếu có
    if (isset($in['MaVung']) && !existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    // Kiểm tra TenThuoc nếu có
    if (isset($in['TenThuoc']) && !existsThuoc($conn, $in['TenThuoc'])) {
        http_response_code(400);
        echo json_encode(['error'=>"TenThuoc '{$in['TenThuoc']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $ngayPhun     = $in['NgayPhun']     ?? null;
    $tenNguoi     = $in['TenNguoiPhun'] ?? null;
    $maVung       = $in['MaVung']       ?? null;
    $tenThuoc     = $in['TenThuoc']     ?? null;
    $lieuLuong    = $in['LieuLuong']    ?? null;
    $ghiChu       = $in['GhiChu']       ?? null;

    $stmt = $conn->prepare("
        UPDATE nhat_ky_phun_thuoc SET
          NgayPhun=?,TenNguoiPhun=?,MaVung=?,TenThuoc=?,LieuLuong=?,GhiChu=?
        WHERE ID=?
    ");
    $stmt->bind_param(
        'ssssdsi',
        $ngayPhun,
        $tenNguoi,
        $maVung,
        $tenThuoc,
        $lieuLuong,
        $ghiChu,
        $id
    );
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi đã cập nhật
    $sel = $conn->prepare("SELECT * FROM nhat_ky_phun_thuoc WHERE ID = ?");
    $sel->bind_param('i', $id);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd]);
}

// ============= DELETE =============
// Xóa theo ?ID=...
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM nhat_ky_phun_thuoc WHERE ID = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa nhật ký ID='$id' thành công"]);
}
?>
