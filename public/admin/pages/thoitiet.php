<?php
require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ===== Helpers ===== */
function findTable(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) return $t;
  }
  return null;
}

/* ===== Table ===== */
$TABLE_WEATHER = findTable($conn, ['thoi_tiet','thoitiet']);
if (!$TABLE_WEATHER) {
  echo "<h1>Quản lý Thời tiết</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>thoi_tiet</code>/<code>thoitiet</code> trong CSDL.</div>";
  return;
}

/* ===== Hướng gió options ===== */
$huongGioOptions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

/* ===== Actions ===== */
if ($action === 'delete') {
  $ID = intval($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_WEATHER` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
    else $msg = "Đã xóa dữ liệu thời tiết ID: $ID";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $ID = intval($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("SELECT * FROM `$TABLE_WEATHER` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ThoiDiem'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']=='1';
  $ID = intval($_POST['ID'] ?? 0);

  $ThoiDiem = trim($_POST['ThoiDiem'] ?? '');
  $MaVung = trim($_POST['MaVung'] ?? '');
  $NhietDo = floatval($_POST['NhietDo'] ?? 0);
  $DoAm = floatval($_POST['DoAm'] ?? 0);
  $LuongMua = floatval($_POST['LuongMua'] ?? 0);
  $TocDoGio = floatval($_POST['TocDoGio'] ?? 0);
  $HuongGio = trim($_POST['HuongGio'] ?? '') ?: null;
  $ChiSoUV = floatval($_POST['ChiSoUV'] ?? 0);
  $MoTa = trim($_POST['MoTa'] ?? '') ?: null;

  if ($ThoiDiem === '') $err = 'Vui lòng nhập thời điểm';
  elseif ($MaVung === '') $err = 'Vui lòng nhập mã vùng';
  elseif ($NhietDo < -50 || $NhietDo > 60) $err = 'Nhiệt độ không hợp lệ';
  elseif ($DoAm < 0 || $DoAm > 100) $err = 'Độ ẩm phải từ 0-100%';
  else {
    if ($isEdit && $ID > 0) {
      $sql = "UPDATE `$TABLE_WEATHER` SET ThoiDiem=?, MaVung=?, NhietDo=?, DoAm=?, LuongMua=?, TocDoGio=?, HuongGio=?, ChiSoUV=?, MoTa=? WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssddddsdi', $ThoiDiem, $MaVung, $NhietDo, $DoAm, $LuongMua, $TocDoGio, $HuongGio, $ChiSoUV, $MoTa, $ID);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật thời tiết ID: $ID"; $editData = null; }
      $stmt->close();
    } else {
      $sql = "INSERT INTO `$TABLE_WEATHER` (ThoiDiem, MaVung, NhietDo, DoAm, LuongMua, TocDoGio, HuongGio, ChiSoUV, MoTa) VALUES (?,?,?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssddddsds', $ThoiDiem, $MaVung, $NhietDo, $DoAm, $LuongMua, $TocDoGio, $HuongGio, $ChiSoUV, $MoTa);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm dữ liệu thời tiết cho vùng: $MaVung";
      $stmt->close();
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where = ''; $types=''; $params=[];
if ($search !== '') {
  $where = "WHERE MaVung LIKE ? OR MoTa LIKE ? OR HuongGio LIKE ?";
  $kw = "%$search%"; $types='sss'; $params = [$kw,$kw,$kw];
}
$sql = "SELECT * FROM `$TABLE_WEATHER` $where ORDER BY ThoiDiem DESC LIMIT 300";
if ($params) { $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $list=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Thời tiết</h1>
<link rel="stylesheet" href="pages/layout/assets/css/thoitiet.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $editData ? 'Chỉnh sửa dữ liệu thời tiết' : 'Thêm dữ liệu thời tiết mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="ID" value="<?= $editData['ID'] ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>ID:</strong> <?= $editData['ID'] ?> - <strong>Thời điểm:</strong> <?= htmlspecialchars($editData['ThoiDiem']) ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Thời điểm *</strong></label>
      <input type="datetime-local" name="ThoiDiem" required value="<?= htmlspecialchars(str_replace(' ', 'T', $editData['ThoiDiem'] ?? '')) ?>">
    </div>

    <div class="col">
      <label><strong>Mã vùng *</strong></label>
      <input name="MaVung" required value="<?= htmlspecialchars($editData['MaVung'] ?? '') ?>" placeholder="VD: V001">
    </div>

    <div class="col">
      <label><strong>Nhiệt độ (°C) *</strong></label>
      <input type="number" step="0.1" min="-50" max="60" name="NhietDo" required value="<?= $editData['NhietDo'] ?? '' ?>">
    </div>

    <div class="col">
      <label><strong>Độ ẩm (%) *</strong></label>
      <input type="number" step="0.1" min="0" max="100" name="DoAm" required value="<?= $editData['DoAm'] ?? '' ?>">
    </div>

    <div class="col">
      <label><strong>Lượng mưa (mm)</strong></label>
      <input type="number" step="0.1" min="0" name="LuongMua" value="<?= $editData['LuongMua'] ?? 0 ?>">
    </div>

    <div class="col">
      <label><strong>Tốc độ gió (m/s)</strong></label>
      <input type="number" step="0.1" min="0" name="TocDoGio" value="<?= $editData['TocDoGio'] ?? 0 ?>">
    </div>

    <div class="col">
      <label><strong>Hướng gió</strong></label>
      <?php $hgSel = $editData['HuongGio'] ?? ''; ?>
      <select name="HuongGio">
        <option value="">-- Chọn hướng gió --</option>
        <?php foreach ($huongGioOptions as $hg): ?>
          <option value="<?= $hg ?>" <?= $hgSel===$hg?'selected':'' ?>><?= $hg ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Chỉ số UV</strong></label>
      <input type="number" step="0.1" min="0" max="15" name="ChiSoUV" value="<?= $editData['ChiSoUV'] ?? 0 ?>">
    </div>

    <div class="full">
      <label><strong>Mô tả</strong></label>
      <input name="MoTa" value="<?= htmlspecialchars($editData['MoTa'] ?? '') ?>" placeholder="VD: Nắng nóng, mưa nhẹ...">
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($editData): ?><a class="btn secondary" href="index.php?p=thoitiet">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SEARCH -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="thoitiet">
    <input name="search" placeholder="Tìm theo mã vùng, mô tả, hướng gió..." value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a href="index.php?p=thoitiet" class="btn secondary">Xóa lọc</a><?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>🌤️ Dữ liệu thời tiết</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> bản ghi</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Thời điểm</th>
          <th>Mã vùng</th>
          <th>Nhiệt độ</th>
          <th>Độ ẩm</th>
          <th>Lượng mưa</th>
          <th>Gió</th>
          <th>UV</th>
          <th>Mô tả</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="10" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= $r['ID'] ?></strong></td>
          <td><?= date('d/m/Y H:i', strtotime($r['ThoiDiem'])) ?></td>
          <td><span class="badge region"><?= htmlspecialchars($r['MaVung']) ?></span></td>
          <td><?= $r['NhietDo'] ?>°C</td>
          <td><?= $r['DoAm'] ?>%</td>
          <td><?= $r['LuongMua'] ?>mm</td>
          <td><?= htmlspecialchars($r['HuongGio'] ?? '-') ?> <?= $r['TocDoGio'] ?>m/s</td>
          <td><?= $r['ChiSoUV'] ?></td>
          <td><?= htmlspecialchars($r['MoTa'] ?? '-') ?></td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=thoitiet&action=edit&id=<?= $r['ID'] ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=thoitiet&action=delete&id=<?= $r['ID'] ?>"
               title="Xóa" data-confirm="Xóa dữ liệu thời tiết này không thể khôi phục?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/thoitiet.js"></script>
  </div>
</div>