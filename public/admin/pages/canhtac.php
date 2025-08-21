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

$TABLE_CANH_TAC = findTable($conn, ['canh_tac','canhtac']);
$TABLE_VUNG = findTable($conn, ['vung_trong','vungtrong']);
$TABLE_PHAN_BON = findTable($conn, ['phan_bon','phanbon']);

if (!$TABLE_CANH_TAC) {
  echo "<h1>🌱 Canh tác</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>canh_tac</code> hoặc <code>canhtac</code> trong CSDL <b>qlvtxoai</b>.</div>";
  return;
}

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

// Lấy danh sách vùng trồng và phân bón cho dropdown
$listVung = [];
$listPhanBon = [];

if ($TABLE_VUNG) {
  $q = $conn->query("SELECT MaVung, TenVung FROM `$TABLE_VUNG` ORDER BY TenVung");
  if ($q) $listVung = $q->fetch_all(MYSQLI_ASSOC);
}

if ($TABLE_PHAN_BON) {
  $q = $conn->query("SELECT TenPhanBon FROM `$TABLE_PHAN_BON` ORDER BY TenPhanBon");
  if ($q) $listPhanBon = $q->fetch_all(MYSQLI_ASSOC);
}

// XỬ LÝ CÁC THAO TÁC
switch ($action) {
  case 'delete':
    $ID = intval($_GET['id'] ?? 0);
    if ($ID > 0) {
      $stmt = $conn->prepare("DELETE FROM `$TABLE_CANH_TAC` WHERE ID=?");
      $stmt->bind_param("i", $ID);
      if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
      else $msg = "Đã xóa hoạt động canh tác ID: $ID";
      $stmt->close();
    }
    break;
    
  case 'edit':
    $ID = intval($_GET['id'] ?? 0);
    if ($ID > 0) {
      $stmt = $conn->prepare("SELECT * FROM `$TABLE_CANH_TAC` WHERE ID=?");
      $stmt->bind_param("i", $ID);
      $stmt->execute();
      $result = $stmt->get_result();
      $editData = $result->fetch_assoc();
      $stmt->close();
    }
    break;
}

// XỬ LÝ FORM (THÊM/SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['LoaiCongViec'])) {
  $ID = intval($_POST['ID'] ?? 0);
  $NgayThucHien = trim($_POST['NgayThucHien'] ?? '') ?: null;
  $LoaiCongViec = trim($_POST['LoaiCongViec'] ?? '');
  $NguoiThucHien = trim($_POST['NguoiThucHien'] ?? '') ?: null;
  $MaVung = trim($_POST['MaVung'] ?? '') ?: null;
  $TenPhanBon = trim($_POST['TenPhanBon'] ?? '') ?: null;
  $LieuLuong = floatval($_POST['LieuLuong'] ?? 0) ?: null;
  $GhiChu = trim($_POST['GhiChu'] ?? '') ?: null;
  $isEdit = $ID > 0;

  // Validate
  if ($LoaiCongViec === '') $err = 'Vui lòng nhập Loại công việc';
  else {
    if ($isEdit) {
      // CẬP NHẬT
      $sql = "UPDATE `$TABLE_CANH_TAC` SET NgayThucHien=?, LoaiCongViec=?, NguoiThucHien=?, MaVung=?, TenPhanBon=?, LieuLuong=?, GhiChu=? WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssssd
", $NgayThucHien, $LoaiCongViec, $NguoiThucHien, $MaVung, $TenPhanBon, $LieuLuong, $GhiChu, $ID);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else $msg = "Đã cập nhật hoạt động canh tác: $LoaiCongViec";
    } else {
      // THÊM MỚI
      $sql = "INSERT INTO `$TABLE_CANH_TAC` (NgayThucHien, LoaiCongViec, NguoiThucHien, MaVung, TenPhanBon, LieuLuong, GhiChu) VALUES (?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssssds", $NgayThucHien, $LoaiCongViec, $NguoiThucHien, $MaVung, $TenPhanBon, $LieuLuong, $GhiChu);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm hoạt động canh tác: $LoaiCongViec";
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
  $whereClause = "WHERE c.LoaiCongViec LIKE ? OR c.NguoiThucHien LIKE ? OR c.MaVung LIKE ? OR c.TenPhanBon LIKE ? OR c.GhiChu LIKE ? OR v.TenVung LIKE ?";
  $searchTerm = "%$search%";
  $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
  $types = 'ssssss';
}

