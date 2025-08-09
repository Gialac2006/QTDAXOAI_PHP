<?php
// UTF-8 (không BOM)
require_once __DIR__ . '/config.php';

$conn = @new mysqli($servername, $username, $password, $database, $port);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Kết nối CSDL thất bại',
        'details' => $conn->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$conn->set_charset('utf8mb4');
