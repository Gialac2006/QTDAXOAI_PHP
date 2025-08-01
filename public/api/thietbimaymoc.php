<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// DB connection
$host     = 'localhost';
$db       = 'qlvtxoai';
$user     = 'root';
$pass     = 'password';
$port     = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB connection failed']);
    exit;
}

// Route
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

function handleGet($conn) {
    $id    = $_GET['MaThietBi'] ?? null;
    $page  = max(1, intval($_GET['page']  ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page-1)*$limit;

    if ($id) {
        $sql  = "SELECT * FROM thiet_bi_may_moc WHERE MaThietBi=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s',$id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success'=>true,'data'=>$row]);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>"Không tìm thấy thiết bị '$id'"]);
        }
    } else {
        $sql  = "SELECT * FROM thiet_bi_may_moc LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM thiet_bi_may_moc")
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

function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['MaThietBi']) || empty($data['TenThietBi'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Thiếu MaThietBi hoặc TenThietBi']);
        return;
    }
    $chk = $conn->prepare("SELECT 1 FROM thiet_bi_may_moc WHERE MaThietBi=?");
    $chk->bind_param('s',$data['MaThietBi']);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        http_response_code(409);
        echo json_encode(['error'=>'MaThietBi đã tồn tại']);
        return;
    }

    $loai   = $data['LoaiThietBi'] ?? null;
    $nam    = $data['NamSuDung']   ?? null;
    $tinh   = $data['TinhTrang']   ?? null;
    $lien   = $data['LienKetVungHo'] ?? null;

    $sql = "INSERT INTO thiet_bi_may_moc 
            (MaThietBi,TenThietBi,LoaiThietBi,NamSuDung,TinhTrang,LienKetVungHo)
            VALUES(?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss',
        $data['MaThietBi'],
        $data['TenThietBi'],
        $loai, $nam, $tinh, $lien
    );
    if ($stmt->execute()) {
        $sel = $conn->prepare("SELECT * FROM thiet_bi_may_moc WHERE MaThietBi=?");
        $sel->bind_param('s',$data['MaThietBi']);
        $sel->execute();
        $new = $sel->get_result()->fetch_assoc();
        http_response_code(201);
        echo json_encode(['success'=>true,'message'=>'Thêm thành công','data'=>$new]);
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm','details'=>$stmt->error]);
    }
}

function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['MaThietBi'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaThietBi']);
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $ten    = $data['TenThietBi']   ?? null;
    $loai   = $data['LoaiThietBi']  ?? null;
    $nam    = $data['NamSuDung']    ?? null;
    $tinh   = $data['TinhTrang']    ?? null;
    $lien   = $data['LienKetVungHo'] ?? null;

    $sql = "UPDATE thiet_bi_may_moc SET 
                TenThietBi=?,LoaiThietBi=?,NamSuDung=?,TinhTrang=?,LienKetVungHo=?
            WHERE MaThietBi=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss',
        $ten, $loai, $nam, $tinh, $lien, $id
    );
    if ($stmt->execute()) {
        $sel = $conn->prepare("SELECT * FROM thiet_bi_may_moc WHERE MaThietBi=?");
        $sel->bind_param('s',$id);
        $sel->execute();
        $upd = $sel->get_result()->fetch_assoc();
        echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd]);
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
    }
}

function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['MaThietBi'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp MaThietBi']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM thiet_bi_may_moc WHERE MaThietBi=?");
    $stmt->bind_param('s',$id);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>"Xóa $id thành công"]);
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
    }
}
?>
