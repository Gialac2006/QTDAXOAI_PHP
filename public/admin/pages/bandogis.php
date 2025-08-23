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

/* ===== Tables ===== */
$TABLE_BANDOGIS = findTable($conn, ['ban_do_gis','bandogis']);
$TABLE_VUNG = findTable($conn, ['vung_trong','vungtrong']);

if (!$TABLE_BANDOGIS) {
  echo "<h1>Quản lý Bản đồ GIS</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>ban_do_gis</code>/<code>bandogis</code> trong CSDL.</div>";
  return;
}

/* ===== Load options ===== */
$vungOptions = [];
if ($TABLE_VUNG) {
  // Kiểm tra cột có sẵn trong bảng vung_trong
  $columns = $conn->query("SHOW COLUMNS FROM `$TABLE_VUNG`");
  $hasName = false;
  $nameColumn = '';
  
  if ($columns) {
    while ($col = $columns->fetch_assoc()) {
      if (in_array(strtolower($col['Field']), ['tenvung', 'ten_vung', 'ten', 'name'])) {
        $hasName = true;
        $nameColumn = $col['Field'];
        break;
      }
    }
  }
  
  if ($hasName) {
    $rs = $conn->query("SELECT MaVung, `$nameColumn` as TenVung FROM `$TABLE_VUNG` ORDER BY `$nameColumn`");
    if ($rs) while ($r = $rs->fetch_assoc()) $vungOptions[$r['MaVung']] = $r['TenVung'];
  } else {
    // Nếu không có cột tên, chỉ lấy mã
    $rs = $conn->query("SELECT MaVung FROM `$TABLE_VUNG` ORDER BY MaVung");
    if ($rs) while ($r = $rs->fetch_assoc()) $vungOptions[$r['MaVung']] = $r['MaVung'];
  }
}

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

