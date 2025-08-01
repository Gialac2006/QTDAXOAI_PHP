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

// Cấu hình DB
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

// Kiểm tra VaiTro tồn tại
function existsRole($conn, $role) {
    $stmt = $conn->prepare("SELECT 1 FROM vai_tro WHERE TenVaiTro = ?");
    $stmt->bind_param('s', $role);
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

// GET: ?TenDangNhap=... hoặc list phân trang
function handleGet($conn) {
    $key   = $_GET['TenDangNhap'] ?? null;
    $page  = max(1,intval($_GET['page']  ?? 1));
    $limit = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page-1)*$limit;

    if ($key) {
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE TenDangNhap = ?");
        $stmt->bind_param('s',$key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy user '$key'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM nguoi_dung")
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
    if (empty($in['TenDangNhap']) || empty($in['MatKhau']) || empty($in['HoTen'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu TenDangNhap, MatKhau hoặc HoTen']);
        return;
    }
    // Trùng TenDangNhap
    $chk = $conn->prepare("SELECT 1 FROM nguoi_dung WHERE TenDangNhap = ?");
    $chk->bind_param('s',$in['TenDangNhap']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'TenDangNhap đã tồn tại']);
        return;
    }
    // Trùng Email nếu có
    if (!empty($in['Email'])) {
        $chk2 = $conn->prepare("SELECT 1 FROM nguoi_dung WHERE Email = ?");
        $chk2->bind_param('s',$in['Email']);
        $chk2->execute();
        if ($chk2->get_result()->fetch_row()) {
            http_response_code(409);
            echo json_encode(['error'=>'Email đã được dùng']);
            return;
        }
    }
    // Kiểm tra VaiTro nếu có
    if (!empty($in['VaiTro']) && !existsRole($conn, $in['VaiTro'])) {
        http_response_code(400);
        echo json_encode(['error'=>"VaiTro '{$in['VaiTro']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $u1 = $in['TenDangNhap'];
    $u2 = password_hash($in['MatKhau'], PASSWORD_DEFAULT);
    $u3 = $in['HoTen'];
    $u4 = $in['Email']    ?? null;
    $u5 = $in['VaiTro']    ?? null;

    $stmt = $conn->prepare("INSERT INTO nguoi_dung 
      (TenDangNhap,MatKhau,HoTen,Email,VaiTro)
      VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss',$u1,$u2,$u3,$u4,$u5);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }
    // Lấy bản ghi mới
    $sel = $conn->prepare("SELECT TenDangNhap,HoTen,Email,VaiTro FROM nguoi_dung WHERE TenDangNhap = ?");
    $sel->bind_param('s',$u1);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode(['success'=>true,'message'=>'Thêm user thành công','data'=>$new]);
}

// PUT: cập nhật
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$q);
    $key = $q['TenDangNhap'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp TenDangNhap để cập nhật']);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // Kiểm tra user tồn tại
    $chk = $conn->prepare("SELECT 1 FROM nguoi_dung WHERE TenDangNhap = ?");
    $chk->bind_param('s',$key);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['error'=>"User '$key' không tồn tại"]);
        return;
    }
    // Kiểm tra Email nếu đổi
    if (!empty($in['Email'])) {
        $e = $in['Email'];
        $chk2 = $conn->prepare("SELECT 1 FROM nguoi_dung WHERE Email = ? AND TenDangNhap <> ?");
        $chk2->bind_param('ss',$e,$key);
        $chk2->execute();
        if ($chk2->get_result()->fetch_row()) {
            http_response_code(409);
            echo json_encode(['error'=>'Email đã được dùng']);
            return;
        }
    }
    // Kiểm tra VaiTro nếu đổi
    if (isset($in['VaiTro']) && !existsRole($conn, $in['VaiTro'])) {
        http_response_code(400);
        echo json_encode(['error'=>"VaiTro '{$in['VaiTro']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $hoTen = $in['HoTen']    ?? null;
    $email = $in['Email']    ?? null;
    $role  = $in['VaiTro']   ?? null;
    $pass  = isset($in['MatKhau']) ? password_hash($in['MatKhau'], PASSWORD_DEFAULT) : null;

    $stmt = $conn->prepare("
      UPDATE nguoi_dung SET
        " . ($pass    !== null ? "MatKhau=?, " : "") . "
        HoTen=?, Email=?, VaiTro=?
      WHERE TenDangNhap=?
    ");
    // bind_param động
    $types = '';
    $vars  = [];
    if ($pass    !== null) { $types.='s'; $vars[]=&$pass; }
    $types .= 'ss';
    $vars[]=&$hoTen;
    $vars[]=&$email;
    $types .= 's';
    $vars[]=&$role;
    $types .= 's';
    $vars[]=&$key;

    array_unshift($vars, $types);
    call_user_func_array([$stmt,'bind_param'], $vars);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Trả về bản ghi
    $sel = $conn->prepare("SELECT TenDangNhap,HoTen,Email,VaiTro FROM nguoi_dung WHERE TenDangNhap = ?");
    $sel->bind_param('s',$key);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();
    echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd]);
}

// DELETE: xóa
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$q);
    $key = $q['TenDangNhap'] ?? null;
    if (!$key) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp TenDangNhap để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM nguoi_dung WHERE TenDangNhap = ?");
    $stmt->bind_param('s',$key);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa user '$key' thành công"]);
}
?>
