<?php
require __DIR__ . '/../../connect.php'; // $conn (mysqli)

/* ========= Helpers ========= */
function u_trim($str, $limit = 60, $suffix='…'){
  $str = (string)$str;
  if (function_exists('mb_strimwidth')) return mb_strimwidth($str, 0, $limit, $suffix, 'UTF-8');
  if ($str === '') return '';
  if (!preg_match_all('/./u', $str, $m)) return $str;
  $chs = $m[0]; if (count($chs) <= $limit) return $str;
  return implode('', array_slice($chs, 0, $limit)) . $suffix;
}
function findTable(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    $t = trim($t, '`');
    $chk = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($t)}'");
    if ($chk && $chk->num_rows) return $t;
  }
  return null;
}

/* ========= Detect tables ========= */
$T_IOT  = findTable($conn, ['thiet_bi_iot','thietbiiot']);
$T_VUNG = findTable($conn, ['vung_trong','vungtrong']);

if (!$T_IOT || !$T_VUNG) {
  echo "<h1>📡 Quản lý Thiết bị IoT</h1>";
  echo "<div class='msg err' style='display:block'>";
  $missing = [];
  if(!$T_IOT)  $missing[]='thiet_bi_iot/thietbiiot';
  if(!$T_VUNG) $missing[]='vung_trong/vungtrong';
  echo "Thiếu bảng: <b>".htmlspecialchars(implode(', ', $missing))."</b>. Vui lòng kiểm tra CSDL.";
  echo "</div>";
  return;
}

/* ========= Options: vùng trồng ========= */
$vungs = [];           // MaVung => label
$res = $conn->query("SELECT MaVung, COALESCE(TenVung, '') AS TenVung, COALESCE(DiaChi,'') AS DiaChi
                     FROM `$T_VUNG` ORDER BY MaVung ASC");
if ($res) while($r = $res->fetch_assoc()){
  $lab = $r['MaVung'];
  if ($r['TenVung'] !== '') $lab .= " — ".$r['TenVung'];
  elseif ($r['DiaChi'] !== '') $lab .= " — ".$r['DiaChi'];
  $vungs[$r['MaVung']] = $lab;
}

/* ========= Tình trạng options ========= */
$tinhTrangOptions = ['Đang hoạt động', 'Ngừng hoạt động', 'Bảo trì'];

/* ========= Actions ========= */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $id = trim($_GET['id'] ?? '');
  if ($id!=='') {
    $stmt = $conn->prepare("DELETE FROM `$T_IOT` WHERE MaThietBi=?");
    $stmt->bind_param('s', $id);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá thiết bị #$id";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $id = trim($_GET['id'] ?? '');
  if ($id!=='') {
    $stmt = $conn->prepare("SELECT * FROM `$T_IOT` WHERE MaThietBi=?");
    $stmt->bind_param('s', $id); $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit) $err = "Không tìm thấy thiết bị #$id";
  }
}

