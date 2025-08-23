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
function genCode(mysqli $conn, string $table, string $col, string $prefix='TB'): string {
  do {
    $code = $prefix . sprintf('%03d', rand(1,999));
    $chk = $conn->query("SELECT 1 FROM `$table` WHERE `$col`='{$conn->real_escape_string($code)}'");
  } while($chk && $chk->num_rows);
  return $code;
}

/* ===== Tables ===== */
$T_TB  = findTable($conn, ['thiet_bi_may_moc','thietbimaymoc']); // bảng chính
$T_HO  = findTable($conn, ['ho_nong_dan','honongdan']);          // để load MaHo
$T_VUNG= findTable($conn, ['vung_trong','vungtrong']);           // để load MaVung

if (!$T_TB) {
  echo "<h1>Quản lý Thiết bị máy móc</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>thiet_bi_may_moc</code>/<code>thietbimaymoc</code> trong CSDL.</div>";
  return;
}

/* ===== Options ===== */
$loaiOpts = ['Thu hoạch','Cắt tỉa','Tưới tiêu','Vận chuyển','Bảo dưỡng']; // có thể merge thêm từ DB
$qLoai = $conn->query("SELECT DISTINCT LoaiThietBi FROM `$T_TB` WHERE LoaiThietBi IS NOT NULL AND TRIM(LoaiThietBi)<>''");
if ($qLoai) while($r=$qLoai->fetch_assoc()){
  $v = trim($r['LoaiThietBi']); if ($v!=='' && !in_array($v,$loaiOpts,true)) $loaiOpts[]=$v;
}
$tinhTrangOpts = ['Tốt','Đang sử dụng','Bảo trì','Hỏng','Đang sửa chữa','Thanh lý'];

$hoOpts = [];
if ($T_HO) {
  // cố thử lấy thêm tên chủ hộ nếu có
  $hasTen = $conn->query("SHOW COLUMNS FROM `$T_HO` LIKE 'TenChuHo'")->num_rows>0;
  $sql = "SELECT MaHo".($hasTen?", TenChuHo":"")." FROM `$T_HO` ORDER BY MaHo ASC LIMIT 1000";
  if ($q=$conn->query($sql)) while($r=$q->fetch_assoc()){
    $hoOpts[] = ['MaHo'=>$r['MaHo'], 'TenChuHo'=>$r['TenChuHo'] ?? null];
  }
}
$vungOpts = [];
if ($T_VUNG) {
  $hasTen = $conn->query("SHOW COLUMNS FROM `$T_VUNG` LIKE 'TenVung'")->num_rows>0;
  $sql = "SELECT MaVung".($hasTen?", TenVung":"")." FROM `$T_VUNG` ORDER BY MaVung ASC LIMIT 1000";
  if ($q=$conn->query($sql)) while($r=$q->fetch_assoc()){
    $vungOpts[] = ['MaVung'=>$r['MaVung'], 'TenVung'=>$r['TenVung'] ?? null];
  }
}

