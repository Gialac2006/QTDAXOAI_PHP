<?php
require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ===== Helpers ===== */
function u_trim($str, $limit = 40, $suffix = '…'){
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

/* ===== Tables ===== */
$T_VUNG  = findTable($conn, ['vung_trong','vungtrong']);
$T_HO    = findTable($conn, ['ho_nong_dan','honongdan']);
$T_GIONG = findTable($conn, ['giong_xoai','giongxoai']);

if (!$T_VUNG) {
  echo "<h1>Quản lý Vùng trồng</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>vung_trong</code>/<code>vungtrong</code> trong CSDL.</div>";
  return;
}

/* Cột khả dụng (ẩn/hiện form + sinh SQL động) */
$COLS = [
  'MaVung'        => tableHasCol($conn, $T_VUNG, 'MaVung'),
  'MaHo'          => tableHasCol($conn, $T_VUNG, 'MaHo'),
  'MaGiong'       => tableHasCol($conn, $T_VUNG, 'MaGiong'),   // bạn nói đã thay MaMuaVu -> MaGiong
  'TenVung'       => tableHasCol($conn, $T_VUNG, 'TenVung'),
  'DiaChi'        => tableHasCol($conn, $T_VUNG, 'DiaChi'),
  'DiaDiem'       => tableHasCol($conn, $T_VUNG, 'DiaDiem'),
  'DienTich'      => tableHasCol($conn, $T_VUNG, 'DienTich'),
  'NgayBatDau'    => tableHasCol($conn, $T_VUNG, 'NgayBatDau'),
  'NgayKetThuc'   => tableHasCol($conn, $T_VUNG, 'NgayKetThuc'),
  'GhiChu'        => tableHasCol($conn, $T_VUNG, 'GhiChu'),
];
$COL_DIACHI = $COLS['DiaChi'] ? 'DiaChi' : ($COLS['DiaDiem'] ? 'DiaDiem' : null);

/* ===== Options: Hộ & Giống ===== */
$hoOpts = [];
if ($T_HO && $COLS['MaHo']) {
  $q = $conn->query("SELECT MaHo, TenChuHo FROM `$T_HO` ORDER BY TenChuHo");
  if ($q) while($r=$q->fetch_assoc()) $hoOpts[]=$r;
}
$giongOpts = [];
if ($T_GIONG && $COLS['MaGiong']) {
  $q = $conn->query("SELECT MaGiong, TenGiong FROM `$T_GIONG` ORDER BY TenGiong");
  if ($q) while($r=$q->fetch_assoc()) $giongOpts[]=$r;
}

/* ===== Actions ===== */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $MaVung = trim($_GET['id'] ?? '');
  if ($MaVung !== '' && $COLS['MaVung']) {
    $stmt = $conn->prepare("DELETE FROM `$T_VUNG` WHERE MaVung=?");
    $stmt->bind_param('s', $MaVung);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá vùng: $MaVung";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $MaVung = trim($_GET['id'] ?? '');
  if ($MaVung !== '' && $COLS['MaVung']) {
    $stmt = $conn->prepare("SELECT * FROM `$T_VUNG` WHERE MaVung=?");
    $stmt->bind_param('s', $MaVung);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update (SQL động theo cột có thật) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__form'])) {
  $isEdit = !empty($_POST['MaVung']);
  $MaVung = trim($_POST['MaVung'] ?? '');

  // Thu thập dữ liệu theo cột thực tế
  $vals = [];
  if ($COLS['TenVung'])     $vals['TenVung']     = trim($_POST['TenVung'] ?? '') ?: null;
  if ($COLS['MaHo'])        $vals['MaHo']        = trim($_POST['MaHo'] ?? '') ?: null;
  if ($COLS['MaGiong'])     $vals['MaGiong']     = trim($_POST['MaGiong'] ?? '') ?: null;
  if ($COL_DIACHI)          $vals[$COL_DIACHI]   = trim($_POST[$COL_DIACHI] ?? '') ?: null;
  if ($COLS['DienTich'])    $vals['DienTich']    = ($_POST['DienTich'] === '' ? null : (float)$_POST['DienTich']);
  if ($COLS['NgayBatDau'])  $vals['NgayBatDau']  = trim($_POST['NgayBatDau'] ?? '') ?: null;
  if ($COLS['NgayKetThuc']) $vals['NgayKetThuc'] = trim($_POST['NgayKetThuc'] ?? '') ?: null;
  if ($COLS['GhiChu'])      $vals['GhiChu']      = trim($_POST['GhiChu'] ?? '') ?: null;

  // Validate cơ bản
  if ($COLS['MaHo'] && empty($vals['MaHo']))      $err = 'Vui lòng chọn Hộ nông dân';
  if (!$err && $COLS['MaGiong'] && empty($vals['MaGiong'])) $err = 'Vui lòng chọn Giống xoài';

  if (!$err) {
    if ($isEdit && $COLS['MaVung']) {
      // UPDATE
      $set = []; $types=''; $bind=[];
      foreach ($vals as $c=>$v) {
        $set[] = "`$c`=?";
        if ($c==='DienTich') $types.='d'; else $types.='s';
        $bind[] = $v;
      }
      $types .= 's'; // for WHERE MaVung
      $bind[] = $MaVung;

      $sql = "UPDATE `$T_VUNG` SET ".implode(',', $set)." WHERE MaVung=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$bind);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật vùng: $MaVung"; $edit = null; }
      $stmt->close();
    } else {
      // INSERT -> tạo mã VT001..VT999 nếu có cột MaVung
      if ($COLS['MaVung']) {
        do {
          $gen = 'VT'.sprintf('%03d', rand(1,999));
          $dup = $conn->query("SELECT MaVung FROM `$T_VUNG` WHERE MaVung='{$conn->real_escape_string($gen)}'");
        } while ($dup && $dup->num_rows);
        $MaVung = $gen;
        $vals = array_merge(['MaVung'=>$MaVung], $vals);
      }
      $cols = array_keys($vals);
      $ph   = implode(',', array_fill(0, count($cols), '?'));
      $types=''; $bind=[];
      foreach ($cols as $c) { $types .= ($c==='DienTich' ? 'd' : 's'); $bind[] = $vals[$c]; }
      $sql = "INSERT INTO `$T_VUNG` (`".implode('`,`',$cols)."`) VALUES ($ph)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$bind);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm vùng: ".($COLS['MaVung']?$MaVung:'(mới)');
      $stmt->close();
    }
  }
}

/* ===== Search + List (JOIN nếu có FK) ===== */
$search = trim($_GET['search'] ?? '');
$joins = ''; $selectMore = ''; $where = ''; $types=''; $params=[];
if ($T_HO && $COLS['MaHo'])    { $joins .= " LEFT JOIN `$T_HO` h ON v.MaHo=h.MaHo"; $selectMore .= ", h.TenChuHo"; }
if ($T_GIONG && $COLS['MaGiong']) { $joins .= " LEFT JOIN `$T_GIONG` g ON v.MaGiong=g.MaGiong"; $selectMore .= ", g.TenGiong"; }

if ($search !== '') {
  $likeCols = ["v.MaVung"];
  if ($COL_DIACHI) $likeCols[] = "v.`$COL_DIACHI`";
  if ($COLS['GhiChu']) $likeCols[] = "v.GhiChu";
  if (strpos($selectMore,'TenChuHo')!==false) $likeCols[] = "h.TenChuHo";
  if (strpos($selectMore,'TenGiong')!==false) $likeCols[] = "g.TenGiong";
  $likes = implode(' OR ', array_map(fn($c)=>"$c LIKE ?", $likeCols));
  $where = "WHERE ($likes)";
  $kw = "%$search%"; $types = str_repeat('s', count($likeCols)); $params = array_fill(0, count($likeCols), $kw);
}

$list = [];
$sql = "SELECT v.* $selectMore FROM `$T_VUNG` v $joins $where ORDER BY v.MaVung DESC LIMIT 300";
if ($params){ $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $rows=$stmt->get_result(); $list=$rows->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Vùng trồng</h1>
<link rel="stylesheet" href="pages/layout/assets/css/vungtrong.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ===== FORM ===== -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa vùng trồng' : 'Thêm vùng trồng mới' ?></h3>

  <form method="post" class="row form-3col">
    <input type="hidden" name="__form" value="1">
    <?php if ($edit && $COLS['MaVung']): ?>
      <input type="hidden" name="MaVung" value="<?= htmlspecialchars($edit['MaVung']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã vùng:</strong> <?= htmlspecialchars($edit['MaVung']) ?>
      </div>
    <?php endif; ?>

    <?php if ($COLS['TenVung']): ?>
      <div class="col">
        <label><strong>Tên vùng</strong></label>
        <input name="TenVung" value="<?= htmlspecialchars($edit['TenVung'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['MaHo']): ?>
      <div class="col">
        <label><strong>Hộ nông dân *</strong></label>
        <?php $selHo = $edit['MaHo'] ?? ''; ?>
        <select name="MaHo" required>
          <option value="">-- Chọn hộ --</option>
          <?php foreach ($hoOpts as $o): ?>
            <option value="<?= htmlspecialchars($o['MaHo']) ?>" <?= $selHo===$o['MaHo']?'selected':'' ?>>
              <?= htmlspecialchars($o['MaHo'].' - '.$o['TenChuHo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if ($COLS['MaGiong']): ?>
      <div class="col">
        <label><strong>Giống xoài *</strong></label>
        <?php $selG = $edit['MaGiong'] ?? ''; ?>
        <select name="MaGiong" required>
          <option value="">-- Chọn giống --</option>
          <?php foreach ($giongOpts as $o): ?>
            <option value="<?= htmlspecialchars($o['MaGiong']) ?>" <?= $selG===$o['MaGiong']?'selected':'' ?>>
              <?= htmlspecialchars($o['MaGiong'].' - '.$o['TenGiong']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if ($COL_DIACHI): ?>
      <div class="full">
        <label><strong>Địa chỉ</strong></label>
        <input name="<?= $COL_DIACHI ?>" value="<?= htmlspecialchars($edit[$COL_DIACHI] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['DienTich']): ?>
      <div class="col">
        <label><strong>Diện tích (m²)</strong></label>
        <input type="number" step="0.1" min="0" name="DienTich" value="<?= htmlspecialchars($edit['DienTich'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['NgayBatDau']): ?>
      <div class="col">
        <label><strong>Ngày bắt đầu</strong></label>
        <input type="date" name="NgayBatDau" value="<?= htmlspecialchars($edit['NgayBatDau'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['NgayKetThuc']): ?>
      <div class="col">
        <label><strong>Ngày kết thúc</strong></label>
        <input type="date" name="NgayKetThuc" value="<?= htmlspecialchars($edit['NgayKetThuc'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <?php if ($COLS['GhiChu']): ?>
      <div class="full">
        <label><strong>Ghi chú</strong></label>
        <textarea name="GhiChu" rows="3" placeholder="Thông tin thêm..."><?= htmlspecialchars($edit['GhiChu'] ?? '') ?></textarea>
      </div>
    <?php endif; ?>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=vungtrong">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ===== SEARCH ===== -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="vungtrong">
    <input name="search" placeholder="Tìm theo mã vùng, hộ, giống, địa chỉ..." value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a class="btn secondary" href="index.php?p=vungtrong">Xóa lọc</a><?php endif; ?>
  </form>
</div>

<!-- ===== LIST ===== -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách vùng trồng</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> vùng</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <?php if ($COLS['MaVung']): ?><th>Mã vùng</th><?php endif; ?>
          <?php if ($COLS['TenVung']): ?><th>Tên vùng</th><?php endif; ?>
          <?php if ($COLS['MaHo']): ?><th>Hộ nông dân</th><?php endif; ?>
          <?php if ($COLS['MaGiong']): ?><th>Giống xoài</th><?php endif; ?>
          <?php if ($COL_DIACHI): ?><th>Địa chỉ</th><?php endif; ?>
          <?php if ($COLS['DienTich']): ?><th>Diện tích</th><?php endif; ?>
          <?php if ($COLS['NgayBatDau']): ?><th>Bắt đầu</th><?php endif; ?>
          <?php if ($COLS['NgayKetThuc']): ?><th>Kết thúc</th><?php endif; ?>
          <?php if ($COLS['GhiChu']): ?><th>Ghi chú</th><?php endif; ?>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="11" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <?php if ($COLS['MaVung']): ?><td><strong><?= htmlspecialchars($r['MaVung']) ?></strong></td><?php endif; ?>
          <?php if ($COLS['TenVung']): ?><td><?= htmlspecialchars($r['TenVung'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['MaHo']): ?>
            <td><?= htmlspecialchars(($r['MaHo'] ?? '-') . (isset($r['TenChuHo']) ? ' - '.$r['TenChuHo'] : '')) ?></td>
          <?php endif; ?>
          <?php if ($COLS['MaGiong']): ?>
            <td><?= htmlspecialchars(($r['MaGiong'] ?? '-') . (isset($r['TenGiong']) ? ' - '.$r['TenGiong'] : '')) ?></td>
          <?php endif; ?>
          <?php if ($COL_DIACHI): ?><td title="<?= htmlspecialchars($r[$COL_DIACHI] ?? '') ?>"><?= htmlspecialchars(u_trim($r[$COL_DIACHI] ?? '-', 30)) ?></td><?php endif; ?>
          <?php if ($COLS['DienTich']): ?><td style="text-align:right"><?= ($r['DienTich']!==null && $r['DienTich']!=='') ? number_format((float)$r['DienTich'],1).' m²' : '-' ?></td><?php endif; ?>
          <?php if ($COLS['NgayBatDau']): ?><td><?= htmlspecialchars($r['NgayBatDau'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['NgayKetThuc']): ?><td><?= htmlspecialchars($r['NgayKetThuc'] ?? '-') ?></td><?php endif; ?>
          <?php if ($COLS['GhiChu']): ?><td title="<?= htmlspecialchars($r['GhiChu'] ?? '') ?>"><?= htmlspecialchars(u_trim($r['GhiChu'] ?? '-', 30)) ?></td><?php endif; ?>
          <td class="actions">
            <?php if ($COLS['MaVung']): ?>
              <a class="btn icon" href="index.php?p=vungtrong&action=edit&id=<?= urlencode($r['MaVung']) ?>" title="Sửa">✏️</a>
              <a class="btn icon danger" href="index.php?p=vungtrong&action=delete&id=<?= urlencode($r['MaVung']) ?>" title="Xoá" data-confirm="Xoá vùng '<?= htmlspecialchars($r['MaVung']) ?>'?">🗑️</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/vungtrong.js"></script>

  </div>
</div>