/* ========= Create / Update ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MaThietBi'], $_POST['TenThietBi'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']==='1';
  $MaThietBiOriginal = trim($_POST['MaThietBi_original'] ?? '');

  // fields
  $MaThietBi    = trim($_POST['MaThietBi']    ?? '');
  $TenThietBi   = trim($_POST['TenThietBi']   ?? '');
  $NgayLapDat   = trim($_POST['NgayLapDat']   ?? '') ?: null;
  $TinhTrang    = trim($_POST['TinhTrang']    ?? '') ?: null;
  $MaVung       = trim($_POST['MaVung']       ?? '') ?: null;
  $MaKetNoi     = trim($_POST['MaKetNoi']     ?? '') ?: null;

  // validate
  if ($MaThietBi === '')  $err = 'Vui lòng nhập Mã thiết bị';
  elseif ($TenThietBi === '') $err = 'Vui lòng nhập Tên thiết bị';
  elseif ($MaVung && !isset($vungs[$MaVung])) $err = 'Mã vùng không hợp lệ';
  else {
    if ($isEdit) {
      // Đổi khóa chính -> kiểm tra trùng
      if ($MaThietBi !== $MaThietBiOriginal) {
        $chk = $conn->prepare("SELECT 1 FROM `$T_IOT` WHERE MaThietBi=?");
        $chk->bind_param('s', $MaThietBi);
        $chk->execute();
        $dup = $chk->get_result()->num_rows > 0;
        $chk->close();
        if ($dup) $err = "Mã thiết bị đã tồn tại";
      }
      if (!$err) {
        $sql = "UPDATE `$T_IOT` SET MaThietBi=?, TenThietBi=?, NgayLapDat=?, TinhTrang=?, MaVung=?, MaKetNoi=? WHERE MaThietBi=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssss', $MaThietBi, $TenThietBi, $NgayLapDat, $TinhTrang, $MaVung, $MaKetNoi, $MaThietBiOriginal);
        if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
        else { $msg = "Đã cập nhật thiết bị: $TenThietBi"; $edit=null; }
        $stmt->close();
      }
    } else {
      // Thêm mới
      $chk = $conn->prepare("SELECT 1 FROM `$T_IOT` WHERE MaThietBi=?");
      $chk->bind_param('s', $MaThietBi);
      $chk->execute();
      if ($chk->get_result()->num_rows > 0) $err = "Mã thiết bị đã tồn tại";
      $chk->close();

      if (!$err) {
        $sql = "INSERT INTO `$T_IOT` (MaThietBi, TenThietBi, NgayLapDat, TinhTrang, MaVung, MaKetNoi) VALUES (?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $MaThietBi, $TenThietBi, $NgayLapDat, $TinhTrang, $MaVung, $MaKetNoi);
        if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
        else $msg = "Đã thêm thiết bị mới: $TenThietBi (Mã: $MaThietBi)";
        $stmt->close();
      }
    }
  }
}

/* ========= Filters + List ========= */
$kw     = trim($_GET['search']     ?? '');
$fVung  = trim($_GET['vung']       ?? '');
$fTinhTrang = trim($_GET['tinhtrang'] ?? '');

$where = []; $types=''; $params=[];

if ($kw!=='') {
  $where[] = "(i.MaThietBi LIKE ? OR i.TenThietBi LIKE ? OR i.MaKetNoi LIKE ?)";
  $w = "%$kw%"; $types.='sss'; $params[]=$w; $params[]=$w; $params[]=$w;
}
if ($fVung!=='' && isset($vungs[$fVung])) { $where[] = "i.MaVung=?"; $types.='s'; $params[]=$fVung; }
if ($fTinhTrang!=='') { $where[] = "i.TinhTrang=?"; $types.='s'; $params[]=$fTinhTrang; }

$sqlList = "SELECT i.*, v.TenVung
            FROM `$T_IOT` i
            LEFT JOIN `$T_VUNG` v  ON v.MaVung=i.MaVung";
if ($where) $sqlList .= " WHERE ".implode(' AND ', $where);
$sqlList .= " ORDER BY i.NgayLapDat DESC, i.MaThietBi ASC LIMIT 400";

$list=[];
if ($params){
  $stmt=$conn->prepare($sqlList);
  $stmt->bind_param($types, ...$params); $stmt->execute();
  $list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}else{
  $q=$conn->query($sqlList); if($q) $list=$q->fetch_all(MYSQLI_ASSOC);
}

/* ========= View ========= */
?>
<h1>Quản lý Thiết bị IoT</h1>
<link rel="stylesheet" href="pages/layout/assets/css/thietbiiot.css">

