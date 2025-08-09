<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$rs = $conn->query("SELECT TenVaiTro, MoTa FROM vai_tro ORDER BY TenVaiTro");
echo json_encode(['success'=>true,'data'=>$rs->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
