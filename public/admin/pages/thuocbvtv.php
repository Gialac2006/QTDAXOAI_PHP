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

$TABLE_THUOC = findTable($conn, ['thuoc_bvtv','thuocbvtv']);
if (!$TABLE_THUOC) {
  echo "<h1>🧪 Thuốc Bảo Vệ Thực Vật</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>thuoc_bvtv</code> hoặc <code>thuocbvtv</code> trong CSDL <b>qlvtxoai</b>.</div>";
  return;
}

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

// XỬ LÝ CÁC THAO TÁC
switch ($action) {
  case 'delete':
    $TenThuoc = trim($_GET['id'] ?? '');
    if ($TenThuoc !== '') {
      $stmt = $conn->prepare("DELETE FROM `$TABLE_THUOC` WHERE TenThuoc=?");
      $stmt->bind_param("s", $TenThuoc);
      if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
      else $msg = "Đã xóa thuốc: $TenThuoc";
      $stmt->close();
    }
    break;
    
  case 'edit':
    $TenThuoc = trim($_GET['id'] ?? '');
    if ($TenThuoc !== '') {
      $stmt = $conn->prepare("SELECT * FROM `$TABLE_THUOC` WHERE TenThuoc=?");
      $stmt->bind_param("s", $TenThuoc);
      $stmt->execute();
      $result = $stmt->get_result();
      $editData = $result->fetch_assoc();
      $stmt->close();
    }
    break;
}

