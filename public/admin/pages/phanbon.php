<?php
require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ===== Helpers ===== */
function u_trim($str, $limit = 60, $suffix='…'){
  $str = (string)$str;
  if (function_exists('mb_strimwidth')) return mb_strimwidth($str, 0, $limit, $suffix, 'UTF-8');
  if ($str === '') return '';
  if (!preg_match_all('/./u', $str, $m)) return $str;
  $chs = $m[0]; if (count($chs) <= $limit) return $str;
  return implode('', array_slice($chs, 0, $limit)).$suffix;
}
function findTable(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) return $t;
  }
  return null;
}
function tableHasCol(mysqli $conn, string $table, string $col): bool {
  $col = $conn->real_escape_string($col);
  $q = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $q && $q->num_rows > 0;
}

/* ===== Table & columns ===== */
$T_PB = findTable($conn, ['phan_bon','phanbon']);
if (!$T_PB) {
  echo "<h1>Quản lý Phân bón</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>phan_bon</code>/<code>phanbon</code> trong CSDL.</div>";
  return;
}

$COLS = [
  'TenPhanBon' => tableHasCol($conn, $T_PB, 'TenPhanBon'),
  'MaPhanBon'  => tableHasCol($conn, $T_PB, 'MaPhanBon'),
  'LoaiPhan'   => tableHasCol($conn, $T_PB, 'LoaiPhan'),
  'ThanhPhan'  => tableHasCol($conn, $T_PB, 'ThanhPhan'), // có cũng được, không có sẽ tự ẩn
  'GhiChu'     => tableHasCol($conn, $T_PB, 'GhiChu'),
];

$PK = $COLS['TenPhanBon'] ? 'TenPhanBon' : ($COLS['MaPhanBon'] ? 'MaPhanBon' : null);
if (!$PK) {
  echo "<h1>Quản lý Phân bón</h1>";
  echo "<div class='msg err' style='display:block'>Không thấy khoá chính (cần <code>TenPhanBon</code> hoặc <code>MaPhanBon</code>).</div>";
  return;
}

/* ===== Options Loại phân (3 loại cố định + merge giá trị đang có trong DB) ===== */
$loaiOpts = ['Hữu cơ','Hóa học','Vi sinh'];
if ($COLS['LoaiPhan']) {
  $q = $conn->query("SELECT DISTINCT LoaiPhan FROM `$T_PB` WHERE LoaiPhan IS NOT NULL AND TRIM(LoaiPhan)<>''");
  if ($q) while($r = $q->fetch_assoc()){
    $v = trim($r['LoaiPhan']);
    if ($v !== '' && !in_array($v, $loaiOpts, true)) $loaiOpts[] = $v;
  }
}

