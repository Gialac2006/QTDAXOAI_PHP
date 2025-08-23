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
$T_PHUN = findTable($conn, ['nhat_ky_phun_thuoc','nhatkyphunthuoc']);
$T_VUNG = findTable($conn, ['vung_trong','vungtrong']);
$T_THUOC = findTable($conn, ['thuoc_bvtv','thuocbvtv']); // Có thể có bảng thuốc riêng

if (!$T_PHUN) {
  echo "<h1>💉 Quản lý Nhật ký phun thuốc</h1>";
  echo "<div class='msg err' style='display:block'>";
  echo "Thiếu bảng: <b>nhat_ky_phun_thuoc/nhatkyphunthuoc</b>. Vui lòng kiểm tra CSDL.";
  echo "</div>";
  return;
}

/* ========= Options: vùng trồng ========= */
$vungs = [];           // MaVung => label
if ($T_VUNG) {
  $res = $conn->query("SELECT MaVung, COALESCE(TenVung, '') AS TenVung, COALESCE(DiaChi,'') AS DiaChi
                       FROM `$T_VUNG` ORDER BY MaVung ASC");
  if ($res) while($r = $res->fetch_assoc()){
    $lab = $r['MaVung'];
    if ($r['TenVung'] !== '') $lab .= " — ".$r['TenVung'];
    elseif ($r['DiaChi'] !== '') $lab .= " — ".$r['DiaChi'];
    $vungs[$r['MaVung']] = $lab;
  }
}

/* ========= Options: thuốc (từ bảng riêng hoặc từ dữ liệu hiện có) ========= */
$thuocs = [];
if ($T_THUOC) {
  // Nếu có bảng thuốc riêng
  $res = $conn->query("SELECT TenThuoc FROM `$T_THUOC` ORDER BY TenThuoc ASC");
  if ($res) while($r = $res->fetch_assoc()){
    $thuocs[] = $r['TenThuoc'];
  }
} else {
  // Lấy từ dữ liệu có sẵn trong bảng phun thuốc
  $res = $conn->query("SELECT DISTINCT TenThuoc FROM `$T_PHUN` WHERE TenThuoc IS NOT NULL AND TRIM(TenThuoc)<>'' ORDER BY TenThuoc ASC");
  if ($res) while($r = $res->fetch_assoc()){
    $thuocs[] = $r['TenThuoc'];
  }
}

/* ========= Actions ========= */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id>0) {
    $stmt = $conn->prepare("DELETE FROM `$T_PHUN` WHERE ID=?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá bản ghi #$id";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id>0) {
    $stmt = $conn->prepare("SELECT * FROM `$T_PHUN` WHERE ID=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit) $err = "Không tìm thấy bản ghi #$id";
  }
}

/* ========= Create / Update ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['NgayPhun'])) {
  $isEdit = isset($_POST['ID']) && ctype_digit($_POST['ID']);
  $ID     = $isEdit ? (int)$_POST['ID'] : 0;

  // fields
  $NgayPhun      = trim($_POST['NgayPhun']     ?? '') ?: null;
  $TenNguoiPhun  = trim($_POST['TenNguoiPhun'] ?? '') ?: null;
  $MaVung        = trim($_POST['MaVung']       ?? '') ?: null;
  $TenThuoc      = trim($_POST['TenThuoc']     ?? '') ?: null;
  $LieuLuong     = ($_POST['LieuLuong'] ?? '') === '' ? null : (float)$_POST['LieuLuong'];
  $GhiChu        = trim($_POST['GhiChu']       ?? '') ?: null;

  // validate
  if (!$NgayPhun) $err = 'Vui lòng chọn Ngày phun';
  elseif ($MaVung && $T_VUNG && !isset($vungs[$MaVung])) $err = 'Mã vùng không hợp lệ';
  else {
    if ($isEdit && $ID>0) {
      $sql = "UPDATE `$T_PHUN`
              SET NgayPhun=?, TenNguoiPhun=?, MaVung=?, TenThuoc=?, LieuLuong=?, GhiChu=?
              WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssssdsi', $NgayPhun, $TenNguoiPhun, $MaVung, $TenThuoc, $LieuLuong, $GhiChu, $ID);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật bản ghi #$ID"; $edit=null; }
      $stmt->close();
    } else {
      $sql = "INSERT INTO `$T_PHUN` (NgayPhun, TenNguoiPhun, MaVung, TenThuoc, LieuLuong, GhiChu)
              VALUES (?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssssds', $NgayPhun, $TenNguoiPhun, $MaVung, $TenThuoc, $LieuLuong, $GhiChu);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm nhật ký phun thuốc mới (#".$stmt->insert_id.")";
      $stmt->close();
    }
  }
}

/* ========= Filters + List ========= */
$kw     = trim($_GET['search'] ?? '');
$fVung  = trim($_GET['vung']   ?? '');
$fThuoc = trim($_GET['thuoc']  ?? '');

$where = []; $types=''; $params=[];

if ($kw!=='') {
  $where[] = "(p.TenNguoiPhun LIKE ? OR p.TenThuoc LIKE ? OR p.GhiChu LIKE ?)";
  $w = "%$kw%"; $types.='sss'; $params[]=$w; $params[]=$w; $params[]=$w;
}
if ($fVung!=='' && ($T_VUNG && isset($vungs[$fVung]))) { $where[] = "p.MaVung=?"; $types.='s'; $params[]=$fVung; }
if ($fThuoc!=='') { $where[] = "p.TenThuoc=?"; $types.='s'; $params[]=$fThuoc; }

$sqlList = "SELECT p.*";
if ($T_VUNG) $sqlList .= ", v.TenVung";
$sqlList .= " FROM `$T_PHUN` p";
if ($T_VUNG) $sqlList .= " LEFT JOIN `$T_VUNG` v ON v.MaVung=p.MaVung";
if ($where) $sqlList .= " WHERE ".implode(' AND ', $where);
$sqlList .= " ORDER BY p.NgayPhun DESC, p.ID DESC LIMIT 400";

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
<h1>Quản lý Nhật ký phun thuốc</h1>
<link rel="stylesheet" href="pages/layout/assets/css/nhatkyphunthuoc.css">

<?php if ($msg): ?><div class="msg ok" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa nhật ký phun thuốc' : 'Thêm nhật ký phun thuốc mới' ?></h3>
  <form method="post" class="row form-3col">
    <?php if ($edit): ?>
      <input type="hidden" name="ID" value="<?= (int)$edit['ID'] ?>">
      <div class="muted full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã bản ghi:</strong> #<?= (int)$edit['ID'] ?>
      </div>
    <?php endif; ?>

    <div>
      <label><strong>Ngày phun *</strong></label>
      <input type="date" name="NgayPhun" required 
             value="<?= htmlspecialchars($edit['NgayPhun'] ?? '') ?>">
    </div>

    <div>
      <label><strong>Tên người phun</strong></label>
      <input name="TenNguoiPhun" placeholder="vd: Nguyễn Văn A"
             value="<?= htmlspecialchars($edit['TenNguoiPhun'] ?? '') ?>">
    </div>

    <?php if ($T_VUNG && $vungs): ?>
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
    <?php else: ?>
    <div>
      <label><strong>Mã vùng</strong></label>
      <input name="MaVung" placeholder="vd: V001"
             value="<?= htmlspecialchars($edit['MaVung'] ?? '') ?>">
    </div>
    <?php endif; ?>

    <div>
      <label><strong>Tên thuốc</strong></label>
      <?php if ($thuocs): ?>
        <select name="TenThuoc">
          <option value="">-- Chọn thuốc --</option>
          <?php $selectedThuoc = $edit['TenThuoc'] ?? ''; ?>
          <?php foreach ($thuocs as $thuoc): ?>
            <option value="<?= htmlspecialchars($thuoc) ?>" <?= $selectedThuoc===$thuoc?'selected':'' ?>>
              <?= htmlspecialchars($thuoc) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input name="TenThuoc" placeholder="vd: Thuốc trừ sâu ABC"
               value="<?= htmlspecialchars($edit['TenThuoc'] ?? '') ?>">
      <?php endif; ?>
    </div>

    <div>
      <label><strong>Liều lượng</strong></label>
      <input type="number" step="0.01" min="0" name="LieuLuong" 
             placeholder="vd: 2.5"
             value="<?= htmlspecialchars($edit['LieuLuong'] ?? '') ?>">
    </div>

    <div class="full">
      <label><strong>Ghi chú</strong></label>
      <textarea name="GhiChu" rows="3" placeholder="Mô tả thêm về việc phun thuốc..."><?= htmlspecialchars($edit['GhiChu'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=nhatkyphunthuoc">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- FILTERS -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="nhatkyphunthuoc">
    
    <?php if ($T_VUNG && $vungs): ?>
    <select name="vung">
      <option value="">-- Lọc theo vùng --</option>
      <?php foreach ($vungs as $k=>$lab): ?>
        <option value="<?= htmlspecialchars($k) ?>" <?= $fVung===$k?'selected':'' ?>>
          <?= htmlspecialchars($lab) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <?php if ($thuocs): ?>
    <select name="thuoc">
      <option value="">-- Lọc theo thuốc --</option>
      <?php foreach ($thuocs as $thuoc): ?>
        <option value="<?= htmlspecialchars($thuoc) ?>" <?= $fThuoc===$thuoc?'selected':'' ?>>
          <?= htmlspecialchars($thuoc) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <input name="search" id="q" placeholder="Tìm người phun/tên thuốc/ghi chú..." value="<?= htmlspecialchars($kw) ?>">
    <button class="btn">Lọc</button>
    <?php if ($kw!=='' || $fVung!=='' || $fThuoc!==''): ?>
      <a class="btn secondary" href="index.php?p=nhatkyphunthuoc">Xóa lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách nhật ký phun thuốc</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> bản ghi</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>#</th>
        <th>Ngày phun</th>
        <th>Người phun</th>
        <th>Vùng</th>
        <th>Tên thuốc</th>
        <th>Liều lượng</th>
        <th>Ghi chú</th>
        <th style="width:110px">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong>#<?= (int)$r['ID'] ?></strong></td>
          <td><?= $r['NgayPhun'] ? htmlspecialchars(date('d/m/Y', strtotime($r['NgayPhun']))) : '-' ?></td>
          <td><?= htmlspecialchars($r['TenNguoiPhun'] ?? '-') ?></td>
          <td>
            <?php if ($r['MaVung']): ?>
              <strong><?= htmlspecialchars($r['MaVung']) ?></strong>
              <?php if (isset($r['TenVung']) && $r['TenVung']): ?>
                <div class="muted"><?= htmlspecialchars($r['TenVung']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($r['TenThuoc']): ?>
              <span class="badge-thuoc"><?= htmlspecialchars($r['TenThuoc']) ?></span>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right">
            <?= $r['LieuLuong']!==null ? rtrim(rtrim(number_format((float)$r['LieuLuong'],2,'.',''), '0'),'.') : '-' ?>
          </td>
          <td title="<?= htmlspecialchars($r['GhiChu'] ?? '') ?>">
            <?= htmlspecialchars(u_trim($r['GhiChu'] ?? '-', 50)) ?>
          </td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=nhatkyphunthuoc&action=edit&id=<?= (int)$r['ID'] ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=nhatkyphunthuoc&action=delete&id=<?= (int)$r['ID'] ?>"
               title="Xoá" data-confirm="Xoá bản ghi #<?= (int)$r['ID'] ?>?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="pages/layout/assets/js/nhatkyphunthuoc.js"></script>