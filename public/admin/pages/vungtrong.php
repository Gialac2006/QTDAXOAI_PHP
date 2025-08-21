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

$TABLE_VUNG = findTable($conn, ['vung_trong','vungtrong']);
$TABLE_HO = findTable($conn, ['ho_nong_dan','honongdan']);
$TABLE_GIONG = findTable($conn, ['giong_xoai','giongxoai']);

if (!$TABLE_VUNG) {
  echo "<h1>🌱 Vùng trồng</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>vung_trong</code> hoặc <code>vungtrong</code> trong CSDL <b>qlvtxoai</b>.</div>";
  return;
}

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

// Lấy danh sách hộ nông dân và giống xoài cho dropdown
$listHo = [];
$listGiong = [];

if ($TABLE_HO) {
  $q = $conn->query("SELECT MaHo, TenChuHo FROM `$TABLE_HO` ORDER BY TenChuHo");
  if ($q) $listHo = $q->fetch_all(MYSQLI_ASSOC);
}

if ($TABLE_GIONG) {
  $q = $conn->query("SELECT MaGiong, TenGiong FROM `$TABLE_GIONG` WHERE TinhTrang = 'Còn sử dụng' ORDER BY TenGiong");
  if ($q) $listGiong = $q->fetch_all(MYSQLI_ASSOC);
}

// XỬ LÝ CÁC THAO TÁC
switch ($action) {
  case 'delete':
    $MaVung = trim($_GET['id'] ?? '');
    if ($MaVung !== '') {
      $stmt = $conn->prepare("DELETE FROM `$TABLE_VUNG` WHERE MaVung=?");
      $stmt->bind_param("s", $MaVung);
      if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
      else $msg = "Đã xóa vùng: $MaVung";
      $stmt->close();
    }
    break;
    
  case 'edit':
    $MaVung = trim($_GET['id'] ?? '');
    if ($MaVung !== '') {
      $stmt = $conn->prepare("SELECT * FROM `$TABLE_VUNG` WHERE MaVung=?");
      $stmt->bind_param("s", $MaVung);
      $stmt->execute();
      $result = $stmt->get_result();
      $editData = $result->fetch_assoc();
      $stmt->close();
    }
    break;
}

// XỬ LÝ FORM (THÊM/SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenVung'])) {
  $MaVung = trim($_POST['MaVung'] ?? '');
  $TenVung = trim($_POST['TenVung'] ?? '');
  $DiaChi = trim($_POST['DiaChi'] ?? '') ?: null;
  $DienTich = floatval($_POST['DienTich'] ?? 0) ?: null;
  $TinhTrang = trim($_POST['TinhTrang'] ?? 'Đang trồng');
  $NgayBatDau = trim($_POST['NgayBatDau'] ?? '') ?: null;
  $MaHo = trim($_POST['MaHo'] ?? '') ?: null;
  $MaGiong = trim($_POST['MaGiong'] ?? '') ?: null;
  $isEdit = !empty($MaVung);

  // Validate
  if ($TenVung === '') $err = 'Vui lòng nhập Tên vùng';
  else {
    if ($isEdit) {
      // CẬP NHẬT
      $sql = "UPDATE `$TABLE_VUNG` SET TenVung=?, DiaChi=?, DienTich=?, TinhTrang=?, NgayBatDau=?, MaHo=?, MaGiong=? WHERE MaVung=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssdsssss", $TenVung, $DiaChi, $DienTich, $TinhTrang, $NgayBatDau, $MaHo, $MaGiong, $MaVung);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else $msg = "Đã cập nhật vùng: $TenVung";
    } else {
      // THÊM MỚI (auto-generate MaVung)
      do {
        $MaVung = 'V' . sprintf('%03d', rand(100, 999));
        $check = $conn->query("SELECT MaVung FROM `$TABLE_VUNG` WHERE MaVung='$MaVung'");
      } while ($check && $check->num_rows > 0);
      
      $sql = "INSERT INTO `$TABLE_VUNG` (MaVung, TenVung, DiaChi, DienTich, TinhTrang, NgayBatDau, MaHo, MaGiong) VALUES (?,?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssdsssss", $MaVung, $TenVung, $DiaChi, $DienTich, $TinhTrang, $NgayBatDau, $MaHo, $MaGiong);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm vùng: $TenVung (Mã: $MaVung)";
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
  $whereClause = "WHERE v.TenVung LIKE ? OR v.MaVung LIKE ? OR v.DiaChi LIKE ? OR v.TinhTrang LIKE ? OR h.TenChuHo LIKE ? OR g.TenGiong LIKE ?";
  $searchTerm = "%$search%";
  $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
  $types = 'ssssss';
}

