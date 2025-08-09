<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method Not Allowed']); exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];

// BẮT BUỘC: MaMuaVu + Nam
foreach (['MaMuaVu','Nam'] as $f) {
  if (empty($in[$f])) { http_response_code(400);
    echo json_encode(['success'=>false,'error'=>"Thiếu $f"]); exit;
  }
}

$MaMuaVu = trim($in['MaMuaVu']);
$Nam      = (string)$in['Nam'];
$Dot      = $in['Dot'] ?? null;
$NBD      = $in['NgayBatDau'] ?? null;
$NKT      = $in['NgayKetThuc'] ?? null;

// Validate date nếu có
foreach ([['NgayBatDau',$NBD],['NgayKetThuc',$NKT]] as [$k,$v]) {
  if ($v && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>"$k phải YYYY-MM-DD"]); exit;
  }
}

$st = $conn->prepare("INSERT INTO mua_vu (MaMuaVu,Nam,Dot,NgayBatDau,NgayKetThuc) VALUES (?,?,?,?,?)");
$st->bind_param('sssss', $MaMuaVu,$Nam,$Dot,$NBD,$NKT);

if (!$st->execute()) {
  http_response_code($conn->errno==1062?409:500);
  echo json_encode(['success'=>false,'error'=>$conn->errno==1062?'Trùng MaMuaVu':$conn->error]); exit;
}

http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>[
  'MaMuaVu'=>$MaMuaVu,'Nam'=>$Nam,'Dot'=>$Dot,'NgayBatDau'=>$NBD,'NgayKetThuc'=>$NKT
]]);
