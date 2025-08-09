<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$in = json_decode(file_get_contents('php://input'), true) ?: [];
foreach (['MaThietBi','TenThietBi'] as $f)
  if (empty($in[$f])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>"Thiếu $f"], JSON_UNESCAPED_UNICODE); exit; }
if (!empty($in['NamSuDung']) && !preg_match('/^\d{4}$/', (string)$in['NamSuDung'])) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>'NamSuDung phải yyyy'], JSON_UNESCAPED_UNICODE); exit;
}

$MaThietBi = trim($in['MaThietBi']);
$Ten       = trim($in['TenThietBi']);
$Loai      = $in['LoaiThietBi'] ?? null;
$Nam       = $in['NamSuDung']   ?? null;
$TinhTrang = $in['TinhTrang']   ?? null;      // mặc định 'Tốt' nếu null theo SQL
$LienKet   = $in['LienKetVungHo'] ?? null;

$st = $conn->prepare("INSERT INTO thiet_bi_may_moc (MaThietBi,TenThietBi,LoaiThietBi,NamSuDung,TinhTrang,LienKetVungHo)
                      VALUES (?,?,?,?,?,?)");
$st->bind_param('ssssss', $MaThietBi,$Ten,$Loai,$Nam,$TinhTrang,$LienKet);
if(!$st->execute()){
  http_response_code($conn->errno==1062?409:400);
  echo json_encode(['success'=>false,'error'=>$conn->errno==1062?'Trùng MaThietBi':$conn->error], JSON_UNESCAPED_UNICODE); exit;
}
http_response_code(201);
echo json_encode(['success'=>true,'message'=>'Đã tạo','data'=>['MaThietBi'=>$MaThietBi]], JSON_UNESCAPED_UNICODE);
