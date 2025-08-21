<?php
require __DIR__ . '/../../connect.php'; // $conn
?>

<?php
// Tìm bảng tồn tại
function findTable(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) return $t;
  }
  return null;
}

$TABLE_USER = findTable($conn, ['nguoi_dung','nguoidung']);
if (!$TABLE_USER) {
  echo "<h1>👥 Người dùng</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>nguoi_dung</code> hoặc <code>nguoidung</code> trong CSDL <b>qlvtxoai</b>.</div>";
  return;
}

// Lấy danh sách vai trò
$vaiTroOptions = [];
$vaiTroQuery = $conn->query("SELECT DISTINCT VaiTro FROM `$TABLE_USER` WHERE VaiTro IS NOT NULL");
if ($vaiTroQuery) {
  while ($row = $vaiTroQuery->fetch_assoc()) {
    if (!empty($row['VaiTro'])) {
      $vaiTroOptions[] = $row['VaiTro'];
    }
  }
}
// Thêm các vai trò mặc định nếu chưa có
if (!in_array('Admin', $vaiTroOptions)) $vaiTroOptions[] = 'Admin';
if (!in_array('User', $vaiTroOptions)) $vaiTroOptions[] = 'User';

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

// XỬ LÝ CÁC THAO TÁC
switch ($action) {
  case 'delete':
    $TenDangNhap = trim($_GET['id'] ?? '');
    if ($TenDangNhap !== '') {
      $stmt = $conn->prepare("DELETE FROM `$TABLE_USER` WHERE TenDangNhap=?");
      $stmt->bind_param("s", $TenDangNhap);
      if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
      else $msg = "Đã xóa người dùng: $TenDangNhap";
      $stmt->close();
    }
    break;
    
  case 'edit':
    $TenDangNhap = trim($_GET['id'] ?? '');
    if ($TenDangNhap !== '') {
      $stmt = $conn->prepare("SELECT * FROM `$TABLE_USER` WHERE TenDangNhap=?");
      $stmt->bind_param("s", $TenDangNhap);
      $stmt->execute();
      $result = $stmt->get_result();
      $editData = $result->fetch_assoc();
      $stmt->close();
    }
    break;
}

// XỬ LÝ FORM (THÊM/SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['HoTen'])) {
  $TenDangNhap = trim($_POST['TenDangNhap'] ?? '');
  $MatKhau = trim($_POST['MatKhau'] ?? '');
  $HoTen = trim($_POST['HoTen'] ?? '');
  $Email = trim($_POST['Email'] ?? '') ?: null;
  $VaiTro = trim($_POST['VaiTro'] ?? '') ?: null;
  $isEdit = !empty($_POST['isEdit']);

  // Validate
  if ($HoTen === '') $err = 'Vui lòng nhập Họ tên';
  elseif (!$isEdit && $TenDangNhap === '') $err = 'Vui lòng nhập Tên đăng nhập';
  elseif (!$isEdit && $MatKhau === '') $err = 'Vui lòng nhập Mật khẩu';
  elseif ($Email && !filter_var($Email, FILTER_VALIDATE_EMAIL)) $err = 'Email không hợp lệ';
  else {
    if ($isEdit) {
      // CẬP NHẬT
      $TenDangNhap = $_POST['TenDangNhap_original']; // Lấy tên đăng nhập gốc
      
      if ($MatKhau) {
        // Có thay đổi mật khẩu
        $hashedPassword = password_hash($MatKhau, PASSWORD_DEFAULT);
        $sql = "UPDATE `$TABLE_USER` SET MatKhau=?, HoTen=?, Email=?, VaiTro=? WHERE TenDangNhap=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $hashedPassword, $HoTen, $Email, $VaiTro, $TenDangNhap);
      } else {
        // Không thay đổi mật khẩu
        $sql = "UPDATE `$TABLE_USER` SET HoTen=?, Email=?, VaiTro=? WHERE TenDangNhap=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $HoTen, $Email, $VaiTro, $TenDangNhap);
      }
      
      if (!$stmt->execute()) {
        if ($conn->errno == 1062) { // Duplicate entry
          $err = "Email đã được sử dụng bởi người dùng khác";
        } else {
          $err = "Không cập nhật được: ".$stmt->error;
        }
      } else {
        $msg = "Đã cập nhật người dùng: $HoTen";
      }
    } else {
      // THÊM MỚI
      // Kiểm tra tên đăng nhập đã tồn tại
      $checkStmt = $conn->prepare("SELECT TenDangNhap FROM `$TABLE_USER` WHERE TenDangNhap=?");
      $checkStmt->bind_param("s", $TenDangNhap);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      
      if ($checkResult->num_rows > 0) {
        $err = "Tên đăng nhập đã tồn tại";
      } else {
        $hashedPassword = password_hash($MatKhau, PASSWORD_DEFAULT);
        $sql = "INSERT INTO `$TABLE_USER` (TenDangNhap, MatKhau, HoTen, Email, VaiTro) VALUES (?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $TenDangNhap, $hashedPassword, $HoTen, $Email, $VaiTro);
        
        if (!$stmt->execute()) {
          if ($conn->errno == 1062) { // Duplicate entry
            $err = "Tên đăng nhập hoặc Email đã tồn tại";
          } else {
            $err = "Không thêm được: ".$stmt->error;
          }
        } else {
          $msg = "Đã thêm người dùng: $HoTen (Tên đăng nhập: $TenDangNhap)";
        }
      }
      $checkStmt->close();
    }
    if (isset($stmt)) $stmt->close();
    if (!$err) $editData = null; // Reset form sau khi lưu thành công
  }
}