/* ===== Actions ===== */
if ($action === 'delete') {
  $MaVung = trim($_GET['id'] ?? '');
  if ($MaVung !== '') {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_BANDOGIS` WHERE MaVung=?");
    $stmt->bind_param('s', $MaVung);
    if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
    else $msg = "Đã xóa bản đồ vùng: $MaVung";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $MaVung = trim($_GET['id'] ?? '');
  if ($MaVung !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$TABLE_BANDOGIS` WHERE MaVung=?");
    $stmt->bind_param('s', $MaVung);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MaVung'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']=='1';
  $MaVungOriginal = trim($_POST['MaVung_original'] ?? '');

  $MaVung = trim($_POST['MaVung'] ?? '');
  $ToaDo = trim($_POST['ToaDo'] ?? '') ?: null;
  $NhanTen = trim($_POST['NhanTen'] ?? '') ?: null;
  $ThongTinPopup = trim($_POST['ThongTinPopup'] ?? '') ?: null;

  if ($MaVung === '') $err = 'Vui lòng chọn Vùng trồng';
  else {
    if ($isEdit) {
      // Đổi khóa chính -> kiểm tra trùng
      if ($MaVung !== $MaVungOriginal) {
        $chk = $conn->prepare("SELECT 1 FROM `$TABLE_BANDOGIS` WHERE MaVung=?");
        $chk->bind_param('s', $MaVung);
        $chk->execute();
        $dup = $chk->get_result()->num_rows > 0;
        $chk->close();
        if ($dup) $err = "Vùng này đã có thông tin bản đồ";
      }
      if (!$err) {
        $sql = "UPDATE `$TABLE_BANDOGIS` SET MaVung=?, ToaDo=?, NhanTen=?, ThongTinPopup=? WHERE MaVung=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $MaVung, $ToaDo, $NhanTen, $ThongTinPopup, $MaVungOriginal);
        if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
        else { $msg = "Đã cập nhật bản đồ vùng: $MaVung"; $editData = null; }
        $stmt->close();
      }
    } else {
      // Thêm mới
      $chk = $conn->prepare("SELECT 1 FROM `$TABLE_BANDOGIS` WHERE MaVung=?");
      $chk->bind_param('s', $MaVung);
      $chk->execute();
      if ($chk->get_result()->num_rows > 0) $err = "Vùng này đã có thông tin bản đồ";
      $chk->close();

      if (!$err) {
        $sql = "INSERT INTO `$TABLE_BANDOGIS` (MaVung, ToaDo, NhanTen, ThongTinPopup) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $MaVung, $ToaDo, $NhanTen, $ThongTinPopup);
        if (!$stmt->execute()) {
          if ($conn->errno == 1062) $err = "Vùng này đã có thông tin bản đồ";
          elseif ($conn->errno == 1452) $err = "Mã vùng không tồn tại trong hệ thống";
          else $err = "Không thêm được: ".$stmt->error;
        } else $msg = "Đã thêm bản đồ cho vùng: $MaVung";
        $stmt->close();
      }
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$filterVung = trim($_GET['filter_vung'] ?? '');

$where = []; $types=''; $params=[];

if ($search !== '') {
  $where[] = "(NhanTen LIKE ? OR ThongTinPopup LIKE ?)";
  $kw = "%$search%"; 
  $types .= 'ss'; 
  $params = array_merge($params, [$kw, $kw]);
}
if ($filterVung !== '') {
  $where[] = "MaVung = ?";
  $types .= 's';
  $params[] = $filterVung;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM `$TABLE_BANDOGIS` $whereClause ORDER BY MaVung ASC LIMIT 300";
if ($params) { 
  $stmt=$conn->prepare($sql); 
  $stmt->bind_param($types, ...$params); 
  $stmt->execute(); 
  $list=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); 
  $stmt->close(); 
} else { 
  $q=$conn->query($sql); 
  $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; 
}
?>

<h1>Quản lý Bản đồ GIS</h1>
<link rel="stylesheet" href="pages/layout/assets/css/bandogis.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $editData ? 'Chỉnh sửa bản đồ GIS' : 'Thêm bản đồ GIS mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="MaVung_original" value="<?= htmlspecialchars($editData['MaVung']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã vùng cũ:</strong> <?= htmlspecialchars($editData['MaVung']) ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Vùng trồng *</strong></label>
      <?php $vungSel = $editData['MaVung'] ?? ''; ?>
      <select name="MaVung" required>
        <option value="">-- Chọn vùng trồng --</option>
        <?php foreach ($vungOptions as $ma => $ten): ?>
          <option value="<?= htmlspecialchars($ma) ?>" <?= $vungSel===$ma?'selected':'' ?>>
            <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Nhãn tên</strong></label>
      <input name="NhanTen" placeholder="Tên hiển thị trên bản đồ" value="<?= htmlspecialchars($editData['NhanTen'] ?? '') ?>">
    </div>

    <div class="full">
      <label><strong>Tọa độ GIS</strong></label>
      <textarea name="ToaDo" placeholder="Nhập tọa độ GIS (JSON, WKT, hoặc định dạng khác)..." style="min-height:120px"><?= htmlspecialchars($editData['ToaDo'] ?? '') ?></textarea>
    </div>

    <div class="full">
      <label><strong>Thông tin popup</strong></label>
      <textarea name="ThongTinPopup" placeholder="Thông tin hiển thị khi click vào vùng trên bản đồ..."><?= htmlspecialchars($editData['ThongTinPopup'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($editData): ?><a class="btn secondary" href="index.php?p=bandogis">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SEARCH & FILTER -->
<div class="card">
  <form method="get" class="toolbar" style="flex-wrap:wrap">
    <input type="hidden" name="p" value="bandogis">
    
    <input name="search" placeholder="Tìm theo nhãn tên, thông tin popup..." value="<?= htmlspecialchars($search) ?>" style="min-width:300px">
    
    <select name="filter_vung" style="min-width:200px">
      <option value="">-- Tất cả vùng --</option>
      <?php foreach ($vungOptions as $ma => $ten): ?>
        <option value="<?= htmlspecialchars($ma) ?>" <?= $filterVung===$ma?'selected':'' ?>>
          <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn">Tìm kiếm</button>
    <?php if ($search || $filterVung): ?>
      <a href="index.php?p=bandogis" class="btn secondary">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>🗺️ Danh sách bản đồ GIS</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> vùng</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Mã vùng</th>
          <th>Vùng trồng</th>
          <th>Nhãn tên</th>
          <th>Tọa độ</th>
          <th>Thông tin popup</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['MaVung']) ?></strong></td>
          <td>
            <?php 
            $vungText = $r['MaVung'];
            if (isset($vungOptions[$r['MaVung']]) && $vungOptions[$r['MaVung']] !== $r['MaVung']) {
              $vungText = $vungOptions[$r['MaVung']];
            }
            echo htmlspecialchars($vungText);
            ?>
          </td>
          <td><?= htmlspecialchars($r['NhanTen'] ?? '-') ?></td>
          <td>
            <?php 
            $toaDo = $r['ToaDo'] ?? '';
            if (strlen($toaDo) > 50) {
              echo '<span class="tooltip" title="' . htmlspecialchars($toaDo) . '">';
              echo htmlspecialchars(substr($toaDo, 0, 50)) . '...';
              echo '</span>';
            } else {
              echo htmlspecialchars($toaDo ?: '-');
            }
            ?>
          </td>
          <td>
            <?php 
            $popup = $r['ThongTinPopup'] ?? '';
            if (strlen($popup) > 60) {
              echo '<span class="tooltip" title="' . htmlspecialchars($popup) . '">';
              echo htmlspecialchars(substr($popup, 0, 60)) . '...';
              echo '</span>';
            } else {
              echo htmlspecialchars($popup ?: '-');
            }
            ?>
          </td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=bandogis&action=edit&id=<?= urlencode($r['MaVung']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=bandogis&action=delete&id=<?= urlencode($r['MaVung']) ?>"
               title="Xóa" data-confirm="Xóa bản đồ vùng '<?= htmlspecialchars($r['MaVung']) ?>' không thể khôi phục?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/bandogis.js"></script>
  </div>
</div>