// LẤY DANH SÁCH VỚI JOIN
$list = [];
$sql = "SELECT c.*, v.TenVung 
        FROM `$TABLE_CANH_TAC` c 
        LEFT JOIN `$TABLE_VUNG` v ON c.MaVung = v.MaVung 
        $whereClause 
        ORDER BY c.NgayThucHien DESC, c.ID DESC LIMIT 200";

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

<h1>🌾 Quản lý Canh tác</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- FORM THÊM/SỬA -->
<div class="card">
  <h3><?php echo $editData ? 'Chỉnh sửa hoạt động canh tác' : 'Thêm hoạt động canh tác mới'; ?></h3>
  <form method="post" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; padding:8px 0">
    
    <?php if ($editData): ?>
      <input type="hidden" name="ID" value="<?php echo $editData['ID']; ?>">
      <div style="grid-column:1/-1; background:#f5f5f5; padding:8px 12px; border-radius:4px">
        <strong>ID:</strong> <?php echo $editData['ID']; ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Ngày thực hiện</strong></label>
      <input type="datetime-local" name="NgayThucHien" 
             value="<?php echo $editData ? date('Y-m-d\TH:i', strtotime($editData['NgayThucHien'])) : ''; ?>">
    </div>

    <div>
      <label><strong>Loại công việc *</strong></label>
      <select name="LoaiCongViec" required>
        <option value="">-- Chọn loại công việc --</option>
        <option value="Tưới" <?php echo ($editData['LoaiCongViec']??'') === 'Tưới' ? 'selected' : ''; ?>>Tưới</option>
        <option value="Bón phân" <?php echo ($editData['LoaiCongViec']??'') === 'Bón phân' ? 'selected' : ''; ?>>Bón phân</option>
        <option value="Phun thuốc" <?php echo ($editData['LoaiCongViec']??'') === 'Phun thuốc' ? 'selected' : ''; ?>>Phun thuốc</option>
        <option value="Cắt tỉa" <?php echo ($editData['LoaiCongViec']??'') === 'Cắt tỉa' ? 'selected' : ''; ?>>Cắt tỉa</option>
        <option value="Thu hoạch" <?php echo ($editData['LoaiCongViec']??'') === 'Thu hoạch' ? 'selected' : ''; ?>>Thu hoạch</option>
        <option value="Làm cỏ" <?php echo ($editData['LoaiCongViec']??'') === 'Làm cỏ' ? 'selected' : ''; ?>>Làm cỏ</option>
        <option value="Khác" <?php echo ($editData['LoaiCongViec']??'') === 'Khác' ? 'selected' : ''; ?>>Khác</option>
      </select>
    </div>

    <div>
      <label><strong>Người thực hiện</strong></label>
      <input name="NguoiThucHien" value="<?php echo htmlspecialchars($editData['NguoiThucHien'] ?? ''); ?>" 
             placeholder="Nhập tên người thực hiện">
    </div>

    <div>
      <label><strong>Vùng trồng</strong></label>
      <select name="MaVung">
        <option value="">-- Chọn vùng trồng --</option>
        <?php foreach($listVung as $vung): ?>
          <option value="<?php echo htmlspecialchars($vung['MaVung']); ?>" 
                  <?php echo ($editData['MaVung']??'') === $vung['MaVung'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($vung['MaVung'] . ' - ' . $vung['TenVung']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Tên phân bón</strong></label>
      <select name="TenPhanBon">
        <option value="">-- Chọn phân bón --</option>
        <?php foreach($listPhanBon as $phanBon): ?>
          <option value="<?php echo htmlspecialchars($phanBon['TenPhanBon']); ?>" 
                  <?php echo ($editData['TenPhanBon']??'') === $phanBon['TenPhanBon'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($phanBon['TenPhanBon']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Liều lượng (kg)</strong></label>
      <input type="number" name="LieuLuong" step="0.1" min="0" 
             value="<?php echo $editData['LieuLuong'] ?? ''; ?>" 
             placeholder="Nhập liều lượng">
    </div>

    <div style="grid-column:1/-1">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" style="width:100%; min-height:80px" 
                placeholder="Nhập ghi chú chi tiết về hoạt động canh tác..."><?php echo htmlspecialchars($editData['GhiChu'] ?? ''); ?></textarea>
    </div>

    <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px">
      <button type="submit" class="btn">
        <?php echo $editData ? 'Cập nhật' : 'Thêm mới'; ?>
      </button>
      <?php if ($editData): ?>
        <a href="index.php?p=canhtac" class="btn" style="background:#999">Hủy</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TÌM KIẾM -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="canhtac">
    <input name="search" placeholder="Tìm theo loại công việc, người thực hiện, vùng, phân bón..." 
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:350px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?>
      <a href="index.php?p=canhtac" class="btn" style="background:#999">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
    <h3>📋 Danh sách hoạt động canh tác</h3>
    <span class="muted">Tổng: <strong><?php echo count($list); ?></strong> hoạt động</span>
  </div>
  
  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Ngày thực hiện</th>
          <th>Loại công việc</th>
          <th>Người thực hiện</th>
          <th>Vùng trồng</th>
          <th>Phân bón</th>
          <th>Liều lượng</th>
          <th>Ghi chú</th>
          <th width="100">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="9" class="muted" style="text-align:center; padding:40px">
            <?php echo $search ? "Không tìm thấy kết quả cho: \"$search\"" : "Chưa có dữ liệu"; ?>
          </td></tr>
        <?php else: foreach($list as $r): ?>
          <tr>
            <td><strong><?php echo $r['ID']; ?></strong></td>
            <td><?php echo $r['NgayThucHien'] ? date('d/m/Y H:i', strtotime($r['NgayThucHien'])) : '-'; ?></td>
            <td>
              <span class="badge <?php 
                echo match($r['LoaiCongViec']) {
                  'Tưới' => 'badge-info',
                  'Bón phân' => 'badge-success',
                  'Phun thuốc' => 'badge-warning',
                  'Cắt tỉa' => 'badge-secondary',
                  'Thu hoạch' => 'badge-primary',
                  'Làm cỏ' => 'badge-secondary',
                  default => ''
                };
              ?>">
                <?php echo htmlspecialchars($r['LoaiCongViec']); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($r['NguoiThucHien'] ?? '-'); ?></td>
            <td>
              <?php if ($r['MaVung']): ?>
                <span title="Mã: <?php echo htmlspecialchars($r['MaVung']); ?>">
                  <?php echo htmlspecialchars($r['TenVung'] ?? $r['MaVung']); ?>
                </span>
              <?php else: ?>
                <span class="muted">Chưa chọn</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($r['TenPhanBon'] ?? '-'); ?></td>
            <td style="text-align:right">
              <?php echo $r['LieuLuong'] ? number_format($r['LieuLuong'], 1) . ' kg' : '-'; ?>
            </td>
            <td title="<?php echo htmlspecialchars($r['GhiChu'] ?? ''); ?>">
              <?php echo htmlspecialchars(mb_strimwidth($r['GhiChu'] ?? '-', 0, 30, '...')); ?>
            </td>
            <td>
              <div style="display:flex; gap:2px">
                <a href="index.php?p=canhtac&action=edit&id=<?php echo $r['ID']; ?>"
                   class="btn" style="padding:2px 6px; font-size:11px" title="Chỉnh sửa">✏️</a>
                <a href="index.php?p=canhtac&action=delete&id=<?php echo $r['ID']; ?>"
                   class="btn danger" style="padding:2px 6px; font-size:11px" title="Xóa"
                   data-confirm="Xóa hoạt động '<?php echo htmlspecialchars($r['LoaiCongViec']); ?>' không thể khôi phục?">🗑️</a>
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
  max-width: 1200px;
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
.badge-info{
  background: #d1ecf1;
  color: #0c5460;
  border-color: #bee5eb;
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
.badge-secondary{
  background: #e2e3e5;
  color: #383d41;
  border-color: #d6d8db;
}
.badge-primary{
  background: #cce5ff;
  color: #004085;
  border-color: #b3d7ff;
}

/* Toolbar */
.toolbar{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

/* ===== Bảng dữ liệu ===== */
table{
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
table th, table td{
  padding: 8px 10px;                 /* giảm chút cho gọn */
  border-bottom: 1px solid var(--line);
  vertical-align: middle;
}
table th{
  background: linear-gradient(180deg, #fbf8f5 0%, #f4eee7 100%); /* be rất nhẹ */
  text-align: left;
  font-weight: 700;
  color: var(--primary);
  white-space: nowrap;
}
table tr:hover td{
  background: #faf6f1;
}
table td{
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
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

/* ===== Tiện ích ===== */
.muted{ color: var(--muted); }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .container{
    max-width: 100%;
    padding: 0 12px;
  }
  
  table{
    font-size: 12px;
  }
  
  table th, table td{
    padding: 6px 8px;
  }
  
  .toolbar{
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar input{
    min-width: auto !important;
  }
}

/* ===== Cuộn nhẹ nhàng ===== */
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