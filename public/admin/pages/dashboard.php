<?php
require __DIR__ . '/../../connect.php'; // $conn

// Helper: tìm bảng tồn tại & COUNT(*)
function safeCount(mysqli $conn, array $candidates): ?int {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) {
      $q = $conn->query("SELECT COUNT(*) c FROM `{$t}`");
      if ($q) return (int)($q->fetch_assoc()['c'] ?? 0);
    }
  }
  return null; // không có bảng nào
}

$cntHo   = safeCount($conn, ['honongdan','ho_nong_dan']);
$cntGiong   = safeCount($conn, ['giongxoai','giong_xoai']);
$cntVung = safeCount($conn, ['vungtrong','vung_trong']);
$cntTen = safeCount($conn, ['nguoidung','nguoi_dung']);
$cntBvtv = safeCount($conn, ['thuocbvtv','thuoc_bvtv']);
$cntPhan = safeCount($conn, ['phanbon','phan_bon']);
$cntCanh = safeCount($conn, ['canhtac','canh_tac']);
$cntMay = safeCount($conn, ['thietbimaymoc','thiet_bi_may_moc']);
$cntIot = safeCount($conn, ['thietbiiot','thiet_bi_iot']);
$cntNhatky = safeCount($conn, ['nhatkyphunthuoc','nhat_ky_phun_thuoc']);
$cntThoitiet = safeCount($conn, ['thoitiet','thoi_tiet']);
$cntBaotri = safeCount($conn, ['lichbaotri','lich_bao_tri']);
$cntBaocao = safeCount($conn, ['baocaosanluong','bao_cao_san_luong']);
$cntBando = safeCount($conn, ['bandogis','ban_do_gis']);
$cntGiaxoai = safeCount($conn, ['giaxoai','gia_xoai']);



$missing = [];
if ($cntHo   === null) $missing[] = 'honongdan/ho_nong_dan';
if ($cntGiong   === null) $missing[] = 'giongxoai/giong_xoai';
if ($cntVung === null) $missing[] = 'vungtrong/vung_trong';
if ($cntTen === null) $missing[] = 'nguoidung/nguoi_dung';
if ($cntBvtv === null) $missing[] = 'thuocbvtv/thuoc_bvtv';
if ($cntPhan === null) $missing[] = 'phanbon/phan_bon';
if ($cntCanh === null) $missing[] = 'canhtac/canh_tac';
if ($cntMay === null) $missing[] = 'thietbimaymoc/thiet_bi_may_moc';
if ($cntIot === null) $missing[] = 'thietbiiot/thiet_bi_iot';
if ($cntNhatky === null) $missing[] = 'nhatkyphunthuoc/nhat_ky_phun_thuoc';
if ($cntThoitiet === null) $missing[] = 'thoitiet/thoi_tiet';
if ($cntBaotri === null) $missing[] = 'lichbaotri/lich_bao_tri';
if ($cntBaocao === null) $missing[] = 'baocaosanluong/bao_cao_san_luong';
if ($cntBando === null) $missing[] = 'bandogis/ban_do_gis';
if ($cntGiaxoai=== null) $missing[] = 'giaxoai/gia_xoai';





?>
<h1>📊 Tổng quan</h1>
<?php if ($missing): ?>
  <div class="msg err" style="display:block">
    Thiếu bảng: <?php echo htmlspecialchars(implode(', ', $missing)); ?>.
    Vui lòng kiểm tra tên bảng trong CSDL <b>qlvtxoai</b> hoặc đổi tên trong file <code>dashboard.php</code>.
  </div>
<?php endif; ?>

<div class="cards">
  <div class="card">
    <div class="muted">Hộ nông dân</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntHo===null?'—':$cntHo; ?></div>
  </div>
   <div class="card">
    <div class="muted">Giống xoài</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntGiong===null?'—':$cntGiong; ?></div>
  </div>
  <div class="card">
    <div class="muted">Vùng trồng</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntVung===null?'—':$cntVung; ?></div>
  </div>
  <div class="card">
    <div class="muted">Người dùng</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntTen===null?'—':$cntTen; ?></div>
  </div>
   <div class="card">
    <div class="muted">Thuốc bảo vệ thực vật</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntBvtv===null?'—':$cntBvtv; ?></div>
  </div>
   <div class="card">
    <div class="muted">Phân bón</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntPhan===null?'—':$cntPhan; ?></div>
  </div>
   <div class="card">
    <div class="muted">Canh tác</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntCanh===null?'—':$cntCanh; ?></div>
  </div>
   <div class="card">
    <div class="muted">Thiết bị máy móc</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntMay===null?'—':$cntMay; ?></div>
  </div>
  <div class="card">
    <div class="muted">Thiết bị Iot</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntIot===null?'—':$cntIot; ?></div>
  </div>
   <div class="card">
    <div class="muted">Nhật ký phun thuốc</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntNhatky===null?'—':$cntNhatky; ?></div>
  </div>
  <div class="card">
    <div class="muted">Nhật ký phun thuốc</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntThoitiet===null?'—':$cntThoitiet; ?></div>
  </div>
  <div class="card">
    <div class="muted">Lịch bảo trì</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntBaotri===null?'—':$cntBaotri; ?></div>
  </div>
 <div class="card">
    <div class="muted">Báo cáo sản lượng</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntBaocao===null?'—':$cntBaocao; ?></div>
  </div>
  <div class="card">
    <div class="muted">Bản đồ gis</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntBando===null?'—':$cntBando; ?></div>
  </div>
  <div class="card">
    <div class="muted">Giá xoài</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntGiaxoai===null?'—':$cntGiaxoai; ?></div>
  </div>
</div>