// TÌM KIẾM
$search = trim($_GET['search'] ?? '');
$whereClause = '';
$params = [];
$types = '';

if ($search !== '') {
  $whereClause = "WHERE TenDangNhap LIKE ? OR HoTen LIKE ? OR Email LIKE ? OR VaiTro LIKE ?";
  $searchTerm = "%$search%";
  $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
  $types = 'ssss';
}

// LẤY DANH SÁCH
$list = [];
$sql = "SELECT * FROM `$TABLE_USER` $whereClause ORDER BY VaiTro ASC, HoTen ASC LIMIT 200";

if ($params) {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
  $list = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  $q = $conn->query($sql);
  if ($q) $list = $q->fetch_all(MYSQLI_ASSOC);
}
?>

<h1>Quản lý Người dùng</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- FORM THÊM/SỬA -->
<div class="card">
  <h3><?php echo $editData ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới'; ?></h3>
  <form method="post" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; padding:8px 0">
    
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="TenDangNhap_original" value="<?php echo htmlspecialchars($editData['TenDangNhap']); ?>">
      <div style="grid-column:1/-1; background:#f5f5f5; padding:8px 12px; border-radius:4px">
        <strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($editData['TenDangNhap']); ?>
      </div>
    <?php else: ?>
      <div>
        <label><strong>Tên đăng nhập *</strong></label>
        <input name="TenDangNhap" required value="<?php echo htmlspecialchars($_POST['TenDangNhap'] ?? ''); ?>">
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Họ tên *</strong></label>
      <input name="HoTen" required value="<?php echo htmlspecialchars($editData['HoTen'] ?? $_POST['HoTen'] ?? ''); ?>">
    </div>

    <div>
      <label><strong>Email</strong></label>
      <input type="email" name="Email" value="<?php echo htmlspecialchars($editData['Email'] ?? $_POST['Email'] ?? ''); ?>">
    </div>

    <div>
      <label><strong>Vai trò</strong></label>
      <select name="VaiTro">
        <option value="">-- Chọn vai trò --</option>
        <?php foreach ($vaiTroOptions as $vt): ?>
          <option value="<?php echo htmlspecialchars($vt); ?>" 
            <?php echo ($editData['VaiTro'] ?? $_POST['VaiTro'] ?? '') === $vt ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($vt); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="<?php echo $editData ? 'grid-column:1/-1' : ''; ?>">
      <label><strong>Mật khẩu <?php echo $editData ? '(để trống nếu không đổi)' : '*'; ?></strong></label>
      <input type="password" name="MatKhau" <?php echo $editData ? '' : 'required'; ?>>
    </div>

    <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px">
      <button type="submit" class="btn">
        <?php echo $editData ? 'Cập nhật' : 'Thêm mới'; ?>
      </button>
      <?php if ($editData): ?>
        <a href="index.php?p=nguoidung" class="btn" style="background:#999">Hủy</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TÌM KIẾM -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="nguoidung">
    <input name="search" placeholder="Tìm theo tên đăng nhập, họ tên, email, vai trò..." 
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:300px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?>
      <a href="index.php?p=nguoidung" class="btn" style="background:#999">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
    <h3>📋 Danh sách người dùng</h3>
    <span class="muted">Tổng: <strong><?php echo count($list); ?></strong> người dùng</span>
  </div>
  
  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th>Tên đăng nhập</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>Vai trò</th>
          <th width="100">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="5" class="muted" style="text-align:center; padding:40px">
            <?php echo $search ? "Không tìm thấy kết quả cho: \"$search\"" : "Chưa có dữ liệu"; ?>
          </td></tr>
        <?php else: foreach($list as $r): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($r['TenDangNhap']); ?></strong></td>
            <td><?php echo htmlspecialchars($r['HoTen']); ?></td>
            <td><?php echo htmlspecialchars($r['Email'] ?? '-'); ?></td>
            <td>
              <?php if ($r['VaiTro']): ?>
                <span class="badge" style="background: <?php echo $r['VaiTro'] === 'Admin' ? '#ffe4e1' : '#e9f3ff'; ?>; 
                      color: <?php echo $r['VaiTro'] === 'Admin' ? '#d63031' : '#0984e3'; ?>">
                  <?php echo htmlspecialchars($r['VaiTro']); ?>
                </span>
              <?php else: ?>
                <span class="muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex; gap:2px">
                <a href="index.php?p=nguoidung&action=edit&id=<?php echo urlencode($r['TenDangNhap']); ?>"
                   class="btn" style="padding:2px 6px; font-size:11px" title="Chỉnh sửa">✏️</a>
                <a href="index.php?p=nguoidung&action=delete&id=<?php echo urlencode($r['TenDangNhap']); ?>"
                   class="btn danger" style="padding:2px 6px; font-size:11px" title="Xóa"
                   data-confirm="Xóa người dùng '<?php echo htmlspecialchars($r['HoTen']); ?>' không thể khôi phục?">🗑️</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
