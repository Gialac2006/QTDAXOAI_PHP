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
$TABLE_USER = findTable($conn, ['nguoi_dung','nguoidung']);
if (!$TABLE_USER) {
  echo "<h1>Quản lý Người dùng</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>nguoi_dung</code>/<code>nguoidung</code> trong CSDL.</div>";
  return;
}

/* ===== Vai trò options ===== */
$vaiTroOptions = [];
$rs = $conn->query("SELECT DISTINCT VaiTro FROM `$TABLE_USER` WHERE VaiTro IS NOT NULL AND TRIM(VaiTro)<>''");
if ($rs) while ($r = $rs->fetch_assoc()) $vaiTroOptions[] = $r['VaiTro'];
if (!in_array('Admin', $vaiTroOptions, true)) $vaiTroOptions[] = 'Admin';
if (!in_array('User', $vaiTroOptions, true))  $vaiTroOptions[] = 'User';

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

/* ===== Actions ===== */
if ($action === 'delete') {
  $TenDangNhap = trim($_GET['id'] ?? '');
  if ($TenDangNhap !== '') {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_USER` WHERE TenDangNhap=?");
    $stmt->bind_param('s', $TenDangNhap);
    if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
    else $msg = "Đã xóa người dùng: $TenDangNhap";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $TenDangNhap = trim($_GET['id'] ?? '');
  if ($TenDangNhap !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$TABLE_USER` WHERE TenDangNhap=?");
    $stmt->bind_param('s', $TenDangNhap);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['HoTen'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']=='1';

  $TenDangNhap = trim($_POST['TenDangNhap'] ?? '');
  $TenDangNhapOriginal = trim($_POST['TenDangNhap_original'] ?? '');
  $MatKhau = trim($_POST['MatKhau'] ?? '');
  $HoTen  = trim($_POST['HoTen'] ?? '');
  $Email  = trim($_POST['Email'] ?? '') ?: null;
  $VaiTro = trim($_POST['VaiTro'] ?? '') ?: null;

  if ($HoTen === '') $err = 'Vui lòng nhập Họ tên';
  elseif (!$isEdit && $TenDangNhap === '') $err = 'Vui lòng nhập Tên đăng nhập';
  elseif (!$isEdit && $MatKhau === '') $err = 'Vui lòng nhập Mật khẩu';
  elseif ($Email && !filter_var($Email, FILTER_VALIDATE_EMAIL)) $err = 'Email không hợp lệ';
  else {
    if ($isEdit) {
      // Đổi khóa chính -> kiểm tra trùng
      if ($TenDangNhap !== $TenDangNhapOriginal) {
        $chk = $conn->prepare("SELECT 1 FROM `$TABLE_USER` WHERE TenDangNhap=?");
        $chk->bind_param('s', $TenDangNhap);
        $chk->execute();
        $dup = $chk->get_result()->num_rows > 0;
        $chk->close();
        if ($dup) $err = "Tên đăng nhập đã tồn tại";
      }
      if (!$err) {
        if ($MatKhau !== '') {
          $hashed = password_hash($MatKhau, PASSWORD_DEFAULT);
          $sql = "UPDATE `$TABLE_USER` SET TenDangNhap=?, MatKhau=?, HoTen=?, Email=?, VaiTro=? WHERE TenDangNhap=?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param('ssssss', $TenDangNhap, $hashed, $HoTen, $Email, $VaiTro, $TenDangNhapOriginal);
        } else {
          $sql = "UPDATE `$TABLE_USER` SET TenDangNhap=?, HoTen=?, Email=?, VaiTro=? WHERE TenDangNhap=?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param('sssss', $TenDangNhap, $HoTen, $Email, $VaiTro, $TenDangNhapOriginal);
        }
        if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
        else { $msg = "Đã cập nhật người dùng: $HoTen"; $editData = null; }
        $stmt->close();
      }
    } else {
      // Thêm mới
      $chk = $conn->prepare("SELECT 1 FROM `$TABLE_USER` WHERE TenDangNhap=?");
      $chk->bind_param('s', $TenDangNhap);
      $chk->execute();
      if ($chk->get_result()->num_rows > 0) $err = "Tên đăng nhập đã tồn tại";
      $chk->close();

      if (!$err) {
        $hashed = password_hash($MatKhau, PASSWORD_DEFAULT);
        $sql = "INSERT INTO `$TABLE_USER` (TenDangNhap, MatKhau, HoTen, Email, VaiTro) VALUES (?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $TenDangNhap, $hashed, $HoTen, $Email, $VaiTro);
        if (!$stmt->execute()) {
          if ($conn->errno == 1062) $err = "Tên đăng nhập hoặc Email đã tồn tại";
          else $err = "Không thêm được: ".$stmt->error;
        } else $msg = "Đã thêm người dùng: $HoTen (Tên đăng nhập: $TenDangNhap)";
        $stmt->close();
      }
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where = ''; $types=''; $params=[];
if ($search !== '') {
  $where = "WHERE TenDangNhap LIKE ? OR HoTen LIKE ? OR Email LIKE ? OR VaiTro LIKE ?";
  $kw = "%$search%"; $types='ssss'; $params = [$kw,$kw,$kw,$kw];
}
$sql = "SELECT * FROM `$TABLE_USER` $where ORDER BY VaiTro ASC, HoTen ASC LIMIT 300";
if ($params) { $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $list=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Người dùng</h1>
<link rel="stylesheet" href="pages/layout/assets/css/nguoidung.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $editData ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="TenDangNhap_original" value="<?= htmlspecialchars($editData['TenDangNhap']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Tên đăng nhập cũ:</strong> <?= htmlspecialchars($editData['TenDangNhap']) ?>
      </div>
    <?php endif; ?>

    <?php if (!$editData): ?>
      <div class="col">
        <label><strong>Tên đăng nhập *</strong></label>
        <input name="TenDangNhap" required>
      </div>
    <?php else: ?>
      <div class="col">
        <label><strong>Tên đăng nhập (có thể đổi)</strong></label>
        <input name="TenDangNhap" value="<?= htmlspecialchars($editData['TenDangNhap']) ?>">
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Họ tên *</strong></label>
      <input name="HoTen" required value="<?= htmlspecialchars($editData['HoTen'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Email</strong></label>
      <input type="email" name="Email" value="<?= htmlspecialchars($editData['Email'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Vai trò</strong></label>
      <?php $vtSel = $editData['VaiTro'] ?? ''; ?>
      <select name="VaiTro">
        <option value="">-- Chọn vai trò --</option>
        <?php foreach ($vaiTroOptions as $vt): ?>
          <option value="<?= htmlspecialchars($vt) ?>" <?= $vtSel===$vt?'selected':'' ?>><?= htmlspecialchars($vt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Mật khẩu <?= $editData ? '(để trống nếu không đổi)' : '*' ?></strong></label>
      <input type="password" name="MatKhau" <?= $editData ? '' : 'required' ?>>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($editData): ?><a class="btn secondary" href="index.php?p=nguoidung">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SEARCH -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="nguoidung">
    <input name="search" placeholder="Tìm theo tên đăng nhập, họ tên, email, vai trò..." value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a href="index.php?p=nguoidung" class="btn secondary">Xóa lọc</a><?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách người dùng</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> người dùng</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tên đăng nhập</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>Vai trò</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="5" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['TenDangNhap']) ?></strong></td>
          <td><?= htmlspecialchars($r['HoTen']) ?></td>
          <td><?= htmlspecialchars($r['Email'] ?? '-') ?></td>
          <td>
            <?php $vt = $r['VaiTro'] ?? ''; ?>
            <?php if ($vt): ?>
              <span class="badge <?= strtolower($vt)==='admin'?'admin':'user' ?>"><?= htmlspecialchars($vt) ?></span>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=nguoidung&action=edit&id=<?= urlencode($r['TenDangNhap']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=nguoidung&action=delete&id=<?= urlencode($r['TenDangNhap']) ?>"
               title="Xóa" data-confirm="Xóa người dùng '<?= htmlspecialchars($r['HoTen']) ?>' không thể khôi phục?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/nguoidung.js"></script>
  </div>
</div>
