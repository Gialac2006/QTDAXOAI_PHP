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

$TABLE_THIET_BI = findTable($conn, ['thiet_bi_may_moc','thietbimaymoc']);
$TABLE_VUNG = findTable($conn, ['vung_trong','vungtrong']);
$TABLE_HO = findTable($conn, ['ho_nong_dan','honongdan']);

if (!$TABLE_THIET_BI) {
  echo "<h1>🔧 Thiết bị máy móc</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>thiet_bi_may_moc</code> hoặc <code>thietbimaymoc</code> trong CSDL <b>qlvtxoai</b>.</div>";
  return;
}

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

// Lấy danh sách vùng trồng và hộ nông dân cho dropdown
$listVung = [];
$listHo = [];

if ($TABLE_VUNG) {
  $q = $conn->query("SELECT MaVung, TenVung FROM `$TABLE_VUNG` ORDER BY TenVung");
  if ($q) $listVung = $q->fetch_all(MYSQLI_ASSOC);
}

if ($TABLE_HO) {
  $q = $conn->query("SELECT MaHo, TenChuHo FROM `$TABLE_HO` ORDER BY TenChuHo");
  if ($q) $listHo = $q->fetch_all(MYSQLI_ASSOC);
}

// XỬ LÝ CÁC THAO TÁC
switch ($action) {
  case 'delete':
    $MaThietBi = trim($_GET['id'] ?? '');
    if ($MaThietBi !== '') {
      $stmt = $conn->prepare("DELETE FROM `$TABLE_THIET_BI` WHERE MaThietBi=?");
      $stmt->bind_param("s", $MaThietBi);
      if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
      else $msg = "Đã xóa thiết bị: $MaThietBi";
      $stmt->close();
    }
    break;
    
  case 'edit':
    $MaThietBi = trim($_GET['id'] ?? '');
    if ($MaThietBi !== '') {
      $stmt = $conn->prepare("SELECT * FROM `$TABLE_THIET_BI` WHERE MaThietBi=?");
      $stmt->bind_param("s", $MaThietBi);
      $stmt->execute();
      $result = $stmt->get_result();
      $editData = $result->fetch_assoc();
      $stmt->close();
    }
    break;
}

// XỬ LÝ FORM (THÊM/SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenThietBi'])) {
  $MaThietBi = trim($_POST['MaThietBi'] ?? '');
  $TenThietBi = trim($_POST['TenThietBi'] ?? '');
  $LoaiThietBi = trim($_POST['LoaiThietBi'] ?? '') ?: null;
  $NamSuDung = intval($_POST['NamSuDung'] ?? 0) ?: null;
  $TinhTrang = trim($_POST['TinhTrang'] ?? 'Tốt');
  $MaHo = trim($_POST['MaHo'] ?? '') ?: null;
  $MaVung = trim($_POST['MaVung'] ?? '') ?: null;
  $isEdit = !empty($MaThietBi);

  // Validate
  if ($TenThietBi === '') $err = 'Vui lòng nhập Tên thiết bị';
  else {
    if ($isEdit) {
      // CẬP NHẬT
      $sql = "UPDATE `$TABLE_THIET_BI` SET TenThietBi=?, LoaiThietBi=?, NamSuDung=?, TinhTrang=?, MaHo=?, MaVung=? WHERE MaThietBi=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssisss", $TenThietBi, $LoaiThietBi, $NamSuDung, $TinhTrang, $MaHo, $MaVung, $MaThietBi);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else $msg = "Đã cập nhật thiết bị: $TenThietBi";
    } else {
      // THÊM MỚI (auto-generate MaThietBi)
      do {
        $MaThietBi = 'TB' . sprintf('%03d', rand(100, 999));
        $check = $conn->query("SELECT MaThietBi FROM `$TABLE_THIET_BI` WHERE MaThietBi='$MaThietBi'");
      } while ($check && $check->num_rows > 0);
      
      $sql = "INSERT INTO `$TABLE_THIET_BI` (MaThietBi, TenThietBi, LoaiThietBi, NamSuDung, TinhTrang, MaHo, MaVung) VALUES (?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssisss", $MaThietBi, $TenThietBi, $LoaiThietBi, $NamSuDung, $TinhTrang, $MaHo, $MaVung);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm thiết bị: $TenThietBi (Mã: $MaThietBi)";
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
  $whereClause = "WHERE t.TenThietBi LIKE ? OR t.MaThietBi LIKE ? OR t.LoaiThietBi LIKE ? OR t.TinhTrang LIKE ? OR h.TenChuHo LIKE ? OR v.TenVung LIKE ?";
  $searchTerm = "%$search%";
  $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
  $types = 'ssssss';
}

