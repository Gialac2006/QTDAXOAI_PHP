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

$TABLE_GIONG = findTable($conn, ['giong_xoai','giongxoai']);
if (!$TABLE_GIONG) {
  echo "<h1>🥭 Giống xoài</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>giong_xoai</code> hoặc <code>giongxoai</code> trong CSDL <b>qlvtxoai</b>.</div>";
  return;
}

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

// XỬ LÝ CÁC THAO TÁC
switch ($action) {
  case 'delete':
    $MaGiong = trim($_GET['id'] ?? '');
    if ($MaGiong !== '') {
      $stmt = $conn->prepare("DELETE FROM `$TABLE_GIONG` WHERE MaGiong=?");
      $stmt->bind_param("s", $MaGiong);
      if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
      else $msg = "Đã xóa giống: $MaGiong";
      $stmt->close();
    }
    break;
    
  case 'edit':
    $MaGiong = trim($_GET['id'] ?? '');
    if ($MaGiong !== '') {
      $stmt = $conn->prepare("SELECT * FROM `$TABLE_GIONG` WHERE MaGiong=?");
      $stmt->bind_param("s", $MaGiong);
      $stmt->execute();
      $result = $stmt->get_result();
      $editData = $result->fetch_assoc();
      $stmt->close();
    }
    break;
}

// XỬ LÝ FORM (THÊM/SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenGiong'])) {
  $MaGiong = trim($_POST['MaGiong'] ?? '');
  $TenGiong = trim($_POST['TenGiong'] ?? '');
  $ThoiGianTruongThanh = trim($_POST['ThoiGianTruongThanh'] ?? '') ?: null;
  $NangSuatTrungBinh = trim($_POST['NangSuatTrungBinh'] ?? '') ?: null;
  $DacDiem = trim($_POST['DacDiem'] ?? '') ?: null;
  $TinhTrang = trim($_POST['TinhTrang'] ?? 'Còn sử dụng');
  $isEdit = !empty($MaGiong);

  // Validate
  if ($TenGiong === '') $err = 'Vui lòng nhập Tên giống';
  else {
    if ($isEdit) {
      // CẬP NHẬT
      $sql = "UPDATE `$TABLE_GIONG` SET TenGiong=?, ThoiGianTruongThanh=?, NangSuatTrungBinh=?, DacDiem=?, TinhTrang=? WHERE MaGiong=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssss", $TenGiong, $ThoiGianTruongThanh, $NangSuatTrungBinh, $DacDiem, $TinhTrang, $MaGiong);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else $msg = "Đã cập nhật giống: $TenGiong";
    } else {
      // THÊM MỚI (auto-generate MaGiong)
      do {
        $MaGiong = 'GO' . sprintf('%02d', rand(10, 99));
        $check = $conn->query("SELECT MaGiong FROM `$TABLE_GIONG` WHERE MaGiong='$MaGiong'");
      } while ($check && $check->num_rows > 0);
      
      $sql = "INSERT INTO `$TABLE_GIONG` (MaGiong, TenGiong, ThoiGianTruongThanh, NangSuatTrungBinh, DacDiem, TinhTrang) VALUES (?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssss", $MaGiong, $TenGiong, $ThoiGianTruongThanh, $NangSuatTrungBinh, $DacDiem, $TinhTrang);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm giống: $TenGiong (Mã: $MaGiong)";
    }
    $stmt->close();
    if (!$err) $editData = null; // Reset form sau khi lưu thành công
  }
}

// TÌM KIẾM
$search = trim($_GET['search'] ?? '');
$whereClause = '';
$params = [];
$types = '';

if ($search !== '') {
  $whereClause = "WHERE TenGiong LIKE ? OR MaGiong LIKE ? OR DacDiem LIKE ? OR TinhTrang LIKE ?";
  $searchTerm = "%$search%";
  $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
  $types = 'ssss';
}

// LẤY DANH SÁCH
$list = [];
$sql = "SELECT * FROM `$TABLE_GIONG` $whereClause ORDER BY TenGiong ASC LIMIT 200";

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

