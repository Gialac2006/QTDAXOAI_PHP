<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Xử lý preflight request từ browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Kết nối database
$servername = "localhost";
$database = "qlvtxoai";
$username = "root";
$password = "password";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $database, $port);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Lỗi kết nối database']);
    exit;
}

// Routing theo HTTP method
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET': handleGet($conn); break;
    case 'POST': handlePost($conn); break;
    case 'PUT': handlePut($conn); break;
    case 'DELETE': handleDelete($conn); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']); break;
}

// ============= GET API (Lấy dữ liệu) =============
// Lấy thông tin vai trò
function handleGet($conn) {
    $tenVaiTro = $_GET['TenVaiTro'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;

    try {
        if ($tenVaiTro) {
            // Lấy theo tên vai trò
            $sql = "SELECT * FROM vai_tro WHERE TenVaiTro = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $tenVaiTro);
        } else {
            // Lấy tất cả với phân trang
            $sql = "SELECT * FROM vai_tro LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        // Đếm tổng số bản ghi
        $countSql = "SELECT COUNT(*) as total FROM vai_tro";
        $countResult = $conn->query($countSql);
        $total = $countResult->fetch_assoc()['total'];

        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Lỗi khi lấy dữ liệu', 'details' => $e->getMessage()]);
    }
}

// ============= POST API (Thêm dữ liệu mới) =============
// Thêm vai trò mới
function handlePost($conn) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dữ liệu JSON không hợp lệ']);
        return;
    }

    // Validate dữ liệu
    if (!isset($data['TenVaiTro'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
        return;
    }

    try {
        $sql = "INSERT INTO vai_tro (TenVaiTro, MoTa) 
                VALUES (?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $data['TenVaiTro'], $data['MoTa']);
        
        if ($stmt->execute()) {
            $newId = $conn->insert_id;

            // Lấy dữ liệu vừa thêm vào từ database
            $sqlSelect = "SELECT * FROM vai_tro WHERE TenVaiTro = ?";
            $stmtSelect = $conn->prepare($sqlSelect);
            $stmtSelect->bind_param("s", $data['TenVaiTro']);
            $stmtSelect->execute();
            $result = $stmtSelect->get_result();
            $newVaiTro = $result->fetch_assoc();

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Thêm vai trò thành công', 'data' => $newVaiTro]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Lỗi khi thêm dữ liệu', 'details' => $e->getMessage()]);
    }
}

// ============= PUT API (Cập nhật dữ liệu) =============
// Cập nhật vai trò theo tên vai trò
function handlePut($conn) {
    $tenVaiTro = $_GET['TenVaiTro'] ?? null;
    if (!$tenVaiTro) {
        http_response_code(400);
        echo json_encode(['error' => 'Cần cung cấp TenVaiTro để cập nhật']);
        return;
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    try {
        $sql = "UPDATE vai_tro SET 
                MoTa = ?
                WHERE TenVaiTro = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $data['MoTa'], $tenVaiTro);

        if ($stmt->execute()) {
            // Truy vấn lại để lấy dữ liệu đã cập nhật
            $sqlSelect = "SELECT * FROM vai_tro WHERE TenVaiTro = ?";
            $stmtSelect = $conn->prepare($sqlSelect);
            $stmtSelect->bind_param("s", $tenVaiTro);
            $stmtSelect->execute();
            $result = $stmtSelect->get_result();
            $updatedVaiTro = $result->fetch_assoc();

            echo json_encode(['success' => true, 'message' => 'Cập nhật vai trò thành công', 'data' => $updatedVaiTro]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Lỗi khi cập nhật dữ liệu', 'details' => $e->getMessage()]);
    }
}

// ============= DELETE API (Xóa dữ liệu) =============
// Xóa vai trò theo tên vai trò
function handleDelete($conn) {
    $tenVaiTro = $_GET['TenVaiTro'] ?? null;
    if (!$tenVaiTro) {
        http_response_code(400);
        echo json_encode(['error' => 'Cần cung cấp TenVaiTro để xóa']);
        return;
    }

    try {
        $sql = "DELETE FROM vai_tro WHERE TenVaiTro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $tenVaiTro);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Xóa vai trò thành công']);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Lỗi khi xóa dữ liệu', 'details' => $e->getMessage()]);
    }
}

// ============= Helper Functions =============

// Kiểm tra trùng lặp
function checkDuplicates($conn, $tenVaiTro) {
    $sql = "SELECT * FROM vai_tro WHERE TenVaiTro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tenVaiTro);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

$conn->close();
?>
