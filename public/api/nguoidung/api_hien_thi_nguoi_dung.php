<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$rows = $conn->query("SELECT TenDangNhap,HoTen,Email,VaiTro FROM nguoi_dung ORDER BY TenDangNhap ASC")->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success'=>true,'total'=>count($rows),'data'=>$rows], JSON_UNESCAPED_UNICODE);
