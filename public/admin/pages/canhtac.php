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
$T_CANH = findTable($conn, ['canh_tac','canhtac']);
$T_VUNG = findTable($conn, ['vung_trong','vungtrong']);
$T_PB   = findTable($conn, ['phan_bon','phanbon']);

if (!$T_CANH || !$T_VUNG || !$T_PB) {
  echo "<h1>🌱 Quản lý Canh tác</h1>";
  echo "<div class='msg err' style='display:block'>";
  $missing = [];
  if(!$T_CANH) $missing[]='canh_tac/canhtac';
  if(!$T_VUNG) $missing[]='vung_trong/vungtrong';
  if(!$T_PB)   $missing[]='phan_bon/phanbon';
  echo "Thiếu bảng: <b>".htmlspecialchars(implode(', ', $missing))."</b>. Vui lòng kiểm tra CSDL.";
  echo "</div>";
  return;
}

/* ========= Options: vùng trồng & phân bón ========= */
$vungs = [];           // MaVung => label
$res = $conn->query("SELECT MaVung, COALESCE(TenVung, '') AS TenVung, COALESCE(DiaChi,'') AS DiaChi
                     FROM `$T_VUNG` ORDER BY MaVung ASC");
if ($res) while($r = $res->fetch_assoc()){
  $lab = $r['MaVung'];
  if ($r['TenVung'] !== '') $lab .= " — ".$r['TenVung'];
  elseif ($r['DiaChi'] !== '') $lab .= " — ".$r['DiaChi'];
  $vungs[$r['MaVung']] = $lab;
}

$phanBons = [];        // TenPhanBon => ['label'=>..., 'DonViTinh'=>...]
$res = $conn->query("SELECT TenPhanBon, COALESCE(DonViTinh,'') AS DonViTinh, COALESCE(Loai,'') AS Loai
                     FROM `$T_PB` ORDER BY TenPhanBon ASC");
if ($res) while($r = $res->fetch_assoc()){
  $lab = $r['TenPhanBon'];
  if ($r['DonViTinh']!=='') $lab .= " (".$r['DonViTinh'].")";
  $phanBons[$r['TenPhanBon']] = ['label'=>$lab, 'DonViTinh'=>$r['DonViTinh'], 'Loai'=>$r['Loai']];
}

/* ========= Actions ========= */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id>0) {
    $stmt = $conn->prepare("DELETE FROM `$T_CANH` WHERE ID=?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá bản ghi #$id";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id>0) {
    $stmt = $conn->prepare("SELECT * FROM `$T_CANH` WHERE ID=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit) $err = "Không tìm thấy bản ghi #$id";
  }
}

/* ========= Create / Update ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MaVung'], $_POST['TenPhanBon'])) {
  $isEdit = isset($_POST['ID']) && ctype_digit($_POST['ID']);
  $ID     = $isEdit ? (int)$_POST['ID'] : 0;

  // fields
  $Ngay = trim($_POST['NgayThucHien'] ?? '');
  // input type="datetime-local" => Y-m-d\TH:i  -> convert
  $NgaySQL = $Ngay ? date('Y-m-d H:i:s', strtotime($Ngay)) : null;

  $LoaiCongViec   = trim($_POST['LoaiCongViec']   ?? '');
  $NguoiThucHien  = trim($_POST['NguoiThucHien']  ?? '');
  $MaVung         = trim($_POST['MaVung']         ?? '');
  $TenPhanBon     = trim($_POST['TenPhanBon']     ?? '');
  $LieuLuong      = ($_POST['LieuLuong'] ?? '') === '' ? null : (float)$_POST['LieuLuong'];
  $GhiChu         = trim($_POST['GhiChu']         ?? '');

  // validate FK
  if (!isset($vungs[$MaVung]))       $err = 'Mã vùng không hợp lệ';
  elseif (!isset($phanBons[$TenPhanBon])) $err = 'Tên phân bón không hợp lệ';
  elseif (!$NgaySQL)                  $err = 'Vui lòng chọn Ngày thực hiện';
  else {
    if ($isEdit && $ID>0) {
      $sql = "UPDATE `$T_CANH`
              SET NgayThucHien=?, LoaiCongViec=?, NguoiThucHien=?, MaVung=?, TenPhanBon=?, LieuLuong=?, GhiChu=?
              WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sssssdsi', $NgaySQL, $LoaiCongViec, $NguoiThucHien, $MaVung, $TenPhanBon, $LieuLuong, $GhiChu, $ID);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật bản ghi #$ID"; $edit=null; }
      $stmt->close();
    } else {
      $sql = "INSERT INTO `$T_CANH` (NgayThucHien, LoaiCongViec, NguoiThucHien, MaVung, TenPhanBon, LieuLuong, GhiChu)
              VALUES (?,?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sssssds', $NgaySQL, $LoaiCongViec, $NguoiThucHien, $MaVung, $TenPhanBon, $LieuLuong, $GhiChu);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm canh tác mới (#".$stmt->insert_id.")";
      $stmt->close();
    }
  }
}

/* ========= Filters + List ========= */
$kw    = trim($_GET['search'] ?? '');
$fVung = trim($_GET['vung']   ?? '');
$fPB   = trim($_GET['pb']     ?? '');

$where = []; $types=''; $params=[];

if ($kw!=='') {
  $where[] = "(c.LoaiCongViec LIKE ? OR c.NguoiThucHien LIKE ? OR c.GhiChu LIKE ?)";
  $w = "%$kw%"; $types.='sss'; $params[]=$w; $params[]=$w; $params[]=$w;
}
if ($fVung!=='' && isset($vungs[$fVung])) { $where[] = "c.MaVung=?"; $types.='s'; $params[]=$fVung; }
if ($fPB!==''   && isset($phanBons[$fPB])) { $where[] = "c.TenPhanBon=?"; $types.='s'; $params[]=$fPB; }

$sqlList = "SELECT c.*, v.TenVung, p.DonViTinh
            FROM `$T_CANH` c
            LEFT JOIN `$T_VUNG` v  ON v.MaVung=c.MaVung
            LEFT JOIN `$T_PB`   p  ON p.TenPhanBon=c.TenPhanBon";
if ($where) $sqlList .= " WHERE ".implode(' AND ', $where);
$sqlList .= " ORDER BY c.NgayThucHien DESC, c.ID DESC LIMIT 400";

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
<h1>Quản lý Canh tác</h1>
<link rel="stylesheet" href="pages/layout/assets/css/canhtac.css">

<?php if ($msg): ?><div class="msg ok" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa canh tác' : 'Thêm canh tác mới' ?></h3>
  <form method="post" class="row form-3col">
    <?php if ($edit): ?>
      <input type="hidden" name="ID" value="<?= (int)$edit['ID'] ?>">
      <div class="muted full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã bản ghi:</strong> #<?= (int)$edit['ID'] ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Ngày thực hiện *</strong></label>
      <?php
        $dt = $edit['NgayThucHien'] ?? '';
        $valDT = $dt ? date('Y-m-d\TH:i', strtotime($dt)) : '';
      ?>
      <input type="datetime-local" name="NgayThucHien" required value="<?= htmlspecialchars($valDT) ?>">
    </div>

    <div>
      <label><strong>Loại công việc</strong></label>
      <input name="LoaiCongViec" placeholder="vd: Bón phân / Tưới / Làm cỏ..." value="<?= htmlspecialchars($edit['LoaiCongViec'] ?? '') ?>">
    </div>

    <div>
      <label><strong>Người thực hiện</strong></label>
      <input name="NguoiThucHien" value="<?= htmlspecialchars($edit['NguoiThucHien'] ?? '') ?>">
    </div>

    <div>
      <label><strong>Vùng trồng *</strong></label>
      <?php $mv = $edit['MaVung'] ?? ''; ?>
      <select name="MaVung" required>
        <option value="">-- Chọn vùng --</option>
        <?php foreach ($vungs as $k=>$lab): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $mv===$k?'selected':'' ?>>
            <?= htmlspecialchars($lab) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Phân bón *</strong></label>
      <?php $pb = $edit['TenPhanBon'] ?? ''; ?>
      <select name="TenPhanBon" required>
        <option value="">-- Chọn phân bón --</option>
        <?php foreach ($phanBons as $k=>$row): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $pb===$k?'selected':'' ?>>
            <?= htmlspecialchars($row['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><strong>Liều lượng</strong></label>
      <input type="number" step="0.01" min="0" name="LieuLuong" placeholder="vd: 20"
             value="<?= htmlspecialchars($edit['LieuLuong'] ?? '') ?>">
    </div>

    <div class="full">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" rows="3" placeholder="Mô tả/ghi chú..."><?= htmlspecialchars($edit['GhiChu'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=canhtac">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- FILTERS -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="canhtac">
    <select name="vung">
      <option value="">-- Lọc theo vùng --</option>
      <?php foreach ($vungs as $k=>$lab): ?>
        <option value="<?= htmlspecialchars($k) ?>" <?= $fVung===$k?'selected':'' ?>>
          <?= htmlspecialchars($lab) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="pb">
      <option value="">-- Lọc theo phân bón --</option>
      <?php foreach ($phanBons as $k=>$row): ?>
        <option value="<?= htmlspecialchars($k) ?>" <?= $fPB===$k?'selected':'' ?>>
          <?= htmlspecialchars($row['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input name="search" id="q" placeholder="Tìm công việc/người thực hiện/ghi chú..." value="<?= htmlspecialchars($kw) ?>">
    <button class="btn">Lọc</button>
    <?php if ($kw!=='' || $fVung!=='' || $fPB!==''): ?>
      <a class="btn secondary" href="index.php?p=canhtac">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách canh tác</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> bản ghi</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>#</th>
        <th>Ngày</th>
        <th>Vùng</th>
        <th>Công việc</th>
        <th>Phân bón</th>
        <th>Liều</th>
        <th>Người TH</th>
        <th>Ghi chú</th>
        <th style="width:110px">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="9" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td>#<?= (int)$r['ID'] ?></td>
          <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['NgayThucHien']))) ?></td>
          <td>
            <strong><?= htmlspecialchars($r['MaVung']) ?></strong>
            <div class="muted"><?= htmlspecialchars($r['TenVung'] ?? '') ?></div>
          </td>
          <td><?= htmlspecialchars($r['LoaiCongViec'] ?? '-') ?></td>
          <td>
            <strong><?= htmlspecialchars($r['TenPhanBon']) ?></strong>
            <div class="muted"><?= htmlspecialchars($r['DonViTinh'] ? 'ĐVT: '.$r['DonViTinh'] : '') ?></div>
          </td>
          <td style="text-align:right"><?= $r['LieuLuong']!==null ? rtrim(rtrim(number_format((float)$r['LieuLuong'],2,'.',''), '0'),'.') : '-' ?></td>
          <td><?= htmlspecialchars($r['NguoiThucHien'] ?? '-') ?></td>
          <td title="<?= htmlspecialchars($r['GhiChu'] ?? '') ?>"><?= htmlspecialchars(u_trim($r['GhiChu'] ?? '-', 50)) ?></td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=canhtac&action=edit&id=<?= (int)$r['ID'] ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=canhtac&action=delete&id=<?= (int)$r['ID'] ?>"
               title="Xoá" data-confirm="Xoá bản ghi #<?= (int)$r['ID'] ?>?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="pages/layout/assets/js/canhtac.js"></script>