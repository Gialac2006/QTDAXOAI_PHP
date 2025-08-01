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

// DB connection
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

// GET
function handleGet($conn) {
    $maHo   = $_GET['MaHo'] ?? null;
    $page   = max(1, intval($_GET['page']  ?? 1));
    $limit  = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($maHo) {
        $stmt = $conn->prepare("SELECT * FROM ho_nong_dan WHERE MaHo = ?");
        $stmt->bind_param('s', $maHo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy MaHo='$maHo'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM ho_nong_dan LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM ho_nong_dan")
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

// POST
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);

    $required = ['MaHo','TenChuHo','CCCD','NgaySinh','SoDienThoai','DiaChi','SoThanhVien','LoaiDat','DienTich','NgayDangKy'];
    foreach ($required as $f) {
        if (!isset($in[$f]) || trim($in[$f]) === '') {
            http_response_code(400);
            echo json_encode(['error'=>"Thiếu trường $f"]);
            return;
        }
    }

    // duplicate check
    $chk = $conn->prepare("SELECT 1 FROM ho_nong_dan WHERE MaHo=? OR CCCD=?");
    $chk->bind_param('ss', $in['MaHo'], $in['CCCD']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'MaHo hoặc CCCD đã tồn tại']);
        return;
    }

    // bind variables
    $ma        = $in['MaHo'];
    $chuHo     = $in['TenChuHo'];
    $cccd      = $in['CCCD'];
    $ns        = $in['NgaySinh'];
    $phone     = $in['SoDienThoai'];
    $diaChi    = $in['DiaChi'];
    $thanhVien = intval($in['SoThanhVien']);
    $loaiDat   = $in['LoaiDat'];
    $dienTich  = floatval($in['DienTich']);
    $ngayDK    = $in['NgayDangKy'];

    $stmt = $conn->prepare("
        INSERT INTO ho_nong_dan
          (MaHo,TenChuHo,CCCD,NgaySinh,SoDienThoai,DiaChi,SoThanhVien,LoaiDat,DienTich,NgayDangKy)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        'sssssissds',
        $ma, $chuHo, $cccd, $ns, $phone, $diaChi, $thanhVien, $loaiDat, $dienTich, $ngayDK
    );
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    $sel = $conn->prepare("SELECT * FROM ho_nong_dan WHERE MaHo = ?");
    $sel->bind_param('s', $ma);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode([
        'success'=>true,
        'message'=>'Thêm hộ nông dân thành công',
        'data'=>$new
    ]);
}

// PUT
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $maHo = $q['MaHo'] ?? null;
    if (!$maHo) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaHo để cập nhật']);
        return;
    }

    $in = json_decode(file_get_contents('php://input'), true);

    // existence check
    $chk = $conn->prepare("SELECT 1 FROM ho_nong_dan WHERE MaHo = ?");
    $chk->bind_param('s', $maHo);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['error'=>"MaHo '$maHo' không tồn tại"]);
        return;
    }

    $fields = [
        'TenChuHo','CCCD','NgaySinh','SoDienThoai',
        'DiaChi','SoThanhVien','LoaiDat','DienTich','NgayDangKy'
    ];
    $sets = []; $types = ''; $vars = [];
    foreach ($fields as $f) {
        if (isset($in[$f])) {
            $sets[] = "$f = ?";
            $types .= in_array($f, ['SoThanhVien'])
                ? 'i'
                : (in_array($f, ['DienTich']) ? 'd' : 's');
            $vars[] = $in[$f];
        }
    }
    if (empty($sets)) {
        echo json_encode(['error'=>'Không có trường nào để cập nhật']);
        return;
    }
    $types .= 's';
    $vars[] = $maHo;

    $sql = "UPDATE ho_nong_dan SET " . implode(', ', $sets) . " WHERE MaHo = ?";
    $stmt = $conn->prepare($sql);

    // bind_param dynamic
    $bind_names = [];
    array_unshift($vars, $types);
    foreach ($vars as $key => $val) {
        $bind_names[] = &$vars[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    $sel = $conn->prepare("SELECT * FROM ho_nong_dan WHERE MaHo = ?");
    $sel->bind_param('s', $maHo);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode([
        'success'=>true,
        'message'=>'Cập nhật hộ nông dân thành công',
        'data'=>$upd
    ]);
}

// DELETE
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $maHo = $q['MaHo'] ?? null;
    if (!$maHo) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaHo để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM ho_nong_dan WHERE MaHo = ?");
    $stmt->bind_param('s', $maHo);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa hộ nông dân MaHo='$maHo' thành công"]);
}
?>
