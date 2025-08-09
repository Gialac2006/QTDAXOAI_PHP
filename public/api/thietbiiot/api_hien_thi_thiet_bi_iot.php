<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../connect.php';

$mv = $_GET['MaVung'] ?? null;
if ($mv !== null && $mv !== '') {
  $st = $conn->prepare("SELECT MaThietBi,TenThietBi,NgayLapDat,TinhTrang,MaVung,MaKetNoi
                        FROM thiet_bi_iot WHERE MaVung=? ORDER BY MaThietBi");
  $st->bind_param('s', $mv); $st->execute(); $rs = $st->get_result();
} else {
  $rs = $conn->query("SELECT MaThietBi,TenThietBi,NgayLapDat,TinhTrang,MaVung,MaKetNoi
                      FROM thiet_bi_iot ORDER BY MaThietBi");
}
echo json_encode(['success'=>true,'data'=>$rs->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