/* ===== Actions ===== */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $id = trim($_GET['id'] ?? '');
  if ($id !== '') {
    $stmt = $conn->prepare("DELETE FROM `$T_TB` WHERE `MaThietBi`=?");
    $stmt->bind_param('s', $id);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá thiết bị: $id";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $id = trim($_GET['id'] ?? '');
  if ($id !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$T_TB` WHERE `MaThietBi`=?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__form'])) {
  $isEdit = isset($_POST['__edit']) && $_POST['__edit']==='1';

  $MaThietBi   = trim($_POST['MaThietBi'] ?? '');
  $TenThietBi  = trim($_POST['TenThietBi'] ?? '');
  $LoaiThietBi = trim($_POST['LoaiThietBi'] ?? '');
  if ($LoaiThietBi === '__NEW__') $LoaiThietBi = trim($_POST['LoaiThietBi_new'] ?? '');
  $LoaiThietBi = ($LoaiThietBi==='') ? null : $LoaiThietBi;

  $NamSuDung   = trim($_POST['NamSuDung'] ?? '');
  $NamSuDung   = ($NamSuDung==='' ? null : $NamSuDung); // dạng YYYY

  $TinhTrang   = trim($_POST['TinhTrang'] ?? '');
  $TinhTrang   = ($TinhTrang==='' ? null : $TinhTrang);

  $MaHo        = trim($_POST['MaHo'] ?? '');
  $MaHo        = ($MaHo==='' ? null : $MaHo);

  $MaVung      = trim($_POST['MaVung'] ?? '');
  $MaVung      = ($MaVung==='' ? null : $MaVung);

  // Validate
  if ($TenThietBi === '') $err = 'Vui lòng nhập Tên thiết bị';
  if (!$isEdit && $MaThietBi==='') {
    // auto-generate nếu để trống khi thêm mới
    $MaThietBi = genCode($conn, $T_TB, 'MaThietBi', 'TB');
  }
  if (!$err && $NamSuDung!==null && !preg_match('/^\d{4}$/', $NamSuDung)) {
    $err = 'Năm sử dụng phải là 4 chữ số (vd: 2024)';
  }

  if (!$err) {
    if ($isEdit) {
      $MaThietBi_old = trim($_POST['__pk_old'] ?? '');

      // Nếu đổi mã -> check trùng
      if ($MaThietBi !== $MaThietBi_old) {
        $chk = $conn->prepare("SELECT 1 FROM `$T_TB` WHERE MaThietBi=?");
        $chk->bind_param('s', $MaThietBi);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $err = "MaThietBi đã tồn tại";
        $chk->close();
      }

      if (!$err) {
        $sql = "UPDATE `$T_TB`
                SET TenThietBi=?, LoaiThietBi=?, NamSuDung=?, TinhTrang=?, MaHo=?, MaVung=?, MaThietBi=?
                WHERE MaThietBi=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssss',
          $TenThietBi, $LoaiThietBi, $NamSuDung, $TinhTrang, $MaHo, $MaVung, $MaThietBi, $MaThietBi_old
        );
        if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
        else { $msg = "Đã cập nhật thiết bị: $MaThietBi"; $edit = null; }
        $stmt->close();
      }
    } else {
      $sql = "INSERT INTO `$T_TB` (MaThietBi, TenThietBi, LoaiThietBi, NamSuDung, TinhTrang, MaHo, MaVung)
              VALUES (?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sssssss',
        $MaThietBi, $TenThietBi, $LoaiThietBi, $NamSuDung, $TinhTrang, $MaHo, $MaVung
      );
      if (!$stmt->execute()) {
        if ($conn->errno == 1062) $err = "MaThietBi đã tồn tại";
        else $err = "Không thêm được: ".$stmt->error;
      } else $msg = "Đã thêm thiết bị: ".$MaThietBi;
      $stmt->close();
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where=''; $types=''; $params=[];
if ($search !== '') {
  $likes = [];
  foreach (['MaThietBi','TenThietBi','LoaiThietBi','TinhTrang','MaHo','MaVung','NamSuDung'] as $c) {
    $likes[] = "$c LIKE ?";
    $types  .= 's';
    $params[] = "%$search%";
  }
  $where = "WHERE (".implode(' OR ', $likes).")";
}
$sql = "SELECT * FROM `$T_TB` $where ORDER BY MaThietBi ASC LIMIT 500";
if ($params){
  $stmt=$conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $rows=$stmt->get_result(); $list=$rows->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[];
}
?>

<link rel="stylesheet" href="pages/layout/assets/css/thietbimaymoc.css">

<h1>Quản lý Thiết bị máy móc</h1>

<?php if ($msg): ?><div class="msg ok" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ===== FORM ===== -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa thiết bị' : 'Thêm thiết bị mới' ?></h3>

  <form method="post" class="row form-3col">
    <input type="hidden" name="__form" value="1">
    <?php if ($edit): ?>
      <input type="hidden" name="__edit" value="1">
      <input type="hidden" name="__pk_old" value="<?= htmlspecialchars($edit['MaThietBi']) ?>">
      <div class="full muted info-bar"><strong>Mã cũ:</strong> <?= htmlspecialchars($edit['MaThietBi']) ?></div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Mã thiết bị *</strong></label>
      <input name="MaThietBi" placeholder="VD: TB123 (để trống sẽ tự sinh khi thêm mới)"
             value="<?= htmlspecialchars($edit['MaThietBi'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Tên thiết bị *</strong></label>
      <input name="TenThietBi" required value="<?= htmlspecialchars($edit['TenThietBi'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Loại thiết bị</strong></label>
      <?php $loai = $edit['LoaiThietBi'] ?? ''; ?>
      <select name="LoaiThietBi" id="selLoai">
        <option value="">-- Chọn loại --</option>
        <?php foreach ($loaiOpts as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= $loai===$o?'selected':'' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
        <option value="__NEW__">Khác (nhập)</option>
      </select>
      <input type="text" name="LoaiThietBi_new" id="inpLoaiNew" placeholder="Nhập loại thiết bị"
             style="display:none;margin-top:8px">
    </div>

    <div class="col">
      <label><strong>Năm sử dụng</strong></label>
      <input name="NamSuDung" placeholder="YYYY" pattern="\d{4}" title="Nhập năm dạng 4 số (vd: 2024)"
             value="<?= htmlspecialchars($edit['NamSuDung'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Tình trạng</strong></label>
      <?php $tt = $edit['TinhTrang'] ?? 'Tốt'; ?>
      <select name="TinhTrang">
        <option value="">-- Chọn tình trạng --</option>
        <?php foreach ($tinhTrangOpts as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= $tt===$o?'selected':'' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Mã hộ</strong></label>
      <?php $mh = $edit['MaHo'] ?? ''; ?>
      <select name="MaHo">
        <option value="">-- Chọn hộ --</option>
        <?php foreach ($hoOpts as $ho): ?>
          <option value="<?= htmlspecialchars($ho['MaHo']) ?>" <?= $mh===$ho['MaHo']?'selected':'' ?>>
            <?= htmlspecialchars($ho['MaHo']) ?><?= $ho['TenChuHo']? ' - '.htmlspecialchars($ho['TenChuHo']):'' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col">
      <label><strong>Mã vùng</strong></label>
      <?php $mv = $edit['MaVung'] ?? ''; ?>
      <select name="MaVung">
        <option value="">-- Chọn vùng --</option>
        <?php foreach ($vungOpts as $v): ?>
          <option value="<?= htmlspecialchars($v['MaVung']) ?>" <?= $mv===$v['MaVung']?'selected':'' ?>>
            <?= htmlspecialchars($v['MaVung']) ?><?= $v['TenVung']? ' - '.htmlspecialchars($v['TenVung']):'' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=thietbimaymoc">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ===== SEARCH ===== -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="thietbimaymoc">
    <input name="search" placeholder="Tìm theo mã/tên, loại, năm, tình trạng, mã hộ, mã vùng…"
           value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a href="index.php?p=thietbimaymoc" class="btn secondary">Xoá lọc</a><?php endif; ?>
  </form>
</div>

<!-- ===== LIST ===== -->
<div class="card">
  <div class="list-head">
    <h3>📋 Danh sách thiết bị</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> mục</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>MaThietBi</th>
          <th>Tên thiết bị</th>
          <th>Loại</th>
          <th>Năm</th>
          <th>Tình trạng</th>
          <th>MaHo</th>
          <th>MaVung</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['MaThietBi']) ?></strong></td>
          <td><?= htmlspecialchars($r['TenThietBi'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['LoaiThietBi'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['NamSuDung'] ?? '-') ?></td>
          <td>
            <?php $tg = $r['TinhTrang'] ?? ''; ?>
            <span class="badge"
              style="background:<?= $tg==='Tốt' || $tg==='Đang sử dụng' ? '#e8f5e9' : ($tg==='Bảo trì' || $tg==='Đang sửa chữa' ? '#fff7e6' : '#ffeaea') ?>;
                     color:<?= $tg==='Tốt' || $tg==='Đang sử dụng' ? '#1b5e20' : ($tg==='Bảo trì' || $tg==='Đang sửa chữa' ? '#8a6100' : '#a31515') ?>;">
              <?= htmlspecialchars($tg ?: '-') ?>
            </span>
          </td>
          <td><?= htmlspecialchars($r['MaHo'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['MaVung'] ?? '-') ?></td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=thietbimaymoc&action=edit&id=<?= urlencode($r['MaThietBi']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=thietbimaymoc&action=delete&id=<?= urlencode($r['MaThietBi']) ?>"
               title="Xoá" data-confirm="Xoá thiết bị '<?= htmlspecialchars($r['MaThietBi']) ?>'?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="pages/layout/assets/js/thietbimaymoc.js"></script>