<h1> Quản lý Giống xoài</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- FORM THÊM/SỬA -->
<div class="card">
  <h3><?php echo $editData ? 'Chỉnh sửa giống xoài' : 'Thêm giống xoài mới'; ?></h3>
  <form method="post" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; padding:8px 0">
    
    <?php if ($editData): ?>
      <input type="hidden" name="MaGiong" value="<?php echo htmlspecialchars($editData['MaGiong']); ?>">
      <div style="grid-column:1/-1; background:#f5f5f5; padding:8px 12px; border-radius:4px">
        <strong>Mã giống:</strong> <?php echo htmlspecialchars($editData['MaGiong']); ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Tên giống *</strong></label>
      <input name="TenGiong" required value="<?php echo htmlspecialchars($editData['TenGiong'] ?? ''); ?>">
    </div>

    <div>
      <label><strong>Thời gian trưởng thành</strong></label>
      <input name="ThoiGianTruongThanh" value="<?php echo htmlspecialchars($editData['ThoiGianTruongThanh'] ?? ''); ?>" placeholder="VD: 12 tháng">
    </div>

    <div>
      <label><strong>Năng suất trung bình</strong></label>
      <input name="NangSuatTrungBinh" value="<?php echo htmlspecialchars($editData['NangSuatTrungBinh'] ?? ''); ?>" placeholder="VD: 100 kg">
    </div>

    <div>
      <label><strong>Tình trạng</strong></label>
      <select name="TinhTrang">
        <option value="Còn sử dụng" <?php echo ($editData['TinhTrang']??'Còn sử dụng') === 'Còn sử dụng' ? 'selected' : ''; ?>>Còn sử dụng</option>
        <option value="Tạm ngưng" <?php echo ($editData['TinhTrang']??'') === 'Tạm ngưng' ? 'selected' : ''; ?>>Tạm ngưng</option>
        <option value="Ngưng hoàn toàn" <?php echo ($editData['TinhTrang']??'') === 'Ngưng hoàn toàn' ? 'selected' : ''; ?>>Ngưng hoàn toàn</option>
      </select>
    </div>

    <div style="grid-column:1/-1">
      <label><strong>Đặc điểm</strong></label>
      <textarea name="DacDiem" style="width:100%; min-height:80px" placeholder="Mô tả đặc điểm của giống xoài..."><?php echo htmlspecialchars($editData['DacDiem'] ?? ''); ?></textarea>
    </div>

    <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px">
      <button type="submit" class="btn">
        <?php echo $editData ? 'Cập nhật' : 'Thêm mới'; ?>
      </button>
      <?php if ($editData): ?>
        <a href="index.php?p=giongxoai" class="btn" style="background:#999">Hủy</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TÌM KIẾM -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="giongxoai">
    <input name="search" placeholder="Tìm theo tên giống, mã giống, đặc điểm..." 
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:300px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?>
      <a href="index.php?p=giongxoai" class="btn" style="background:#999">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
    <h3>📋 Danh sách giống xoài</h3>
    <span class="muted">Tổng: <strong><?php echo count($list); ?></strong> giống</span>
  </div>
  
  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th>Mã giống</th>
          <th>Tên giống</th>
          <th>Thời gian trưởng thành</th>
          <th>Năng suất TB</th>
          <th>Đặc điểm</th>
          <th>Tình trạng</th>
          <th width="100">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="7" class="muted" style="text-align:center; padding:40px">
            <?php echo $search ? "Không tìm thấy kết quả cho: \"$search\"" : "Chưa có dữ liệu"; ?>
          </td></tr>
        <?php else: foreach($list as $r): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($r['MaGiong']); ?></strong></td>
            <td><?php echo htmlspecialchars($r['TenGiong']); ?></td>
            <td><?php echo htmlspecialchars($r['ThoiGianTruongThanh'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($r['NangSuatTrungBinh'] ?? '-'); ?></td>
            <td title="<?php echo htmlspecialchars($r['DacDiem'] ?? ''); ?>">
              <?php echo htmlspecialchars(mb_strimwidth($r['DacDiem'] ?? '-', 0, 50, '...')); ?>
            </td>
            <td>
              <span class="badge <?php 
                echo match($r['TinhTrang']) {
                  'Còn sử dụng' => 'badge-success',
                  'Tạm ngưng' => 'badge-warning', 
                  'Ngưng hoàn toàn' => 'badge-danger',
                  default => ''
                };
              ?>">
                <?php echo htmlspecialchars($r['TinhTrang']); ?>
              </span>
            </td>
            <td>
              <div style="display:flex; gap:2px">
                <a href="index.php?p=giongxoai&action=edit&id=<?php echo urlencode($r['MaGiong']); ?>"
                   class="btn" style="padding:2px 6px; font-size:11px" title="Chỉnh sửa">✏️</a>
                <a href="index.php?p=giongxoai&action=delete&id=<?php echo urlencode($r['MaGiong']); ?>"
                   class="btn danger" style="padding:2px 6px; font-size:11px" title="Xóa"
                   data-confirm="Xóa giống '<?php echo htmlspecialchars($r['TenGiong']); ?>' không thể khôi phục?">🗑️</a>
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
  --accent: #e9dfd5;      /* be điểm nhẹ */
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
}
.badge-success{
  background: #d4edda;
  color: #155724;
  border-color: #c3e6cb;
}
.badge-warning{
  background: #fff3cd;
  color: #856404;
  border-color: #ffeaa7;
}
.badge-danger{
  background: #f8d7da;
  color: #721c24;
  border-color: #f5c6cb;
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

/* Toolbar */
.toolbar{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
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