<?php if ($msg): ?><div class="msg ok" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa thiết bị IoT' : 'Thêm thiết bị IoT mới' ?></h3>
  <form method="post" class="row form-3col">
    <?php if ($edit): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="MaThietBi_original" value="<?= htmlspecialchars($edit['MaThietBi']) ?>">
      <div class="muted full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã thiết bị cũ:</strong> <?= htmlspecialchars($edit['MaThietBi']) ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Mã thiết bị *</strong></label>
      <input name="MaThietBi" required placeholder="vd: IOT001" 
             value="<?= htmlspecialchars($edit['MaThietBi'] ?? '') ?>">
    </div>

    <div>
      <label><strong>Tên thiết bị *</strong></label>
      <input name="TenThietBi" required placeholder="vd: Cảm biến độ ẩm"
             value="<?= htmlspecialchars($edit['TenThietBi'] ?? '') ?>">
    </div>

    <div>
      <label><strong>Ngày lắp đặt</strong></label>
      <input type="date" name="NgayLapDat" 
             value="<?= htmlspecialchars($edit['NgayLapDat'] ?? '') ?>">
    </div>

    <div>
      <label><strong>Tình trạng</strong></label>
      <?php $tt = $edit['TinhTrang'] ?? 'Đang hoạt động'; ?>
      <select name="TinhTrang">
        <?php foreach ($tinhTrangOptions as $option): ?>
          <option value="<?= htmlspecialchars($option) ?>" <?= $tt===$option?'selected':'' ?>>
            <?= htmlspecialchars($option) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Vùng trồng</strong></label>
      <?php $mv = $edit['MaVung'] ?? ''; ?>
      <select name="MaVung">
        <option value="">-- Chọn vùng --</option>
        <?php foreach ($vungs as $k=>$lab): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $mv===$k?'selected':'' ?>>
            <?= htmlspecialchars($lab) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Mã kết nối</strong></label>
      <input name="MaKetNoi" placeholder="vd: KT003"
             value="<?= htmlspecialchars($edit['MaKetNoi'] ?? '') ?>">
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=thietbiiot">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- FILTERS -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="thietbiiot">
    <select name="vung">
      <option value="">-- Lọc theo vùng --</option>
      <?php foreach ($vungs as $k=>$lab): ?>
        <option value="<?= htmlspecialchars($k) ?>" <?= $fVung===$k?'selected':'' ?>>
          <?= htmlspecialchars($lab) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="tinhtrang">
      <option value="">-- Lọc theo tình trạng --</option>
      <?php foreach ($tinhTrangOptions as $option): ?>
        <option value="<?= htmlspecialchars($option) ?>" <?= $fTinhTrang===$option?'selected':'' ?>>
          <?= htmlspecialchars($option) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input name="search" id="q" placeholder="Tìm mã thiết bị/tên thiết bị/mã kết nối..." value="<?= htmlspecialchars($kw) ?>">
    <button class="btn">Lọc</button>
    <?php if ($kw!=='' || $fVung!=='' || $fTinhTrang!==''): ?>
      <a class="btn secondary" href="index.php?p=thietbiiot">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách thiết bị IoT</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> thiết bị</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Mã TB</th>
        <th>Tên thiết bị</th>
        <th>Ngày lắp đặt</th>
        <th>Tình trạng</th>
        <th>Vùng</th>
        <th>Mã kết nối</th>
        <th style="width:110px">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <?php
          // Xác định class badge cho tình trạng
          $badgeClass = 'active';
          $tinhTrang = $r['TinhTrang'] ?? '';
          if (str_contains($tinhTrang, 'Ngừng')) $badgeClass = 'inactive';
          elseif (str_contains($tinhTrang, 'Bảo trì')) $badgeClass = 'maintenance';
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['MaThietBi']) ?></strong></td>
          <td><?= htmlspecialchars($r['TenThietBi']) ?></td>
          <td><?= $r['NgayLapDat'] ? htmlspecialchars(date('d/m/Y', strtotime($r['NgayLapDat']))) : '-' ?></td>
          <td>
            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($tinhTrang) ?></span>
          </td>
          <td>
            <?php if ($r['MaVung']): ?>
              <strong><?= htmlspecialchars($r['MaVung']) ?></strong>
              <?php if ($r['TenVung']): ?><div class="muted"><?= htmlspecialchars($r['TenVung']) ?></div><?php endif; ?>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['MaKetNoi'] ?? '-') ?></td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=thietbiiot&action=edit&id=<?= urlencode($r['MaThietBi']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=thietbiiot&action=delete&id=<?= urlencode($r['MaThietBi']) ?>"
               title="Xoá" data-confirm="Xoá thiết bị '<?= htmlspecialchars($r['TenThietBi']) ?>'?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="pages/layout/assets/js/thietbiiot.js"></script>