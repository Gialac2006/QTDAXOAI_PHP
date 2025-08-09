<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){ http_response_code(200); exit; }

require_once __DIR__.'/../connect.php';

$id = $_GET['ID'] ?? null;

if ($id) {
    $st = $conn->prepare("SELECT * FROM lich_bao_tri WHERE ID=?");
    $st->bind_param('i',$id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) {
        echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'Không tìm thấy'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    }
} else {
    $rs = $conn->query("SELECT * FROM lich_bao_tri ORDER BY ID DESC");
    $data = $rs->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}
