<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Xử lý preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cấu hình kết nối MySQL
$servername = "localhost";
$database   = "qlvtxoai";
$username   = "root";
$password   = "password";
$port       = 3307;

$conn = new mysqli($servername, $username, $password, $database, $port);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi kết nối database']);
    exit;
}

// Dispatch theo HTTP method
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':    handleGet($conn);    break;
    case 'POST':   handlePost($conn);   break;
    case 'PUT':    handlePut($conn);    break;
    case 'DELETE': handleDelete($conn); break;
    default:
        http_response_code(405);
        echo json_encode(['error'=>'Method not allowed']);
        break;
}

$conn->close();

function handleGet($conn) {
    $ten = $_GET['TenThuoc'] ?? null;
    if ($ten) {
        // Lấy 1 bản ghi theo khóa chính TenThuoc
        $sql  = "SELECT * FROM thuoc_bvtv WHERE TenThuoc = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$ten);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy thuốc '$ten'"]);
        }
    } else {
        // Lấy danh sách có phân trang
        $page  = max(1,intval($_GET['page']  ?? 1));
        $limit = max(1,min(100,intval($_GET['limit'] ?? 10)));
        $offset = ($page-1)*$limit;

        $sql  = "SELECT * FROM thuoc_bvtv LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii",$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM thuoc_bvtv")
                      ->fetch_assoc()['cnt'];

        echo json_encode([
            'success'    => true,
            'data'       => $data,
            'pagination' => [
                'current_page'=> $page,
                'per_page'    => $limit,
                'total'       => (int)$total,
                'total_pages' => ceil($total/$limit)
            ]
        ]);
    }
}

function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'),true);
    if (!isset($data['TenThuoc']) || trim($data['TenThuoc']) === '') {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu trường TenThuoc']);
        return;
    }
    // Tránh duplicate
    $chk = $conn->prepare("SELECT 1 FROM thuoc_bvtv WHERE TenThuoc = ?");
    $chk->bind_param("s",$data['TenThuoc']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'TenThuoc đã tồn tại']);
        return;
    }
    // Chuẩn bị dữ liệu
    $hoatChat = $data['HoatChat']   ?? null;
    $donVi    = $data['DonViTinh']  ?? null;
    $ghiChu   = $data['GhiChu']     ?? null;

    $sql = "INSERT INTO thuoc_bvtv (TenThuoc, HoatChat, DonViTinh, GhiChu)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",
        $data['TenThuoc'],
        $hoatChat,
        $donVi,
        $ghiChu
    );
    if ($stmt->execute()) {
        // Lấy lại bản ghi vừa thêm
        $sel = $conn->prepare("SELECT * FROM thuoc_bvtv WHERE TenThuoc = ?");
        $sel->bind_param("s",$data['TenThuoc']);
        $sel->execute();
        $new = $sel->get_result()->fetch_assoc();

        http_response_code(201);
        echo json_encode([
            'success'=>true,
            'message'=>'Thêm thuốc thành công',
            'data'=>$new
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
    }
}

function handlePut($conn) {
    // Đọc TenThuoc từ query string
    parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$q);
    $ten = $q['TenThuoc'] ?? null;
    if (!$ten) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp TenThuoc để cập nhật']);
        return;
    }
    $data = json_decode(file_get_contents('php://input'),true);
    $hoatChat = $data['HoatChat']   ?? null;
    $donVi    = $data['DonViTinh']  ?? null;
    $ghiChu   = $data['GhiChu']     ?? null;

    $sql = "UPDATE thuoc_bvtv SET HoatChat=?, DonViTinh=?, GhiChu=? WHERE TenThuoc=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",
        $hoatChat,
        $donVi,
        $ghiChu,
        $ten
    );
    if ($stmt->execute()) {
        // Lấy lại bản ghi đã cập nhật
        $sel = $conn->prepare("SELECT * FROM thuoc_bvtv WHERE TenThuoc = ?");
        $sel->bind_param("s",$ten);
        $sel->execute();
        $upd = $sel->get_result()->fetch_assoc();

        echo json_encode([
            'success'=>true,
            'message'=>'Cập nhật thuốc thành công',
            'data'=>$upd
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
    }
}

function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$q);
    $ten = $q['TenThuoc'] ?? null;
    if (!$ten) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp TenThuoc để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM thuoc_bvtv WHERE TenThuoc = ?");
    $stmt->bind_param("s",$ten);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Xóa thuốc thành công']);
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
    }
}
?>
