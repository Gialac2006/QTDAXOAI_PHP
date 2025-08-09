<?php
require __DIR__ . '/../../connect.php'; // $conn

// Tìm bảng tồn tại trong số các tên ứng viên
function findTable(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) return $t;
  }
  return null;
}

$TABLE_HO = findTable($conn, ['honongdan','ho_nong_dan']);
if (!$TABLE_HO) {
  echo "<h1>👨‍🌾 Hộ nông dân</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>honongdan</code> hoặc <code>ho_nong_dan</code> trong CSDL <b>qlvtxoai</b>. Vui lòng tạo/đổi tên bảng.</div>";
  return;
}

$msg = $err = null;

// XÓA nhanh (?delete=MaHo)
if (isset($_GET['delete'])) {
  $MaHo = trim($_GET['delete']);
  if ($MaHo !== '') {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_HO` WHERE MaHo=?");
    $stmt->bind_param("s", $MaHo);
    if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
    else $msg = "Đã xóa hộ: $MaHo";
    $stmt->close();
  }
}

// THÊM nhanh (POST tối thiểu: TenChuHo, SoDienThoai, DiaChi)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['TenChuHo'])) {
  $TenChuHo    = trim($_POST['TenChuHo'] ?? '');
  $SoDienThoai = trim($_POST['SoDienThoai'] ?? '');
  $DiaChi      = trim($_POST['DiaChi'] ?? '');
  $MaHo        = 'HO'.date('ymdHis');

  if ($TenChuHo==='') { $err = 'Vui lòng nhập Tên chủ hộ'; }
  else {
    $stmt = $conn->prepare("INSERT INTO `$TABLE_HO` (MaHo,TenChuHo,SoDienThoai,DiaChi) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $MaHo, $TenChuHo, $SoDienThoai, $DiaChi);
    if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
    else $msg = "Đã thêm hộ: $TenChuHo";
    $stmt->close();
  }
}

// Lấy danh sách an toàn
$list = [];
$q = $conn->query("SELECT MaHo, TenChuHo, SoDienThoai, DiaChi FROM `$TABLE_HO` ORDER BY TenChuHo ASC LIMIT 200");
if ($q) $list = $q->fetch_all(MYSQLI_ASSOC);
?>
<h1>👨‍🌾 Hộ nông dân</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="card">
  <div class="toolbar">
    <form method="post" class="toolbar" style="flex-wrap:wrap; gap:8px">
      <input name="TenChuHo" placeholder="Tên chủ hộ *" required>
      <input name="SoDienThoai" placeholder="SĐT">
      <input name="DiaChi" style="min-width:260px" placeholder="Địa chỉ">
      <button class="btn">Thêm nhanh</button>
      <span class="muted">* Form tối thiểu. Có thể mở rộng theo CSDL.</span>
    </form>
  </div>

  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th style="width:120px">Mã hộ</th>
          <th>Tên chủ hộ</th>
          <th style="width:140px">SĐT</th>
          <th>Địa chỉ</th>
          <th style="width:100px"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="5" class="muted">Chưa có dữ liệu.</td></tr>
        <?php else: foreach($list as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['MaHo']); ?></td>
            <td><?php echo htmlspecialchars($r['TenChuHo']); ?></td>
            <td><?php echo htmlspecialchars($r['SoDienThoai']); ?></td>
            <td><?php echo htmlspecialchars($r['DiaChi']); ?></td>
            <td>
              <a href="index.php?p=honongdan&delete=<?php echo urlencode($r['MaHo']); ?>"
                 class="btn danger" data-confirm="Xóa hộ <?php echo htmlspecialchars($r['MaHo']); ?>?">Xóa</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
