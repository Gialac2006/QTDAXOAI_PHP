<?php
ob_start();

require __DIR__ . '/../../connect.php';

/* ===== Fonction helper pour redirection sécurisée ===== */
function safeRedirect($url, $message = '') {
    if (!headers_sent()) {
        header("Location: $url" . ($message ? "&msg=" . urlencode($message) : ""));
        exit;
    } else {
        echo "<script>";
        if ($message) echo "alert('" . addslashes($message) . "');";
        echo "window.location.href = '" . addslashes($url) . "';";
        echo "</script>";
        exit;
    }
}

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
$TABLE_GIAXOAI = findTable($conn, ['gia_xoai','giaxoai']);
$TABLE_GIONG = findTable($conn, ['giong_xoai','giongxoai']);

if (!$TABLE_GIAXOAI) {
  echo "<h1>Quản lý Giá xoài</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>gia_xoai</code>/<code>giaxoai</code> trong CSDL.</div>";
  return;
}

/* ===== Load options ===== */
$giongOptions = [];
if ($TABLE_GIONG) {
  $columns = $conn->query("SHOW COLUMNS FROM `$TABLE_GIONG`");
  $hasName = false;
  $nameColumn = '';
  
  if ($columns) {
    while ($col = $columns->fetch_assoc()) {
      if (in_array(strtolower($col['Field']), ['tengiong', 'ten_giong', 'ten', 'name'])) {
        $hasName = true;
        $nameColumn = $col['Field'];
        break;
      }
    }
  }
  
  if ($hasName) {
    $rs = $conn->query("SELECT MaGiong, `$nameColumn` as TenGiong FROM `$TABLE_GIONG` ORDER BY `$nameColumn`");
    if ($rs) while ($r = $rs->fetch_assoc()) $giongOptions[$r['MaGiong']] = $r['TenGiong'];
  } else {
    $rs = $conn->query("SELECT MaGiong FROM `$TABLE_GIONG` ORDER BY MaGiong");
    if ($rs) while ($r = $rs->fetch_assoc()) $giongOptions[$r['MaGiong']] = $r['MaGiong'];
  }
}

$donViOptions = ['kg', 'tấn', 'yến'];

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

