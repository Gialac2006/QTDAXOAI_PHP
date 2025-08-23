<?php
require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ===== Helpers ===== */
function u_trim($str, $limit = 40, $suffix = '…'){
  $str = (string)$str;
  if (function_exists('mb_strimwidth')) return mb_strimwidth($str, 0, $limit, $suffix, 'UTF-8');
  if ($str === '') return '';
  if (!preg_match_all('/./u', $str, $m)) return $str;
  $chs = $m[0]; if (count($chs) <= $limit) return $str;
  return implode('', array_slice($chs, 0, $limit)).$suffix;
}
function findTable(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) return $t;
  }
  return null;
}

/* ===== Tables ===== */
$T_HO = findTable($conn, ['ho_nong_dan','honongdan']);
if (!$T_HO) {
  echo "<h1>Quản lý Hộ nông dân</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>ho_nong_dan</code>/<code>honongdan</code> trong CSDL.</div>";
  return;
}

/* ===== Actions ===== */
$msg = $err = null;
$action = $_GET['action'] ?? '';
$edit   = null;

if ($action === 'delete') {
  $MaHo = trim($_GET['id'] ?? '');
  if ($MaHo !== '') {
    $stmt = $conn->prepare("DELETE FROM `$T_HO` WHERE MaHo=?");
    $stmt->bind_param('s', $MaHo);
    if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
    else $msg = "Đã xóa hộ: $MaHo";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $MaHo = trim($_GET['id'] ?? '');
  if ($MaHo !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$T_HO` WHERE MaHo=?");
    $stmt->bind_param('s', $MaHo);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenChuHo'])) {
  $isEdit      = !empty($_POST['MaHo']);
  $MaHo        = trim($_POST['MaHo'] ?? '');
  $TenChuHo    = trim($_POST['TenChuHo'] ?? '');
  $CCCD        = trim($_POST['CCCD'] ?? '');
  $NgaySinh    = trim($_POST['NgaySinh'] ?? '') ?: null;
  $SoDienThoai = trim($_POST['SoDienThoai'] ?? '') ?: null;
  $DiaChi      = trim($_POST['DiaChi'] ?? '') ?: null;
  $SoThanhVien = (int)($_POST['SoThanhVien'] ?? 1); if ($SoThanhVien < 1) $SoThanhVien = 1;
  $LoaiDat     = trim($_POST['LoaiDat'] ?? '') ?: null;
  $DienTich    = $_POST['DienTich'] === '' ? null : (float)$_POST['DienTich'];

  if ($TenChuHo === '')      $err = 'Vui lòng nhập Tên chủ hộ';
  elseif ($CCCD === '')      $err = 'Vui lòng nhập CCCD';
  else {
    if ($isEdit) {
      $sql = "UPDATE `$T_HO`
              SET TenChuHo=?, CCCD=?, NgaySinh=?, SoDienThoai=?, DiaChi=?, SoThanhVien=?, LoaiDat=?, DienTich=?
              WHERE MaHo=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sssssisds', $TenChuHo, $CCCD, $NgaySinh, $SoDienThoai, $DiaChi, $SoThanhVien, $LoaiDat, $DienTich, $MaHo);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật hộ: $TenChuHo"; $edit = null; }
      $stmt->close();
    } else {
      // Tạo mã HO001..HO999 tránh trùng
      do {
        $gen = 'HO'.sprintf('%03d', rand(1, 999));
        $dup = $conn->query("SELECT MaHo FROM `$T_HO` WHERE MaHo='{$conn->real_escape_string($gen)}'");
      } while ($dup && $dup->num_rows);

      $sql = "INSERT INTO `$T_HO` (MaHo, TenChuHo, CCCD, NgaySinh, SoDienThoai, DiaChi, SoThanhVien, LoaiDat, DienTich)
              VALUES (?,?,?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssssssisd', $gen, $TenChuHo, $CCCD, $NgaySinh, $SoDienThoai, $DiaChi, $SoThanhVien, $LoaiDat, $DienTich);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm hộ: $TenChuHo (Mã: $gen)";
      $stmt->close();
    }
  }
}

/* ===== Options (Loại đất: lấy từ dữ liệu + mặc định) ===== */
$loaiDatOpts = ['Phù Sa','Đồng ruộng','Đất vườn','Đất thổ cư'];
$res = $conn->query("SELECT DISTINCT LoaiDat FROM `$T_HO` WHERE LoaiDat IS NOT NULL AND TRIM(LoaiDat)<>''");
if ($res) while ($r = $res->fetch_assoc()) {
  $v = trim($r['LoaiDat']);
  if ($v !== '' && !in_array($v, $loaiDatOpts, true)) $loaiDatOpts[] = $v;
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where  = ''; $types=''; $params=[];
if ($search !== '') {
  $where = "WHERE TenChuHo LIKE ? OR CCCD LIKE ? OR SoDienThoai LIKE ? OR DiaChi LIKE ?";
  $kw = "%$search%"; $types='ssss'; $params = [$kw,$kw,$kw,$kw];
}
$sql = "SELECT * FROM `$T_HO` $where ORDER BY NgayDangKy DESC, TenChuHo ASC LIMIT 200";
if ($params){ $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $rows=$stmt->get_result(); $list=$rows->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Hộ nông dân</h1>
<link rel="stylesheet" href="pages/layout/assets/css/honongdan.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ===== FORM ===== -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa hộ nông dân' : 'Thêm hộ nông dân mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($edit): ?>
      <input type="hidden" name="MaHo" value="<?= htmlspecialchars($edit['MaHo']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã hộ:</strong> <?= htmlspecialchars($edit['MaHo']) ?>
        <?php if (!empty($edit['NgayDangKy'])): ?>
          <span class="muted">| Đăng ký: <?= htmlspecialchars($edit['NgayDangKy']) ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Tên chủ hộ *</strong></label>
      <input name="TenChuHo" required value="<?= htmlspecialchars($edit['TenChuHo'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>CCCD *</strong></label>
      <input name="CCCD" required value="<?= htmlspecialchars($edit['CCCD'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Ngày sinh</strong></label>
      <input type="date" name="NgaySinh" value="<?= htmlspecialchars($edit['NgaySinh'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Số điện thoại</strong></label>
      <input name="SoDienThoai" value="<?= htmlspecialchars($edit['SoDienThoai'] ?? '') ?>">
    </div>

    <div class="full">
      <label><strong>Địa chỉ</strong></label>
      <input name="DiaChi" value="<?= htmlspecialchars($edit['DiaChi'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Số thành viên</strong></label>
      <input type="number" name="SoThanhVien" min="1" value="<?= htmlspecialchars($edit['SoThanhVien'] ?? 1) ?>">
    </div>

    <div class="col">
      <label><strong>Loại đất</strong></label>
      <?php $ld = $edit['LoaiDat'] ?? ''; ?>
      <select name="LoaiDat">
        <option value="">-- Chọn loại đất --</option>
        <?php foreach ($loaiDatOpts as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= $ld===$o?'selected':'' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Diện tích (m²)</strong></label>
      <input type="number" step="0.1" min="0" name="DienTich" value="<?= htmlspecialchars($edit['DienTich'] ?? '') ?>">
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=honongdan">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ===== SEARCH ===== -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="honongdan">
    <input name="search" placeholder="Tìm theo tên, CCCD, SĐT, địa chỉ..." value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a class="btn secondary" href="index.php?p=honongdan">Xóa lọc</a><?php endif; ?>
  </form>
</div>

<!-- ===== LIST ===== -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách hộ nông dân</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> hộ</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Mã hộ</th>
          <th>Tên chủ hộ</th>
          <th>CCCD</th>
          <th>SĐT</th>
          <th>Địa chỉ</th>
          <th>Thành viên</th>
          <th>Loại đất</th>
          <th>Diện tích</th>
          <th>Ngày ĐK</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="10" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['MaHo']) ?></strong></td>
          <td><?= htmlspecialchars($r['TenChuHo']) ?></td>
          <td><?= htmlspecialchars($r['CCCD']) ?></td>
          <td><?= htmlspecialchars($r['SoDienThoai'] ?? '-') ?></td>
          <td title="<?= htmlspecialchars($r['DiaChi'] ?? '') ?>"><?= htmlspecialchars(u_trim($r['DiaChi'] ?? '-', 30)) ?></td>
          <td style="text-align:center"><?= (int)$r['SoThanhVien'] ?></td>
          <td><?= htmlspecialchars($r['LoaiDat'] ?? '-') ?></td>
          <td style="text-align:right"><?= $r['DienTich']!==null && $r['DienTich']!=='' ? number_format((float)$r['DienTich'],1).' m²' : '-' ?></td>
          <td><?= htmlspecialchars($r['NgayDangKy'] ?? '-') ?></td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=honongdan&action=edit&id=<?= urlencode($r['MaHo']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=honongdan&action=delete&id=<?= urlencode($r['MaHo']) ?>"
               title="Xóa" data-confirm="Xóa hộ '<?= htmlspecialchars($r['TenChuHo']) ?>' không thể khôi phục?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/honongdan.js"></script>
  </div>
</div>
