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
$cntVung = safeCount($conn, ['vungtrong','vung_trong']);
$cntIoT  = safeCount($conn, ['thietbiiot','thiet_bi_iot']);

$missing = [];
if ($cntHo   === null) $missing[] = 'honongdan/ho_nong_dan';
if ($cntVung === null) $missing[] = 'vungtrong/vung_trong';
if ($cntIoT  === null) $missing[] = 'thietbiiot/thiet_bi_iot';
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
    <div class="muted">Vùng trồng</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntVung===null?'—':$cntVung; ?></div>
  </div>
  <div class="card">
    <div class="muted">Thiết bị IoT</div>
    <div style="font-size:28px;font-weight:800"><?php echo $cntIoT===null?'—':$cntIoT; ?></div>
  </div>
</div>