// XỬ LÝ FORM (THÊM/SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenThuoc'])) {
  $TenThuoc = trim($_POST['TenThuoc'] ?? '');
  $HoatChat = trim($_POST['HoatChat'] ?? '') ?: null;
  $DonViTinh = trim($_POST['DonViTinh'] ?? '') ?: null;
  $GhiChu = trim($_POST['GhiChu'] ?? '') ?: null;
  $isEdit = !empty($_POST['isEdit']);

  // Validate
  if ($TenThuoc === '') $err = 'Vui lòng nhập Tên thuốc';
  else {
    if ($isEdit) {
      // CẬP NHẬT
      $TenThuoc_original = $_POST['TenThuoc_original']; // Lấy tên thuốc gốc
      $sql = "UPDATE `$TABLE_THUOC` SET TenThuoc=?, HoatChat=?, DonViTinh=?, GhiChu=? WHERE TenThuoc=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssss", $TenThuoc, $HoatChat, $DonViTinh, $GhiChu, $TenThuoc_original);
      
      if (!$stmt->execute()) {
        if ($conn->errno == 1062) { // Duplicate entry
          $err = "Tên thuốc đã tồn tại";
        } else {
          $err = "Không cập nhật được: ".$stmt->error;
        }
      } else {
        $msg = "Đã cập nhật thuốc: $TenThuoc";
      }
    } else {
      // THÊM MỚI
      // Kiểm tra tên thuốc đã tồn tại
      $checkStmt = $conn->prepare("SELECT TenThuoc FROM `$TABLE_THUOC` WHERE TenThuoc=?");
      $checkStmt->bind_param("s", $TenThuoc);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      
      if ($checkResult->num_rows > 0) {
        $err = "Tên thuốc đã tồn tại";
      } else {
        $sql = "INSERT INTO `$TABLE_THUOC` (TenThuoc, HoatChat, DonViTinh, GhiChu) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $TenThuoc, $HoatChat, $DonViTinh, $GhiChu);
        
        if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
        else $msg = "Đã thêm thuốc: $TenThuoc";
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
  $whereClause = "WHERE TenThuoc LIKE ? OR HoatChat LIKE ? OR DonViTinh LIKE ? OR GhiChu LIKE ?";
  $searchTerm = "%$search%";
  $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
  $types = 'ssss';
}

// LẤY DANH SÁCH
$list = [];
$sql = "SELECT * FROM `$TABLE_THUOC` $whereClause ORDER BY TenThuoc ASC LIMIT 200";

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

// Lấy danh sách đơn vị tính phổ biến
$donViTinhOptions = ['kg', 'gam', 'lít', 'ml', 'gói', 'chai', 'hộp', 'tuýp'];
?>

<h1>Quản lý Thuốc Bảo Vệ Thực Vật</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- FORM THÊM/SỬA -->
<div class="card">
  <h3><?php echo $editData ? 'Chỉnh sửa thuốc BVTV' : 'Thêm thuốc BVTV mới'; ?></h3>
  <form method="post" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; padding:8px 0">
    
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="TenThuoc_original" value="<?php echo htmlspecialchars($editData['TenThuoc']); ?>">
    <?php endif; ?>

    <div>
      <label><strong>Tên thuốc *</strong></label>
      <input name="TenThuoc" required value="<?php echo htmlspecialchars($editData['TenThuoc'] ?? $_POST['TenThuoc'] ?? ''); ?>" placeholder="Nhập tên thuốc BVTV">
    </div>

    <div>
      <label><strong>Hoạt chất</strong></label>
      <input name="HoatChat" value="<?php echo htmlspecialchars($editData['HoatChat'] ?? $_POST['HoatChat'] ?? ''); ?>" placeholder="Nhập hoạt chất chính">
    </div>

    <div>
      <label><strong>Đơn vị tính</strong></label>
      <select name="DonViTinh">
        <option value="">-- Chọn đơn vị tính --</option>
        <?php foreach ($donViTinhOptions as $dv): ?>
          <option value="<?php echo htmlspecialchars($dv); ?>" 
            <?php echo ($editData['DonViTinh'] ?? $_POST['DonViTinh'] ?? '') === $dv ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($dv); ?>
          </option>
        <?php endforeach; ?>
        <option value="other">Khác...</option>
      </select>
    </div>

    <div id="customUnit" style="display: none;">
      <label><strong>Đơn vị tính khác</strong></label>
      <input name="DonViTinhKhac" placeholder="Nhập đơn vị tính khác">
    </div>

    <div style="grid-column:1/-1">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" rows="4" placeholder="Nhập thông tin bổ sung về thuốc (công dụng, cách sử dụng, lưu ý...)"><?php echo htmlspecialchars($editData['GhiChu'] ?? $_POST['GhiChu'] ?? ''); ?></textarea>
    </div>

    <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px">
      <button type="submit" class="btn">
        <?php echo $editData ? 'Cập nhật' : 'Thêm mới'; ?>
      </button>
      <?php if ($editData): ?>
        <a href="index.php?p=thuocbvtv" class="btn" style="background:#999">Hủy</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TÌM KIẾM -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="thuocbvtv">
    <input name="search" placeholder="Tìm theo tên thuốc, hoạt chất, đơn vị tính, ghi chú..." 
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:350px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?>
      <a href="index.php?p=thuocbvtv" class="btn" style="background:#999">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
    <h3>📋 Danh sách thuốc BVTV</h3>
    <span class="muted">Tổng: <strong><?php echo count($list); ?></strong> loại thuốc</span>
  </div>
  
  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th>Tên thuốc</th>
          <th>Hoạt chất</th>
          <th>Đơn vị tính</th>
          <th>Ghi chú</th>
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
            <td><strong><?php echo htmlspecialchars($r['TenThuoc']); ?></strong></td>
            <td><?php echo htmlspecialchars($r['HoatChat'] ?? '-'); ?></td>
            <td style="text-align:center">
              <?php if ($r['DonViTinh']): ?>
                <span class="badge" style="background: #e3f2fd; color: #1976d2;">
                  <?php echo htmlspecialchars($r['DonViTinh']); ?>
                </span>
              <?php else: ?>
                <span class="muted">-</span>
              <?php endif; ?>
            </td>
            <td title="<?php echo htmlspecialchars($r['GhiChu'] ?? ''); ?>">
              <?php echo $r['GhiChu'] ? htmlspecialchars(mb_strimwidth($r['GhiChu'], 0, 50, '...')) : '-'; ?>
            </td>
            <td>
              <div style="display:flex; gap:2px">
                <a href="index.php?p=thuocbvtv&action=edit&id=<?php echo urlencode($r['TenThuoc']); ?>"
                   class="btn" style="padding:2px 6px; font-size:11px" title="Chỉnh sửa">✏️</a>
                <a href="index.php?p=thuocbvtv&action=delete&id=<?php echo urlencode($r['TenThuoc']); ?>"
                   class="btn danger" style="padding:2px 6px; font-size:11px" title="Xóa"
                   data-confirm="Xóa thuốc '<?php echo htmlspecialchars($r['TenThuoc']); ?>' không thể khôi phục?">🗑️</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- THỐNG KÊ NHANH -->
<div class="card">
  <h3>📊 Thống kê nhanh</h3>
  <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px">
    <?php
    // Thống kê theo đơn vị tính
    $statsQuery = $conn->query("SELECT DonViTinh, COUNT(*) as SoLuong FROM `$TABLE_THUOC` WHERE DonViTinh IS NOT NULL AND DonViTinh != '' GROUP BY DonViTinh ORDER BY SoLuong DESC LIMIT 5");
    if ($statsQuery && $statsQuery->num_rows > 0):
    ?>
      <div>
        <h4>Theo đơn vị tính</h4>
        <?php while($stat = $statsQuery->fetch_assoc()): ?>
          <div style="display:flex; justify-content:space-between; align-items:center; margin:4px 0">
            <span class="badge"><?php echo htmlspecialchars($stat['DonViTinh']); ?></span>
            <strong><?php echo $stat['SoLuong']; ?> loại</strong>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
    
    <div>
      <h4>Tổng quan</h4>
      <div style="display:flex; justify-content:space-between; align-items:center; margin:4px 0">
        <span>Tổng số thuốc:</span>
        <strong style="color: var(--primary)"><?php echo count($list); ?></strong>
      </div>
      <?php
      $coHoatChatQuery = $conn->query("SELECT COUNT(*) as SoLuong FROM `$TABLE_THUOC` WHERE HoatChat IS NOT NULL AND HoatChat != ''");
      $coHoatChat = $coHoatChatQuery ? $coHoatChatQuery->fetch_assoc()['SoLuong'] : 0;
      ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin:4px 0">
        <span>Có hoạt chất:</span>
        <strong style="color: #4caf50"><?php echo $coHoatChat; ?></strong>
      </div>
      <?php
      $coGhiChuQuery = $conn->query("SELECT COUNT(*) as SoLuong FROM `$TABLE_THUOC` WHERE GhiChu IS NOT NULL AND GhiChu != ''");
      $coGhiChu = $coGhiChuQuery ? $coGhiChuQuery->fetch_assoc()['SoLuong'] : 0;
      ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin:4px 0">
        <span>Có ghi chú:</span>
        <strong style="color: #ff9800"><?php echo $coGhiChu; ?></strong>
      </div>
    </div>
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
.card h4{
  margin: 0 0 8px;
  font-size: 14px;
  color: var(--primary);
  font-weight: 600;
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

// Xử lý đơn vị tính khác
document.addEventListener('DOMContentLoaded', function() {
  const selectDonVi = document.querySelector('select[name="DonViTinh"]');
  const customUnitDiv = document.getElementById('customUnit');
  const customUnitInput = document.querySelector('input[name="DonViTinhKhac"]');

  if (selectDonVi && customUnitDiv && customUnitInput) {
    selectDonVi.addEventListener('change', function() {
      if (this.value === 'other') {
        customUnitDiv.style.display = 'block';
        customUnitInput.required = true;
      } else {
        customUnitDiv.style.display = 'none';
        customUnitInput.required = false;
        customUnitInput.value = '';
      }
    });

    // Xử lý khi submit form
    const form = selectDonVi.closest('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        if (selectDonVi.value === 'other' && customUnitInput.value.trim()) {
          // Thay thế giá trị select bằng giá trị custom
          selectDonVi.value = customUnitInput.value.trim();
        }
      });
    }
  }
});
</script>