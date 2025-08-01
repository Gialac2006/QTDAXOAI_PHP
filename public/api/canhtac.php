<?php
// File: canhtac.php
// Cấu trúc bảng canh_tac :contentReference[oaicite:1]{index=1}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Xử lý preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cấu hình kết nối
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

// Kiểm tra tồn tại bản ghi khoá ngoại
function existsVung($conn, $maVung) {
    $stmt = $conn->prepare("SELECT 1 FROM vung_trong WHERE MaVung = ?");
    $stmt->bind_param('s', $maVung);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}
function existsPhanBon($conn, $ten) {
    $stmt = $conn->prepare("SELECT 1 FROM phan_bon WHERE TenPhanBon = ?");
    $stmt->bind_param('s', $ten);
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

// GET: ?ID=... hoặc list phân trang
function handleGet($conn) {
    $id     = $_GET['ID'] ?? null;
    $page   = max(1,intval($_GET['page']  ?? 1));
    $limit  = max(1,min(100,intval($_GET['limit'] ?? 10)));
    $offset = ($page-1)*$limit;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM canh_tac WHERE ID = ?");
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
        $stmt = $conn->prepare("SELECT * FROM canh_tac LIMIT ? OFFSET ?");
        $stmt->bind_param('ii',$limit,$offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $total = $conn->query("SELECT COUNT(*) AS cnt FROM canh_tac")
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

// POST – Thêm mới canh tác
function handlePost($conn) {
    $in = json_decode(file_get_contents('php://input'), true);
    // Validate bắt buộc
    $required = ['NgayThucHien','LoaiCongViec','NguoiThucHien','MaVung','TenPhanBon','LieuLuong'];
    foreach ($required as $f) {
        if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
            http_response_code(400);
            echo json_encode(['error'=>"Thiếu trường $f"]);
            return;
        }
    }
    // Kiểm tra FK
    if (!existsVung($conn, $in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    if (!existsPhanBon($conn, $in['TenPhanBon'])) {
        http_response_code(400);
        echo json_encode(['error'=>"TenPhanBon '{$in['TenPhanBon']}' không tồn tại"]);
        return;
    }

    // Gán biến
    $ngay = $in['NgayThucHien'];
    $loai = $in['LoaiCongViec'];
    $nguoi = $in['NguoiThucHien'];
    $vung = $in['MaVung'];
    $pb = $in['TenPhanBon'];
    $lieu = floatval($in['LieuLuong']);
    $ghi = $in['GhiChu'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO canh_tac
        (NgayThucHien,LoaiCongViec,NguoiThucHien,MaVung,TenPhanBon,LieuLuong,GhiChu)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param('sssssds',$ngay,$loai,$nguoi,$vung,$pb,$lieu,$ghi);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi thêm dữ liệu','details'=>$stmt->error]);
        return;
    }
    // Lấy lại bản ghi
    $newId = $conn->insert_id;
    $sel = $conn->prepare("SELECT * FROM canh_tac WHERE ID = ?");
    $sel->bind_param('i',$newId);
    $sel->execute();
    $new = $sel->get_result()->fetch_assoc();

    http_response_code(201);
    echo json_encode(['success'=>true,'message'=>'Thêm canh tác thành công','data'=>$new]);
}

// PUT – Cập nhật dynamic
function handlePut($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để cập nhật']);
        return;
    }
    // Kiểm tra tồn tại
    $chk = $conn->prepare("SELECT 1 FROM canh_tac WHERE ID = ?");
    $chk->bind_param('i',$id);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['error'=>"ID '$id' không tồn tại"]);
        return;
    }
    $in = json_decode(file_get_contents('php://input'), true);

    // Kiểm tra FK nếu có
    if (isset($in['MaVung']) && !existsVung($conn,$in['MaVung'])) {
        http_response_code(400);
        echo json_encode(['error'=>"MaVung '{$in['MaVung']}' không tồn tại"]);
        return;
    }
    if (isset($in['TenPhanBon']) && !existsPhanBon($conn,$in['TenPhanBon'])) {
        http_response_code(400);
        echo json_encode(['error'=>"TenPhanBon '{$in['TenPhanBon']}' không tồn tại"]);
        return;
    }

    // build dynamic
    $fields = [
      'NgayThucHien'=>'s','LoaiCongViec'=>'s','NguoiThucHien'=>'s',
      'MaVung'=>'s','TenPhanBon'=>'s','LieuLuong'=>'d','GhiChu'=>'s'
    ];
    $sets=[]; $types=''; $vars=[];
    foreach ($fields as $f=>$t) {
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

    $sql = "UPDATE canh_tac SET ".implode(', ',$sets)." WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    array_unshift($vars,$types);
    $refs = [];
    foreach ($vars as $k=>$v) $refs[] = &$vars[$k];
    call_user_func_array([$stmt,'bind_param'],$refs);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi cập nhật','details'=>$stmt->error]);
        return;
    }
    $sel = $conn->prepare("SELECT * FROM canh_tac WHERE ID = ?");
    $sel->bind_param('i',$id);
    $sel->execute();
    $upd = $sel->get_result()->fetch_assoc();

    echo json_encode(['success'=>true,'message'=>'Cập nhật thành công','data'=>$upd]);
}

// DELETE – Xóa theo ID
function handleDelete($conn) {
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $q);
    $id = $q['ID'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error'=>'Cần cung cấp ID để xóa']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM canh_tac WHERE ID = ?");
    $stmt->bind_param('i',$id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error'=>'Lỗi xóa','details'=>$stmt->error]);
        return;
    }
    echo json_encode(['success'=>true,'message'=>"Xóa canh tác ID='$id' thành công"]);
}
?>
