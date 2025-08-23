<?php
ob_start(); // Démarre le buffer de sortie pour éviter l'erreur "headers already sent"

require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ===== Fonction helper pour redirection sécurisée ===== */
function safeRedirect($url, $message = '') {
    if (!headers_sent()) {
        // Si les headers ne sont pas encore envoyés, utiliser header()
        header("Location: $url" . ($message ? "&msg=" . urlencode($message) : ""));
        exit;
    } else {
        // Sinon, utiliser JavaScript
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
$TABLE_BAOCAO = findTable($conn, ['bao_cao_san_luong','baocaosanluong']);
$TABLE_VUNG = findTable($conn, ['vung_trong','vungtrong']);
$TABLE_MUAVU = findTable($conn, ['mua_vu','muavu']);

if (!$TABLE_BAOCAO) {
  echo "<h1>Quản lý Báo cáo sản lượng</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>bao_cao_san_luong</code>/<code>baocaosanluong</code> trong CSDL.</div>";
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

$muaVuOptions = [];
if ($TABLE_MUAVU) {
  // Kiểm tra cột có sẵn trong bảng mua_vu
  $columns = $conn->query("SHOW COLUMNS FROM `$TABLE_MUAVU`");
  $hasName = false;
  $nameColumn = '';
  
  if ($columns) {
    while ($col = $columns->fetch_assoc()) {
      if (in_array(strtolower($col['Field']), ['tenmuavu', 'ten_mua_vu', 'ten', 'name'])) {
        $hasName = true;
        $nameColumn = $col['Field'];
        break;
      }
    }
  }
  
  if ($hasName) {
    $rs = $conn->query("SELECT MaMuaVu, `$nameColumn` as TenMuaVu FROM `$TABLE_MUAVU` ORDER BY `$nameColumn`");
    if ($rs) while ($r = $rs->fetch_assoc()) $muaVuOptions[$r['MaMuaVu']] = $r['TenMuaVu'];
  } else {
    // Nếu không có cột tên, chỉ lấy mã
    $rs = $conn->query("SELECT MaMuaVu FROM `$TABLE_MUAVU` ORDER BY MaMuaVu");
    if ($rs) while ($r = $rs->fetch_assoc()) $muaVuOptions[$r['MaMuaVu']] = $r['MaMuaVu'];
  }
}

$chatLuongOptions = [];
$rs = $conn->query("SELECT DISTINCT ChatLuong FROM `$TABLE_BAOCAO` WHERE ChatLuong IS NOT NULL AND TRIM(ChatLuong)<>''");
if ($rs) while ($r = $rs->fetch_assoc()) $chatLuongOptions[] = $r['ChatLuong'];
if (!in_array('Loại 1', $chatLuongOptions, true)) $chatLuongOptions[] = 'Loại 1';
if (!in_array('Loại 2', $chatLuongOptions, true)) $chatLuongOptions[] = 'Loại 2';
if (!in_array('Loại 3', $chatLuongOptions, true)) $chatLuongOptions[] = 'Loại 3';

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

/* ===== Actions ===== */
if ($action === 'delete') {
  $ID = (int)($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_BAOCAO` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    if (!$stmt->execute()) {
      $err = "Không xóa được: ".$stmt->error;
    } else {
      $stmt->close();
      safeRedirect("index.php?p=baocaosanluong", "Đã xóa báo cáo ID: $ID");
    }
    $stmt->close();
  }
}

if ($action === 'edit') {
  $ID = (int)($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("SELECT * FROM `$TABLE_BAOCAO` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MaVung'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']=='1';
  $ID = (int)($_POST['ID'] ?? 0);

  $MaVung = trim($_POST['MaVung'] ?? '');
  $MaMuaVu = trim($_POST['MaMuaVu'] ?? '');
  $SanLuong = (float)($_POST['SanLuong'] ?? 0);
  $ChatLuong = trim($_POST['ChatLuong'] ?? '') ?: null;
  $GhiChu = trim($_POST['GhiChu'] ?? '') ?: null;

  if ($MaVung === '') $err = 'Vui lòng chọn Vùng trồng';
  elseif ($MaMuaVu === '') $err = 'Vui lòng chọn Mùa vụ';
  elseif ($SanLuong <= 0) $err = 'Sản lượng phải lớn hơn 0';
  else {
    if ($isEdit && $ID > 0) {
      $sql = "UPDATE `$TABLE_BAOCAO` SET MaVung=?, MaMuaVu=?, SanLuong=?, ChatLuong=?, GhiChu=? WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssdssi', $MaVung, $MaMuaVu, $SanLuong, $ChatLuong, $GhiChu, $ID);
      if (!$stmt->execute()) {
        $err = "Không cập nhật được: ".$stmt->error;
      } else { 
        $stmt->close();
        safeRedirect("index.php?p=baocaosanluong", "Đã cập nhật báo cáo ID: $ID");
      }
      $stmt->close();
    } else {
      // Thêm mới
      $sql = "INSERT INTO `$TABLE_BAOCAO` (MaVung, MaMuaVu, SanLuong, ChatLuong, GhiChu) VALUES (?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssdss', $MaVung, $MaMuaVu, $SanLuong, $ChatLuong, $GhiChu);
      if (!$stmt->execute()) {
        $err = "Không thêm được: ".$stmt->error;
      } else {
        $newID = $conn->insert_id;
        $stmt->close();
        safeRedirect("index.php?p=baocaosanluong", "Đã thêm báo cáo mới (ID: $newID)");
      }
      $stmt->close();
    }
  }
}

// Kiểm tra message từ URL (sau khi redirect)
if (isset($_GET['msg'])) {
  $msg = $_GET['msg'];
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$filterVung = trim($_GET['filter_vung'] ?? '');
$filterMuaVu = trim($_GET['filter_muavu'] ?? '');

$where = []; $types=''; $params=[];

if ($search !== '') {
  $where[] = "(ChatLuong LIKE ? OR GhiChu LIKE ?)";
  $kw = "%$search%"; 
  $types .= 'ss'; 
  $params = array_merge($params, [$kw, $kw]);
}
if ($filterVung !== '') {
  $where[] = "MaVung = ?";
  $types .= 's';
  $params[] = $filterVung;
}
if ($filterMuaVu !== '') {
  $where[] = "MaMuaVu = ?";
  $types .= 's';
  $params[] = $filterMuaVu;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM `$TABLE_BAOCAO` $whereClause ORDER BY ID DESC LIMIT 300";
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

<h1>Quản lý Báo cáo sản lượng</h1>
<link rel="stylesheet" href="pages/layout/assets/css/baocaosanluong.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $editData ? 'Chỉnh sửa báo cáo' : 'Thêm báo cáo mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="ID" value="<?= htmlspecialchars($editData['ID']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>ID báo cáo:</strong> <?= htmlspecialchars($editData['ID']) ?>
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
      <label><strong>Mùa vụ *</strong></label>
      <?php $muaVuSel = $editData['MaMuaVu'] ?? ''; ?>
      <select name="MaMuaVu" required>
        <option value="">-- Chọn mùa vụ --</option>
        <?php foreach ($muaVuOptions as $ma => $ten): ?>
          <option value="<?= htmlspecialchars($ma) ?>" <?= $muaVuSel===$ma?'selected':'' ?>>
            <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Sản lượng (tấn) *</strong></label>
      <input type="number" step="0.01" min="0" name="SanLuong" required value="<?= htmlspecialchars($editData['SanLuong'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Chất lượng</strong></label>
      <?php $chatLuongSel = $editData['ChatLuong'] ?? ''; ?>
      <select name="ChatLuong">
        <option value="">-- Chọn chất lượng --</option>
        <?php foreach ($chatLuongOptions as $cl): ?>
          <option value="<?= htmlspecialchars($cl) ?>" <?= $chatLuongSel===$cl?'selected':'' ?>><?= htmlspecialchars($cl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="full">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" placeholder="Nhập ghi chú về báo cáo..."><?= htmlspecialchars($editData['GhiChu'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn" type="submit"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($editData): ?><a class="btn secondary" href="index.php?p=baocaosanluong">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SEARCH & FILTER -->
<div class="card">
  <form method="get" class="toolbar" style="flex-wrap:wrap">
    <input type="hidden" name="p" value="baocaosanluong">
    
    <input name="search" placeholder="Tìm theo chất lượng, ghi chú..." value="<?= htmlspecialchars($search) ?>" style="min-width:250px">
    
    <select name="filter_vung" style="min-width:180px">
      <option value="">-- Tất cả vùng --</option>
      <?php foreach ($vungOptions as $ma => $ten): ?>
        <option value="<?= htmlspecialchars($ma) ?>" <?= $filterVung===$ma?'selected':'' ?>>
          <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
        </option>
      <?php endforeach; ?>
    </select>
    
    <select name="filter_muavu" style="min-width:150px">
      <option value="">-- Tất cả mùa vụ --</option>
      <?php foreach ($muaVuOptions as $ma => $ten): ?>
        <option value="<?= htmlspecialchars($ma) ?>" <?= $filterMuaVu===$ma?'selected':'' ?>>
          <?= htmlspecialchars($ma === $ten ? $ma : "$ma - $ten") ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn" type="submit">Tìm kiếm</button>
    <?php if ($search || $filterVung || $filterMuaVu): ?>
      <a href="index.php?p=baocaosanluong" class="btn secondary">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📊 Danh sách báo cáo sản lượng</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> báo cáo</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Vùng trồng</th>
          <th>Mùa vụ</th>
          <th>Sản lượng (tấn)</th>
          <th>Chất lượng</th>
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
          <td>
            <?php 
            $vungText = $r['MaVung'];
            if (isset($vungOptions[$r['MaVung']]) && $vungOptions[$r['MaVung']] !== $r['MaVung']) {
              $vungText = $r['MaVung'] . ' - ' . $vungOptions[$r['MaVung']];
            }
            echo htmlspecialchars($vungText);
            ?>
          </td>
          <td>
            <?php 
            $muaVuText = $r['MaMuaVu'];
            if (isset($muaVuOptions[$r['MaMuaVu']]) && $muaVuOptions[$r['MaMuaVu']] !== $r['MaMuaVu']) {
              $muaVuText = $r['MaMuaVu'] . ' - ' . $muaVuOptions[$r['MaMuaVu']];
            }
            echo htmlspecialchars($muaVuText);
            ?>
          </td>
          <td><strong><?= number_format($r['SanLuong'], 2) ?></strong></td>
          <td>
            <?php if ($r['ChatLuong']): ?>
              <span class="badge <?= $r['ChatLuong']==='Loại 1'?'loai1':'user' ?>"><?= htmlspecialchars($r['ChatLuong']) ?></span>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
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
            <a class="btn icon" href="index.php?p=baocaosanluong&action=edit&id=<?= urlencode($r['ID']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=baocaosanluong&action=delete&id=<?= urlencode($r['ID']) ?>"
               title="Xóa" onclick="return confirm('Xóa báo cáo ID \'<?= htmlspecialchars($r['ID']) ?>\' không thể khôi phục?')">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/baocaosanluong.js"></script>
  </div>
</div>

<?php
ob_end_flush(); // Vide le buffer de sortie
?>