// LẤY DANH SÁCH VỚI JOIN
$list = [];
$sql = "SELECT v.*, h.TenChuHo, g.TenGiong 
        FROM `$TABLE_VUNG` v 
        LEFT JOIN `$TABLE_HO` h ON v.MaHo = h.MaHo 
        LEFT JOIN `$TABLE_GIONG` g ON v.MaGiong = g.MaGiong 
        $whereClause 
        ORDER BY v.TenVung ASC LIMIT 200";

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

<h1> Quản lý Vùng trồng</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- FORM THÊM/SỬA -->
<div class="card">
  <h3><?php echo $editData ? 'Chỉnh sửa vùng trồng' : 'Thêm vùng trồng mới'; ?></h3>
  <form method="post" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; padding:8px 0">
    
    <?php if ($editData): ?>
      <input type="hidden" name="MaVung" value="<?php echo htmlspecialchars($editData['MaVung']); ?>">
      <div style="grid-column:1/-1; background:#f5f5f5; padding:8px 12px; border-radius:4px">
        <strong>Mã vùng:</strong> <?php echo htmlspecialchars($editData['MaVung']); ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Tên vùng *</strong></label>
      <input name="TenVung" required value="<?php echo htmlspecialchars($editData['TenVung'] ?? ''); ?>">
    </div>

    <div>
      <label><strong>Diện tích (m²)</strong></label>
      <input type="number" name="DienTich" step="0.1" min="0" value="<?php echo $editData['DienTich'] ?? ''; ?>">
    </div>

    <div>
      <label><strong>Tình trạng</strong></label>
      <select name="TinhTrang">
        <option value="Đang trồng" <?php echo ($editData['TinhTrang']??'Đang trồng') === 'Đang trồng' ? 'selected' : ''; ?>>Đang trồng</option>
        <option value="Chuẩn bị" <?php echo ($editData['TinhTrang']??'') === 'Chuẩn bị' ? 'selected' : ''; ?>>Chuẩn bị</option>
        <option value="Bảo trì" <?php echo ($editData['TinhTrang']??'') === 'Bảo trì' ? 'selected' : ''; ?>>Bảo trì</option>
        <option value="Nghỉ ruộng" <?php echo ($editData['TinhTrang']??'') === 'Nghỉ ruộng' ? 'selected' : ''; ?>>Nghỉ ruộng</option>
        <option value="Hoàn thành" <?php echo ($editData['TinhTrang']??'') === 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
      </select>
    </div>

    <div>
      <label><strong>Ngày bắt đầu</strong></label>
      <input type="date" name="NgayBatDau" value="<?php echo htmlspecialchars($editData['NgayBatDau'] ?? ''); ?>">
    </div>

    <div>
      <label><strong>Hộ nông dân</strong></label>
      <select name="MaHo">
        <option value="">-- Chọn hộ nông dân --</option>
        <?php foreach($listHo as $ho): ?>
          <option value="<?php echo htmlspecialchars($ho['MaHo']); ?>" 
                  <?php echo ($editData['MaHo']??'') === $ho['MaHo'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($ho['MaHo'] . ' - ' . $ho['TenChuHo']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Giống xoài</strong></label>
      <select name="MaGiong">
        <option value="">-- Chọn giống xoài --</option>
        <?php foreach($listGiong as $giong): ?>
          <option value="<?php echo htmlspecialchars($giong['MaGiong']); ?>" 
                  <?php echo ($editData['MaGiong']??'') === $giong['MaGiong'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($giong['MaGiong'] . ' - ' . $giong['TenGiong']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="grid-column:1/-1">
      <label><strong>Địa chỉ</strong></label>
      <textarea name="DiaChi" style="width:100%; min-height:80px" placeholder="Nhập địa chỉ chi tiết của vùng trồng..."><?php echo htmlspecialchars($editData['DiaChi'] ?? ''); ?></textarea>
    </div>

    <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px">
      <button type="submit" class="btn">
        <?php echo $editData ? 'Cập nhật' : 'Thêm mới'; ?>
      </button>
      <?php if ($editData): ?>
        <a href="index.php?p=vungtrong" class="btn" style="background:#999">Hủy</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TÌM KIẾM -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="vungtrong">
    <input name="search" placeholder="Tìm theo tên vùng, địa chỉ, hộ nông dân, giống xoài..." 
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:350px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?>
      <a href="index.php?p=vungtrong" class="btn" style="background:#999">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
    <h3>📋 Danh sách vùng trồng</h3>
    <span class="muted">Tổng: <strong><?php echo count($list); ?></strong> vùng</span>
  </div>
  
  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th>Mã vùng</th>
          <th>Tên vùng</th>
          <th>Diện tích</th>
          <th>Tình trạng</th>
          <th>Ngày bắt đầu</th>
          <th>Hộ nông dân</th>
          <th>Giống xoài</th>
          <th>Địa chỉ</th>
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
            <td><strong><?php echo htmlspecialchars($r['MaVung']); ?></strong></td>
            <td><?php echo htmlspecialchars($r['TenVung']); ?></td>
            <td style="text-align:right">
              <?php echo $r['DienTich'] ? number_format($r['DienTich'], 1) . ' m²' : '-'; ?>
            </td>
            <td>
              <span class="badge <?php 
                echo match($r['TinhTrang']) {
                  'Đang trồng' => 'badge-success',
                  'Chuẩn bị' => 'badge-info',
                  'Bảo trì' => 'badge-warning', 
                  'Nghỉ ruộng' => 'badge-secondary',
                  'Hoàn thành' => 'badge-primary',
                  default => ''
                };
              ?>">
                <?php echo htmlspecialchars($r['TinhTrang']); ?>
              </span>
            </td>
            <td><?php echo $r['NgayBatDau'] ?: '-'; ?></td>
            <td>
              <?php if ($r['MaHo']): ?>
                <span title="Mã: <?php echo htmlspecialchars($r['MaHo']); ?>">
                  <?php echo htmlspecialchars($r['TenChuHo'] ?? $r['MaHo']); ?>
                </span>
              <?php else: ?>
                <span class="muted">Chưa chọn</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['MaGiong']): ?>
                <span title="Mã: <?php echo htmlspecialchars($r['MaGiong']); ?>">
                  <?php echo htmlspecialchars($r['TenGiong'] ?? $r['MaGiong']); ?>
                </span>
              <?php else: ?>
                <span class="muted">Chưa chọn</span>
              <?php endif; ?>
            </td>
            <td title="<?php echo htmlspecialchars($r['DiaChi'] ?? ''); ?>">
              <?php echo htmlspecialchars(mb_strimwidth($r['DiaChi'] ?? '-', 0, 30, '...')); ?>
            </td>
            <td>
              <div style="display:flex; gap:2px">
                <a href="index.php?p=vungtrong&action=edit&id=<?php echo urlencode($r['MaVung']); ?>"
                   class="btn" style="padding:2px 6px; font-size:11px" title="Chỉnh sửa">✏️</a>
                <a href="index.php?p=vungtrong&action=delete&id=<?php echo urlencode($r['MaVung']); ?>"
                   class="btn danger" style="padding:2px 6px; font-size:11px" title="Xóa"
                   data-confirm="Xóa vùng '<?php echo htmlspecialchars($r['TenVung']); ?>' không thể khôi phục?">🗑️</a>
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