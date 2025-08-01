<?php
// File: thietbiiot.php
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
$servername = "localhost";
$database   = "qlvtxoai";
$username   = "root";
$password   = "password";
$port       = 3307;

// Kết nối
$conn = new mysqli($servername, $username, $password, $database, $port);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Lỗi kết nối database']);
    exit;
}

// Hàm kiểm tra MaVung có tồn tại trong vung_trong không
function existsVung($conn, $maVung) {
    $stmt = $conn->prepare("SELECT 1 FROM vung_trong WHERE MaVung = ?");
    $stmt->bind_param('s', $maVung);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

// Router theo phương thức HTTP
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

// GET: lấy danh sách hoặc một bản ghi
function handleGet($conn) {
    $id    = $_GET['MaThietBi'] ?? null;
    $page  = max(1,intval($_GET['page']  ?? 1));
    $limit = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM thiet_bi_iot WHERE MaThietBi = ?");
        $stmt->bind_param('s',$id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy MaThietBi='$id'"]);
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM thiet_bi_iot LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM thiet_bi_iot")
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

// POST: thêm mới, kèm kiểm tra MaVung
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    if (empty($in['MaThietBi']) || empty($in['TenThietBi'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaThietBi hoặc TenThietBi']);
        return;
    }

    // Kiểm tra MaVung nếu được cung cấp
    if (!empty($in['MaVung']) && !existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }

    // Tránh duplicate
    $chk = $conn->prepare("SELECT 1 FROM thiet_bi_iot WHERE MaThietBi = ?");
    $chk->bind_param('s',$in['MaThietBi']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'MaThietBi đã tồn tại']);
        return;
    }

    // Chuẩn bị giá trị
    $ngay   = $in['NgayLapDat'] ?? null;
    $tinh   = $in['TinhTrang']  ?? null;
    $vung   = $in['MaVung']      ?? null;
    $ketnoi = $in['MaKetNoi']    ?? null;

    $stmt = $conn->prepare("
        INSERT INTO thiet_bi_iot 
        (MaThietBi,TenThietBi,NgayLapDat,TinhTrang,MaVung,MaKetNoi)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param('ssssss',
        $in['MaThietBi'],
        $in['TenThietBi'],
        $ngay,
        $tinh,
        $vung,
        $ketnoi
    );

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi vừa thêm
    $sel = $conn->prepare("SELECT * FROM thiet_bi_iot WHERE MaThietBi = ?");
    $sel->bind_param('s',$in['MaThietBi']);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode(['success'=>true,'message'=>'Thêm thiết bị IoT thành công','data'=>$new]);
}

// PUT: cập nhật, kèm kiểm tra MaVung
// PUT: cập nhật, kèm kiểm tra MaVung
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['MaThietBi'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaThietBi để cập nhật']);
        return;
    }

    $in = json_decode(file_get_contents('php://input'), true);

    // Kiểm tra MaVung nếu được cung cấp
    if (isset($in['MaVung']) && !existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }

    // Gán vào biến tạm để bind_param
    $tenThietBi  = $in['TenThietBi']   ?? null;
    $ngayLapDat  = $in['NgayLapDat']   ?? null;
    $tinhTrang   = $in['TinhTrang']    ?? null;
    $maVung      = $in['MaVung']       ?? null;
    $maKetNoi    = $in['MaKetNoi']     ?? null;

    $stmt = $conn->prepare("
        UPDATE thiet_bi_iot SET
            TenThietBi=?, NgayLapDat=?, TinhTrang=?, MaVung=?, MaKetNoi=?
        WHERE MaThietBi=?
    ");
    // bind_param cần biến, không phải biểu thức
    $stmt->bind_param(
        'ssssss',
        $tenThietBi,
        $ngayLapDat,
        $tinhTrang,
        $maVung,
        $maKetNoi,
        $id
    );

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }

    // Lấy lại bản ghi đã cập nhật
    $sel = $conn->prepare("SELECT * FROM thiet_bi_iot WHERE MaThietBi = ?");
    $sel->bind_param('s', $id);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd]);
}


// DELETE: xóa
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['MaThietBi'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaThietBi để xóa']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM thiet_bi_iot WHERE MaThietBi = ?");
    $stmt->bind_param('s',$id);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }

    echo json_encode(['success'=>true,'message'=>"Xóa thiết bị IoT '$id' thành công"]);
}
?>