/* ===== Palette & base ===== */
:root{
  --bg: #f7f3ee;          /* nền be nhạt */
  --card: #ffffff;        /* nền thẻ */
  --text: #2b2b2b;        /* màu chữ chính */
  --muted: #6b6b6b;       /* chữ phụ */
  --line: #ece7e1;        /* viền mảnh */
  --primary: #7f6a55;     /* nâu be sang */
  --primary-2: #a0896f;   /* nâu nhạt hover */
  --accent: #e9dfd5;      /* be điềm nhẹ */
  --shadow: 0 6px 18px rgba(34, 25, 16, .06);
  --radius: 14px;
  --radius-sm: 10px;
}

/* Reset nhẹ nhàng */
*{ box-sizing: border-box; }
html, body{ height: 100%; }
body{
  margin: 0;
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
  color: var(--text);
  background: radial-gradient(1200px 800px at 10% 0%, #fbf8f5 0%, var(--bg) 60%), var(--bg);
  line-height: 1.5;
}

/* Khối trang chung (nếu có .container) */
.container{
  max-width: 1080px;
  margin: 28px auto;
  padding: 0 16px;
}

/* Tiêu đề trang */
.container > h2{
  font-size: 26px;
  letter-spacing: .2px;
  margin: 0 0 16px;
  font-weight: 700;
  color: var(--primary);
}

/* Hàng (row) tiện căn chỉnh nhanh */
.row{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  margin: 10px 0;
}

/* ===== Card ===== */
.card{
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  padding: 16px;
  box-shadow: var(--shadow);
  margin-bottom: 16px;     /* gọn gàng hơn 20px */
}
.card h3{
  margin: 0 0 10px;
  font-size: 18px;
  color: var(--primary);
  font-weight: 700;
}

/* ===== Form trong card ===== */
.card form label{
  display: block;
  margin: 4px 0 6px;
  font-size: 13px;
  color: var(--muted);
}
.card form input,
.card form select,
.card form textarea{
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: #fff;
  outline: none;
  transition: border-color .2s, box-shadow .2s, background .2s;
  font-size: 14px;
}
.card form textarea{ min-height: 90px; resize: vertical; }
.card form input:focus,
.card form select:focus,
.card form textarea:focus{
  border-color: var(--primary-2);
  box-shadow: 0 0 0 4px rgba(160, 137, 111, .12);
  background: #fff;
}
.card form .form-row{
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 10px;
}
.card form .col-6{ grid-column: span 6; }
.card form .col-4{ grid-column: span 4; }
.card form .col-3{ grid-column: span 3; }
@media (max-width: 720px){
  .card form .col-6, .card form .col-4, .card form .col-3{ grid-column: 1 / -1; }
}

/* ===== Toolbar ===== */
.toolbar{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

/* ===== Nút ===== */
.btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;           /* cân đối hơn */
  border-radius: 999px;
  border: 1px solid transparent;
  background: var(--primary);
  color: #fff;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: transform .06s ease, background .2s ease, box-shadow .2s ease, opacity .2s ease;
  box-shadow: 0 6px 14px rgba(127,106,85,.16);
  text-decoration: none;
}
.btn:hover{ background: var(--primary-2); }
.btn:active{ transform: translateY(1px); }
.btn-secondary{
  background: #fff;
  color: var(--primary);
  border-color: var(--line);
  box-shadow: none;
}
.btn-secondary:hover{
  background: var(--accent);
  border-color: var(--accent);
}
.btn.danger{
  background: #dc3545;
}
.btn.danger:hover{
  background: #c82333;
}

/* Nhãn/huy hiệu nhỏ */
.badge{
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: var(--accent);
  color: var(--primary);
  font-size: 12px;
  border: 1px solid var(--line);
  font-weight: 600;
}

/* ===== Ô tìm kiếm nhanh ===== */
#q{
  border-radius: 999px;
  padding: 10px 14px;
  border: 1px solid var(--line);
  background: #fff;
  outline: none;
  flex: 1;
  min-width: 220px;
}
#q:focus{
  border-color: var(--primary-2);
  box-shadow: 0 0 0 4px rgba(160,137,111,.12);
}

