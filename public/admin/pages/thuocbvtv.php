<?php
require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ===== Helpers ===== */
function u_trim($str, $limit = 60, $suffix='…'){
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

/* ===== Table ===== */
$T_THUOC = findTable($conn, ['thuoc_bvtv','thuocbvtv']);
if (!$T_THUOC) {
  echo "<h1>Quản lý Thuốc BVTV</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>thuoc_bvtv</code>/<code>thuocbvtv</code> trong CSDL.</div>";
  return;
}

/* ===== Đơn vị tính (options) ===== */
$donViOptions = ['gói','chai','lọ','ống','ml','lít','g','kg'];
$res = $conn->query("SELECT DISTINCT DonViTinh FROM `$T_THUOC` WHERE DonViTinh IS NOT NULL AND TRIM(DonViTinh)<>''");
if ($res) while($r = $res->fetch_assoc()){
  $dv = trim($r['DonViTinh']);
  if ($dv !== '' && !in_array($dv, $donViOptions, true)) $donViOptions[] = $dv;
}

/* ===== Actions ===== */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $TenThuoc = trim($_GET['id'] ?? '');
  if ($TenThuoc !== '') {
    $stmt = $conn->prepare("DELETE FROM `$T_THUOC` WHERE TenThuoc=?");
    $stmt->bind_param('s', $TenThuoc);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá thuốc: $TenThuoc";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $TenThuoc = trim($_GET['id'] ?? '');
  if ($TenThuoc !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$T_THUOC` WHERE TenThuoc=?");
    $stmt->bind_param('s', $TenThuoc);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenThuoc'])) {
  $isEdit      = isset($_POST['TenThuoc_original']);
  $orig        = trim($_POST['TenThuoc_original'] ?? '');
  $TenThuoc    = trim($_POST['TenThuoc'] ?? '');
  $HoatChat    = trim($_POST['HoatChat'] ?? '') ?: null;

  $DonViTinh = trim($_POST['DonViTinh'] ?? '');
  if ($DonViTinh === '__NEW__') $DonViTinh = trim($_POST['DonViTinh_new'] ?? '');
  if ($DonViTinh === '') $DonViTinh = null;

  $GhiChu      = trim($_POST['GhiChu'] ?? '') ?: null;

  if ($TenThuoc === '') $err = 'Vui lòng nhập Tên thuốc';
  else {
    if ($isEdit) {
      if ($TenThuoc !== $orig) {
        $chk = $conn->prepare("SELECT 1 FROM `$T_THUOC` WHERE TenThuoc=?");
        $chk->bind_param('s', $TenThuoc);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $err = "Tên thuốc đã tồn tại";
        $chk->close();
      }
      if (!$err) {
        $sql = "UPDATE `$T_THUOC` SET TenThuoc=?, HoatChat=?, DonViTinh=?, GhiChu=? WHERE TenThuoc=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $TenThuoc, $HoatChat, $DonViTinh, $GhiChu, $orig);
        if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
        else { $msg = "Đã cập nhật thuốc: $TenThuoc"; $edit = null; }
        $stmt->close();
      }
    } else {
      $sql = "INSERT INTO `$T_THUOC` (TenThuoc, HoatChat, DonViTinh, GhiChu) VALUES (?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssss', $TenThuoc, $HoatChat, $DonViTinh, $GhiChu);
      if (!$stmt->execute()) {
        if ($conn->errno == 1062) $err = "Tên thuốc đã tồn tại";
        else $err = "Không thêm được: ".$stmt->error;
      } else $msg = "Đã thêm thuốc: $TenThuoc";
      $stmt->close();
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where=''; $types=''; $params=[];
if ($search !== '') {
  $where = "WHERE TenThuoc LIKE ? OR HoatChat LIKE ? OR DonViTinh LIKE ? OR GhiChu LIKE ?";
  $kw = "%$search%"; $types='ssss'; $params = [$kw,$kw,$kw,$kw];
}
$sql = "SELECT * FROM `$T_THUOC` $where ORDER BY TenThuoc ASC LIMIT 400";
if ($params){ $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $rows=$stmt->get_result(); $list=$rows->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Thuốc BVTV</h1>
<link rel="stylesheet" href="pages/layout/assets/css/thuocbvtv.css">

<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ===== FORM ===== -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa thuốc' : 'Thêm thuốc mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($edit): ?>
      <input type="hidden" name="TenThuoc_original" value="<?= htmlspecialchars($edit['TenThuoc']) ?>">
      <div class="full muted" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Tên cũ:</strong> <?= htmlspecialchars($edit['TenThuoc']) ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Tên thuốc *</strong></label>
      <input name="TenThuoc" required value="<?= htmlspecialchars($edit['TenThuoc'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Hoạt chất</strong></label>
      <input name="HoatChat" placeholder="vd: Mancozeb / Abamectin…" value="<?= htmlspecialchars($edit['HoatChat'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Đơn vị tính</strong></label>
      <?php $dvSel = $edit['DonViTinh'] ?? ''; ?>
      <select name="DonViTinh" id="selDonVi">
        <option value="">-- Chọn đơn vị --</option>
        <?php foreach ($donViOptions as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= $dvSel===$o?'selected':'' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
        <option value="__NEW__">Khác (nhập)</option>
      </select>
      <input type="text" name="DonViTinh_new" id="inpDonViNew" placeholder="Nhập đơn vị" style="display:none;margin-top:8px">
    </div>

    <div class="full">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" rows="3" placeholder="Công dụng, lưu ý sử dụng…"><?= htmlspecialchars($edit['GhiChu'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=thuocbvtv">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ===== SEARCH ===== -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="thuocbvtv">
    <input name="search" placeholder="Tìm theo tên thuốc, hoạt chất, đơn vị, ghi chú…" value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a href="index.php?p=thuocbvtv" class="btn secondary">Xoá lọc</a><?php endif; ?>
  </form>
</div>

<!-- ===== LIST ===== -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách thuốc</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> thuốc</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tên thuốc</th>
          <th>Hoạt chất</th>
          <th>Đơn vị</th>
          <th>Ghi chú</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="5" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['TenThuoc']) ?></strong></td>
          <td><?= htmlspecialchars($r['HoatChat'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['DonViTinh'] ?? '-') ?></td>
          <td title="<?= htmlspecialchars($r['GhiChu'] ?? '') ?>"><?= htmlspecialchars(u_trim($r['GhiChu'] ?? '-', 70)) ?></td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=thuocbvtv&action=edit&id=<?= urlencode($r['TenThuoc']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=thuocbvtv&action=delete&id=<?= urlencode($r['TenThuoc']) ?>" 
               title="Xoá" data-confirm="Xoá thuốc '<?= htmlspecialchars($r['TenThuoc']) ?>'?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/thuocbvtv.js"></script>
  </div>
</div>