/* ===== Actions ===== */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $id = trim($_GET['id'] ?? '');
  if ($id !== '') {
    $stmt = $conn->prepare("DELETE FROM `$T_PB` WHERE `$PK`=?");
    $stmt->bind_param('s', $id);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá phân bón: $id";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $id = trim($_GET['id'] ?? '');
  if ($id !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$T_PB` WHERE `$PK`=?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__form'])) {
  $isEdit = isset($_POST['__edit']) && $_POST['__edit'] === '1';

  // Giá trị khoá
  $pk_new = trim($_POST[$PK] ?? '');
  $pk_old = $isEdit ? trim($_POST['__pk_old'] ?? '') : '';

  // Thu thập cột thật sự có trong DB
  $vals = [];
  if ($COLS['TenPhanBon']) $vals['TenPhanBon'] = trim($_POST['TenPhanBon'] ?? '');
  if ($COLS['MaPhanBon'])  $vals['MaPhanBon']  = trim($_POST['MaPhanBon'] ?? '');
  if ($COLS['LoaiPhan'])   $vals['LoaiPhan']   = trim($_POST['LoaiPhan'] ?? '') ?: null;
  if ($COLS['ThanhPhan'])  $vals['ThanhPhan']  = trim($_POST['ThanhPhan'] ?? '') ?: null;
  if ($COLS['GhiChu'])     $vals['GhiChu']     = trim($_POST['GhiChu'] ?? '') ?: null;

  // Validate
  if ($pk_new === '') $err = "Vui lòng nhập ".htmlspecialchars($PK);
  if (!$err && $COLS['LoaiPhan'] && empty($vals['LoaiPhan'])) $err = "Vui lòng chọn Loại phân";

  if (!$err) {
    if ($isEdit) {
      // Đổi PK -> check trùng
      if ($pk_new !== $pk_old) {
        $chk = $conn->prepare("SELECT 1 FROM `$T_PB` WHERE `$PK`=?");
        $chk->bind_param('s', $pk_new);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $err = "$PK đã tồn tại";
        $chk->close();
      }
      if (!$err) {
        $set = []; $types=''; $bind=[];
        foreach ($vals as $c=>$v) { $set[] = "`$c`=?"; $types.='s'; $bind[]=$v; }
        $types .= 's'; $bind[] = $pk_old;

        $sql = "UPDATE `$T_PB` SET ".implode(',', $set)." WHERE `$PK`=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$bind);
        if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
        else { $msg = "Đã cập nhật phân bón: $pk_new"; $edit = null; }
        $stmt->close();
      }
    } else {
      $cols = array_keys($vals);
      $ph   = implode(',', array_fill(0, count($cols), '?'));
      $types = str_repeat('s', count($cols));
      $bind  = array_values($vals);

      $sql = "INSERT INTO `$T_PB` (`".implode('`,`',$cols)."`) VALUES ($ph)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$bind);
      if (!$stmt->execute()) {
        if ($conn->errno == 1062) $err = "$PK đã tồn tại";
        else $err = "Không thêm được: ".$stmt->error;
      } else $msg = "Đã thêm phân bón: ".$pk_new;
      $stmt->close();
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where=''; $types=''; $params=[];
if ($search !== '') {
  $likeCols = ["`$PK`"];
  if ($COLS['LoaiPhan'])  $likeCols[] = "LoaiPhan";
  if ($COLS['ThanhPhan']) $likeCols[] = "ThanhPhan";
  if ($COLS['GhiChu'])    $likeCols[] = "GhiChu";
  $likes = implode(' OR ', array_map(fn($c)=>"$c LIKE ?", $likeCols));
  $where = "WHERE ($likes)";
  $kw = "%$search%"; $types = str_repeat('s', count($likeCols)); $params = array_fill(0, count($likeCols), $kw);
}
$sql = "SELECT * FROM `$T_PB` $where ORDER BY `$PK` ASC LIMIT 400";
if ($params){ $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $rows=$stmt->get_result(); $list=$rows->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Phân bón</h1>
<link rel="stylesheet" href="pages/layout/assets/css/nguoidung.css">

<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ===== FORM ===== -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa phân bón' : 'Thêm phân bón mới' ?></h3>

  <form method="post" class="row form-3col">
    <input type="hidden" name="__form" value="1">
    <?php if ($edit): ?>
      <input type="hidden" name="__edit" value="1">
      <input type="hidden" name="__pk_old" value="<?= htmlspecialchars($edit[$PK]) ?>">
    <?php endif; ?>

    <?php if ($COLS['TenPhanBon']): ?>
      <div class="col">
        <label><strong>Tên phân bón <?= $PK==='TenPhanBon'?'*':'' ?></strong></label>
        <input name="TenPhanBon" <?= $PK==='TenPhanBon'?'required':'' ?>
               value="<?= htmlspecialchars($edit['TenPhanBon'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['MaPhanBon']): ?>
      <div class="col">
        <label><strong>Mã phân bón <?= $PK==='MaPhanBon'?'*':'' ?></strong></label>
        <input name="MaPhanBon" <?= $PK==='MaPhanBon'?'required':'' ?>
               value="<?= htmlspecialchars($edit['MaPhanBon'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['LoaiPhan']): ?>
      <div class="col">
        <label><strong>Loại phân *</strong></label>
        <?php $sel = $edit['LoaiPhan'] ?? ''; ?>
        <select name="LoaiPhan" required>
          <option value="">-- Chọn loại --</option>
          <?php foreach ($loaiOpts as $o): ?>
            <option value="<?= htmlspecialchars($o) ?>" <?= $sel===$o?'selected':'' ?>>
              <?= htmlspecialchars($o) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if ($COLS['ThanhPhan']): ?>
      <div class="col">
        <label><strong>Thành phần</strong></label>
        <input name="ThanhPhan" placeholder="N, P, K, hữu cơ…" value="<?= htmlspecialchars($edit['ThanhPhan'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['GhiChu']): ?>
      <div class="full">
        <label><strong>Ghi chú</strong></label>
        <textarea name="GhiChu" rows="3" placeholder="Hướng dẫn, lưu ý sử dụng…"><?= htmlspecialchars($edit['GhiChu'] ?? '') ?></textarea>
      </div>
    <?php endif; ?>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=phanbon">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ===== SEARCH ===== -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="phanbon">
    <input name="search" placeholder="Tìm theo <?= htmlspecialchars($PK) ?>, loại, thành phần, ghi chú…" value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a href="index.php?p=phanbon" class="btn secondary">Xoá lọc</a><?php endif; ?>
  </form>
</div>

<!-- ===== LIST ===== -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách phân bón</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> mục</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><?= htmlspecialchars($PK) ?></th>
          <?php if ($COLS['TenPhanBon'] && $PK!=='TenPhanBon'): ?><th>Tên phân bón</th><?php endif; ?>
          <?php if ($COLS['MaPhanBon']  && $PK!=='MaPhanBon'):  ?><th>Mã phân bón</th><?php endif; ?>
          <?php if ($COLS['LoaiPhan']):   ?><th>Loại phân</th><?php endif; ?>
          <?php if ($COLS['ThanhPhan']):  ?><th>Thành phần</th><?php endif; ?>
          <?php if ($COLS['GhiChu']):     ?><th>Ghi chú</th><?php endif; ?>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r[$PK]) ?></strong></td>
          <?php if ($COLS['TenPhanBon'] && $PK!=='TenPhanBon'): ?><td><?= htmlspecialchars($r['TenPhanBon'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['MaPhanBon']  && $PK!=='MaPhanBon'):  ?><td><?= htmlspecialchars($r['MaPhanBon'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['LoaiPhan']):   ?><td><?= htmlspecialchars($r['LoaiPhan'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['ThanhPhan']):  ?><td><?= htmlspecialchars($r['ThanhPhan'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['GhiChu']):     ?><td title="<?= htmlspecialchars($r['GhiChu'] ?? '') ?>"><?= htmlspecialchars(u_trim($r['GhiChu'] ?? '-', 70)) ?></td><?php endif; ?>
          <td class="actions">
            <a class="btn icon" href="index.php?p=phanbon&action=edit&id=<?= urlencode($r[$PK]) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=phanbon&action=delete&id=<?= urlencode($r[$PK]) ?>" 
               title="Xoá" data-confirm="Xoá phân bón '<?= htmlspecialchars($r[$PK]) ?>'?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/phanbon.js"></script>
  </div>
</div>