/* ===== Actions ===== */
if ($action === 'delete') {
  $ID = (int)($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_GIAXOAI` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    if (!$stmt->execute()) {
      $err = "Không xóa được: ".$stmt->error;
    } else {
      $stmt->close();
      safeRedirect("index.php?p=giaxoai", "Đã xóa giá xoài ID: $ID");
    }
    $stmt->close();
  }
}

if ($action === 'edit') {
  $ID = (int)($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("SELECT * FROM `$TABLE_GIAXOAI` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['NgayCapNhat'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']=='1';
  $ID = (int)($_POST['ID'] ?? 0);

  $NgayCapNhat = trim($_POST['NgayCapNhat'] ?? '');
  $MaGiong = trim($_POST['MaGiong'] ?? '');
  $GiaBan = (float)($_POST['GiaBan'] ?? 0);
  $DonViTinh = trim($_POST['DonViTinh'] ?? '') ?: 'kg';
  $GhiChu = trim($_POST['GhiChu'] ?? '') ?: null;

  if ($NgayCapNhat === '') $err = 'Vui lòng nhập ngày cập nhật';
  elseif ($GiaBan <= 0) $err = 'Giá bán phải lớn hơn 0';
  else {
    if ($isEdit && $ID > 0) {
      $sql = "UPDATE `$TABLE_GIAXOAI` SET NgayCapNhat=?, MaGiong=?, GiaBan=?, DonViTinh=?, GhiChu=? WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssdssi', $NgayCapNhat, $MaGiong, $GiaBan, $DonViTinh, $GhiChu, $ID);
      if (!$stmt->execute()) {
        $err = "Không cập nhật được: ".$stmt->error;
      } else { 
        $stmt->close();
        safeRedirect("index.php?p=giaxoai", "Đã cập nhật giá xoài ID: $ID");
      }
      $stmt->close();
    } else {
      $sql = "INSERT INTO `$TABLE_GIAXOAI` (NgayCapNhat, MaGiong, GiaBan, DonViTinh, GhiChu) VALUES (?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssdss', $NgayCapNhat, $MaGiong, $GiaBan, $DonViTinh, $GhiChu);
      if (!$stmt->execute()) {
        $err = "Không thêm được: ".$stmt->error;
      } else {
        $newID = $conn->insert_id;
        $stmt->close();
        safeRedirect("index.php?p=giaxoai", "Đã thêm giá xoài mới (ID: $newID)");
      }
      $stmt->close();
    }
  }
}

if (isset($_GET['msg'])) {
  $msg = $_GET['msg'];
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$filterGiong = trim($_GET['filter_giong'] ?? '');

$where = []; $types=''; $params=[];

if ($search !== '') {
  $where[] = "(DonViTinh LIKE ? OR GhiChu LIKE ?)";
  $kw = "%$search%"; 
  $types .= 'ss'; 
  $params = array_merge($params, [$kw, $kw]);
}
if ($filterGiong !== '') {
  $where[] = "MaGiong = ?";
  $types .= 's';
  $params[] = $filterGiong;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM `$TABLE_GIAXOAI` $whereClause ORDER BY NgayCapNhat DESC, ID DESC LIMIT 300";
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

<h1>Quản lý Giá xoài</h1>
<link rel="stylesheet" href="pages/layout/assets/css/giaxoai.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $editData ? 'Chỉnh sửa giá xoài' : 'Thêm giá xoài mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="ID" value="<?= htmlspecialchars($editData['ID']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>ID giá xoài:</strong> <?= htmlspecialchars($editData['ID']) ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Ngày cập nhật *</strong></label>
      <input type="date" name="NgayCapNhat" required value="<?= htmlspecialchars($editData['NgayCapNhat'] ?? date('Y-m-d')) ?>">
    </div>

    <div class="col">
      <label><strong>Giống xoài</strong></label>
      <?php $giongSel = $editData['MaGiong'] ?? ''; ?>
      <select name="MaGiong">
        <option value="">-- Chọn giống xoài --</option>
        <?php foreach ($giongOptions as $ma => $ten): ?>
          <option value="<?= htmlspecialchars($ma) ?>" <?= $giongSel===$ma?'selected':'' ?>>
            <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Giá bán (VNĐ) *</strong></label>
      <input type="number" step="0.01" min="0" name="GiaBan" required value="<?= htmlspecialchars($editData['GiaBan'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Đơn vị tính</strong></label>
      <?php $donViSel = $editData['DonViTinh'] ?? 'kg'; ?>
      <select name="DonViTinh">
        <?php foreach ($donViOptions as $dv): ?>
          <option value="<?= htmlspecialchars($dv) ?>" <?= $donViSel===$dv?'selected':'' ?>><?= htmlspecialchars($dv) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="full">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" placeholder="Nhập ghi chú về giá xoài..."><?= htmlspecialchars($editData['GhiChu'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn" type="submit"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($editData): ?><a class="btn secondary" href="index.php?p=giaxoai">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SEARCH & FILTER -->
<div class="card">
  <form method="get" class="toolbar" style="flex-wrap:wrap">
    <input type="hidden" name="p" value="giaxoai">
    
    <input name="search" placeholder="Tìm theo đơn vị tính, ghi chú..." value="<?= htmlspecialchars($search) ?>" style="min-width:250px">
    
    <select name="filter_giong" style="min-width:180px">
      <option value="">-- Tất cả giống xoài --</option>
      <?php foreach ($giongOptions as $ma => $ten): ?>
        <option value="<?= htmlspecialchars($ma) ?>" <?= $filterGiong===$ma?'selected':'' ?>>
          <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn" type="submit">Tìm kiếm</button>
    <?php if ($search || $filterGiong): ?>
      <a href="index.php?p=giaxoai" class="btn secondary">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>💰 Danh sách giá xoài</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> bản ghi</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Ngày cập nhật</th>
          <th>Giống xoài</th>
          <th>Giá bán</th>
          <th>Đơn vị tính</th>
          <th>Ghi chú</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['ID']) ?></strong></td>
          <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['NgayCapNhat']))) ?></td>
          <td>
            <?php 
            $giongText = $r['MaGiong'];
            if (isset($giongOptions[$r['MaGiong']]) && $giongOptions[$r['MaGiong']] !== $r['MaGiong']) {
              $giongText = $r['MaGiong'] . ' - ' . $giongOptions[$r['MaGiong']];
            }
            echo htmlspecialchars($giongText ?: '-');
            ?>
          </td>
          <td class="number-cell"><?= number_format($r['GiaBan'], 0, ',', '.') ?> VNĐ</td>
          <td><span class="badge user"><?= htmlspecialchars($r['DonViTinh']) ?></span></td>
          <td>
            <?php 
            $ghiChu = $r['GhiChu'] ?? '';
            if (strlen($ghiChu) > 50) {
              echo htmlspecialchars(substr($ghiChu, 0, 50)) . '...';
            } else {
              echo htmlspecialchars($ghiChu ?: '-');
            }
            ?>
          </td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=giaxoai&action=edit&id=<?= urlencode($r['ID']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=giaxoai&action=delete&id=<?= urlencode($r['ID']) ?>"
               title="Xóa" onclick="return confirm('Xóa giá xoài ID \'<?= htmlspecialchars($r['ID']) ?>\' không thể khôi phục?')">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/giaxoai.js"></script>
  </div>
</div>

<?php
ob_end_flush();
?>