/* ===== Bảng dữ liệu ===== */
.table-wrap{
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
}
table{
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
table th, table td{
  padding: 10px 12px;                 /* giảm chút cho gọn */
  border-bottom: 1px solid var(--line);
}
table th{
  background: linear-gradient(180deg, #fbf8f5 0%, #f4eee7 100%); /* be rất nhẹ */
  text-align: left;
  font-weight: 700;
  color: var(--primary);
}
table tr:hover td{
  background: #faf6f1;
}
table td.actions{
  white-space: nowrap;
  display: flex;
  gap: 8px;
}

/* ===== Thông báo ngắn ===== */
.msg{
  margin: 8px 0;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
  background: #edf7ef;
  border-color: #cfe7d2;
  color: #216c35;
}
.msg.err{
  background: #fff1f0;
  border-color: #ffd6d2;
  color: #b42318;
}

/* ===== Tiện ích khoảng cách ===== */
.mt-0{ margin-top: 0 !important; }
.mt-8{ margin-top: 8px !important; }
.mt-12{ margin-top: 12px !important; }
.mb-0{ margin-bottom: 0 !important; }
.mb-8{ margin-bottom: 8px !important; }
.mb-12{ margin-bottom: 12px !important; }
.muted{ color: var(--muted); }

/* ===== Cuộn nhẹ nhàng (nếu có anchor) ===== */
html{ scroll-behavior: smooth; }
.container{
  width: 80%;          /* chiếm 95% màn hình */
  max-width: 1600px;   /* không vượt quá 1600px */
  margin: 32px auto;
  padding: 0 28px;
}


</style>

<script>
// Xác nhận xóa
document.addEventListener('click', function(e) {
  if (e.target.hasAttribute('data-confirm')) {
    if (!confirm(e.target.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  }
});
</script>