// LẤY DANH SÁCH VỚI JOIN
$list = [];
$sql = "SELECT t.*, h.TenChuHo, v.TenVung 
        FROM `$TABLE_THIET_BI` t 
        LEFT JOIN `$TABLE_HO` h ON t.MaHo = h.MaHo 
        LEFT JOIN `$TABLE_VUNG` v ON t.MaVung = v.MaVung 
        $whereClause 
        ORDER BY t.TenThietBi ASC LIMIT 200";

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

<h1>🔧 Quản lý Thiết bị Máy móc</h1>

<?php if ($msg): ?><div class="msg" style="display:block"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- FORM THÊM/SỬA -->
<div class="card">
  <h3><?php echo $editData ? 'Chỉnh sửa thiết bị máy móc' : 'Thêm thiết bị máy móc mới'; ?></h3>
  <form method="post" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; padding:8px 0">
    
    <?php if ($editData): ?>
      <input type="hidden" name="MaThietBi" value="<?php echo htmlspecialchars($editData['MaThietBi']); ?>">
      <div style="grid-column:1/-1; background:#f5f5f5; padding:8px 12px; border-radius:4px">
        <strong>Mã thiết bị:</strong> <?php echo htmlspecialchars($editData['MaThietBi']); ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Tên thiết bị *</strong></label>
      <input name="TenThietBi" required value="<?php echo htmlspecialchars($editData['TenThietBi'] ?? ''); ?>">
    </div>

    <div>
      <label><strong>Loại thiết bị</strong></label>
      <select name="LoaiThietBi">
        <option value="">-- Chọn loại thiết bị --</option>
        <option value="Làm đất" <?php echo ($editData['LoaiThietBi']??'') === 'Làm đất' ? 'selected' : ''; ?>>Làm đất</option>
        <option value="Tưới tiêu" <?php echo ($editData['LoaiThietBi']??'') === 'Tưới tiêu' ? 'selected' : ''; ?>>Tưới tiêu</option>
        <option value="Phun thuốc" <?php echo ($editData['LoaiThietBi']??'') === 'Phun thuốc' ? 'selected' : ''; ?>>Phun thuốc</option>
        <option value="Thu hoạch" <?php echo ($editData['LoaiThietBi']??'') === 'Thu hoạch' ? 'selected' : ''; ?>>Thu hoạch</option>
        <option value="Vận chuyển" <?php echo ($editData['LoaiThietBi']??'') === 'Vận chuyển' ? 'selected' : ''; ?>>Vận chuyển</option>
        <option value="Cắt tỉa" <?php echo ($editData['LoaiThietBi']??'') === 'Cắt tỉa' ? 'selected' : ''; ?>>Cắt tỉa</option>
        <option value="Khác" <?php echo ($editData['LoaiThietBi']??'') === 'Khác' ? 'selected' : ''; ?>>Khác</option>
      </select>
    </div>

    <div>
      <label><strong>Năm sử dụng</strong></label>
      <input type="number" name="NamSuDung" min="1990" max="<?php echo date('Y') + 1; ?>" 
             value="<?php echo $editData['NamSuDung'] ?? ''; ?>" placeholder="VD: 2023">
    </div>

    <div>
      <label><strong>Tình trạng</strong></label>
      <select name="TinhTrang">
        <option value="Tốt" <?php echo ($editData['TinhTrang']??'Tốt') === 'Tốt' ? 'selected' : ''; ?>>Tốt</option>
        <option value="Khá" <?php echo ($editData['TinhTrang']??'') === 'Khá' ? 'selected' : ''; ?>>Khá</option>
        <option value="Trung bình" <?php echo ($editData['TinhTrang']??'') === 'Trung bình' ? 'selected' : ''; ?>>Trung bình</option>
        <option value="Kém" <?php echo ($editData['TinhTrang']??'') === 'Kém' ? 'selected' : ''; ?>>Kém</option>
        <option value="Hỏng" <?php echo ($editData['TinhTrang']??'') === 'Hỏng' ? 'selected' : ''; ?>>Hỏng</option>
        <option value="Đang sửa chữa" <?php echo ($editData['TinhTrang']??'') === 'Đang sửa chữa' ? 'selected' : ''; ?>>Đang sửa chữa</option>
        <option value="Không sử dụng" <?php echo ($editData['TinhTrang']??'') === 'Không sử dụng' ? 'selected' : ''; ?>>Không sử dụng</option>
      </select>
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

    <div style="grid-column:1/-1; display:flex; gap:12px; margin-top:8px">
      <button type="submit" class="btn">
        <?php echo $editData ? 'Cập nhật' : 'Thêm mới'; ?>
      </button>
      <?php if ($editData): ?>
        <a href="index.php?p=thietbimaymoc" class="btn" style="background:#999">Hủy</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TÌM KIẾM -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="thietbimaymoc">
    <input name="search" placeholder="Tìm theo tên thiết bị, loại, tình trạng, hộ nông dân, vùng..." 
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:350px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?>
      <a href="index.php?p=thietbimaymoc" class="btn" style="background:#999">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
    <h3>📋 Danh sách thiết bị máy móc</h3>
    <span class="muted">Tổng: <strong><?php echo count($list); ?></strong> thiết bị</span>
  </div>
  
  <div style="overflow:auto">
    <table>
      <thead>
        <tr>
          <th>Mã thiết bị</th>
          <th>Tên thiết bị</th>
          <th>Loại thiết bị</th>
          <th>Năm sử dụng</th>
          <th>Tình trạng</th>
          <th>Hộ nông dân</th>
          <th>Vùng trồng</th>
          <th width="100">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="8" class="muted" style="text-align:center; padding:40px">
            <?php echo $search ? "Không tìm thấy kết quả cho: \"$search\"" : "Chưa có dữ liệu"; ?>
          </td></tr>
        <?php else: foreach($list as $r): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($r['MaThietBi']); ?></strong></td>
            <td><?php echo htmlspecialchars($r['TenThietBi']); ?></td>
            <td>
              <?php if ($r['LoaiThietBi']): ?>
                <span class="badge <?php 
                  echo match($r['LoaiThietBi']) {
                    'Làm đất' => 'badge-secondary',
                    'Tưới tiêu' => 'badge-info',
                    'Phun thuốc' => 'badge-warning',
                    'Thu hoạch' => 'badge-success',
                    'Vận chuyển' => 'badge-primary',
                    'Cắt tỉa' => 'badge-secondary',
                    default => ''
                  };
                ?>">
                  <?php echo htmlspecialchars($r['LoaiThietBi']); ?>
                </span>
              <?php else: ?>
                <span class="muted">Chưa phân loại</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <?php echo $r['NamSuDung'] ?: '-'; ?>
              <?php if ($r['NamSuDung']): ?>
                <br><small class="muted"><?php echo date('Y') - $r['NamSuDung']; ?> năm</small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?php 
                echo match($r['TinhTrang']) {
                  'Tốt' => 'badge-success',
                  'Khá' => 'badge-info',
                  'Trung bình' => 'badge-warning',
                  'Kém' => 'badge-danger',
                  'Hỏng' => 'badge-danger',
                  'Đang sửa chữa' => 'badge-warning',
                  'Không sử dụng' => 'badge-secondary',
                  default => ''
                };
              ?>">
                <?php echo htmlspecialchars($r['TinhTrang']); ?>
              </span>
            </td>
            <td>
              <?php if ($r['MaHo']): ?>
                <span title="Mã: <?php echo htmlspecialchars($r['MaHo']); ?>">
                  <?php echo htmlspecialchars($r['TenChuHo'] ?? $r['MaHo']); ?>
                </span>
              <?php else: ?>
                <span class="muted">Chưa gán</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['MaVung']): ?>
                <span title="Mã: <?php echo htmlspecialchars($r['MaVung']); ?>">
                  <?php echo htmlspecialchars($r['TenVung'] ?? $r['MaVung']); ?>
                </span>
              <?php else: ?>
                <span class="muted">Chưa gán</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex; gap:2px">
                <a href="index.php?p=thietbimaymoc&action=edit&id=<?php echo urlencode($r['MaThietBi']); ?>"
                   class="btn" style="padding:2px 6px; font-size:11px" title="Chỉnh sửa">✏️</a>
                <a href="index.php?p=thietbimaymoc&action=delete&id=<?php echo urlencode($r['MaThietBi']); ?>"
                   class="btn danger" style="padding:2px 6px; font-size:11px" title="Xóa"
                   data-confirm="Xóa thiết bị '<?php echo htmlspecialchars($r['TenThietBi']); ?>' không thể khôi phục?">🗑️</a>
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
    margin: 16px auto;
    padding: 0 12px;
  }
  .card{
    padding: 12px;
    border-radius: 12px;
  }
  .toolbar input{
    min-width: unset;
    flex: 1;
  }
  table{
    font-size: 13px;
  }
  table th, table td{
    padding: 7px 8px;
  }
}
.container{
  width: 80%;          /* chiếm 95% màn hình */
  max-width: 1600px;   /* không vượt quá 1600px */
  margin: 32px auto;
  padding: 0 28px;
}

/* ===== End Responsive ===== */
</style>

<script>
// Xác nhận xoá bằng thuộc tính data-confirm trên nút xoá
document.addEventListener('click', function(e){
  const a = e.target.closest('a[data-confirm]');
  if(!a) return;
  const msg = a.getAttribute('data-confirm') || 'Xác nhận xoá?';
  if(!confirm(msg)){
    e.preventDefault();
  }
});
</script>
