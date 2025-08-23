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

/* ===== Table ===== */
$T_GIONG = findTable($conn, ['giong_xoai','giongxoai']);
if (!$T_GIONG) {
  echo "<h1>Quản lý Giống xoài</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>giong_xoai</code>/<code>giongxoai</code> trong CSDL.</div>";
  return;
}

/* ===== Actions ===== */
$msg = $err = null; $edit = null;
$action = $_GET['action'] ?? '';

if ($action === 'delete') {
  $MaGiong = trim($_GET['id'] ?? '');
  if ($MaGiong !== '') {
    $stmt = $conn->prepare("DELETE FROM `$T_GIONG` WHERE MaGiong=?");
    $stmt->bind_param('s',$MaGiong);
    if (!$stmt->execute()) $err = "Không xoá được: ".$stmt->error;
    else $msg = "Đã xoá giống: $MaGiong";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $MaGiong = trim($_GET['id'] ?? '');
  if ($MaGiong !== '') {
    $stmt = $conn->prepare("SELECT * FROM `$T_GIONG` WHERE MaGiong=?");
    $stmt->bind_param('s',$MaGiong);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TenGiong'])) {
  $isEdit   = !empty($_POST['MaGiong']);
  $MaGiong  = trim($_POST['MaGiong'] ?? '');
  $TenGiong = trim($_POST['TenGiong'] ?? '');
  $TGTt     = trim($_POST['ThoiGianTruongThanh'] ?? '') ?: null;
  $NS       = trim($_POST['NangSuatTrungBinh'] ?? '') ?: null;
  $DacDiem  = trim($_POST['DacDiem'] ?? '') ?: null;
  $TinhTrang= trim($_POST['TinhTrang'] ?? 'Còn sử dụng');

  if ($TenGiong === '') $err = 'Vui lòng nhập Tên giống';
  else {
    if ($isEdit) {
      $sql = "UPDATE `$T_GIONG`
              SET TenGiong=?, ThoiGianTruongThanh=?, NangSuatTrungBinh=?, DacDiem=?, TinhTrang=?
              WHERE MaGiong=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssssss', $TenGiong,$TGTt,$NS,$DacDiem,$TinhTrang,$MaGiong);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật giống: $TenGiong"; $edit=null; }
      $stmt->close();
    } else {
      // Tạo mã GO001..GO999 tránh trùng
      do {
        $gen = 'GO'.sprintf('%03d', rand(1, 999));
        $dup = $conn->query("SELECT MaGiong FROM `$T_GIONG` WHERE MaGiong='{$conn->real_escape_string($gen)}'");
      } while ($dup && $dup->num_rows);

      $sql = "INSERT INTO `$T_GIONG` (MaGiong, TenGiong, ThoiGianTruongThanh, NangSuatTrungBinh, DacDiem, TinhTrang)
              VALUES (?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssssss', $gen,$TenGiong,$TGTt,$NS,$DacDiem,$TinhTrang);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm giống: $TenGiong (Mã: $gen)";
      $stmt->close();
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where = ''; $types=''; $params=[];
if ($search !== '') {
  $where = "WHERE MaGiong LIKE ? OR TenGiong LIKE ? OR DacDiem LIKE ? OR TinhTrang LIKE ?
            OR ThoiGianTruongThanh LIKE ? OR NangSuatTrungBinh LIKE ?";
  $kw = "%$search%"; $types='ssssss'; $params=[$kw,$kw,$kw,$kw,$kw,$kw];
}
$sql = "SELECT * FROM `$T_GIONG` $where ORDER BY TenGiong ASC LIMIT 300";
if ($params){ $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $rows=$stmt->get_result(); $list=$rows->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Giống xoài</h1>
<link rel="stylesheet" href="pages/layout/assets/css/giongxoai.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ===== FORM ===== -->
<div class="card">
  <h3><?= $edit ? 'Chỉnh sửa giống xoài' : 'Thêm giống xoài mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($edit): ?>
      <input type="hidden" name="MaGiong" value="<?= htmlspecialchars($edit['MaGiong']) ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>Mã giống:</strong> <?= htmlspecialchars($edit['MaGiong']) ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Tên giống *</strong></label>
      <input name="TenGiong" required value="<?= htmlspecialchars($edit['TenGiong'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Thời gian trưởng thành</strong></label>
      <input name="ThoiGianTruongThanh" placeholder="vd: 12 tháng" value="<?= htmlspecialchars($edit['ThoiGianTruongThanh'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Năng suất trung bình</strong></label>
      <input name="NangSuatTrungBinh" placeholder="vd: 100 kg/ha" value="<?= htmlspecialchars($edit['NangSuatTrungBinh'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Tình trạng</strong></label>
      <?php $tt = $edit['TinhTrang'] ?? 'Còn sử dụng'; ?>
      <select name="TinhTrang">
        <?php foreach (['Còn sử dụng','Tạm ngưng','Ngưng hoàn toàn'] as $opt): ?>
          <option <?= $tt===$opt?'selected':'' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="full">
      <label><strong>Đặc điểm</strong></label>
      <textarea name="DacDiem" rows="3" placeholder="Mô tả ngắn..."><?= htmlspecialchars($edit['DacDiem'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?p=giongxoai">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ===== SEARCH ===== -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="giongxoai">
    <input type="search" name="search" placeholder="Tìm theo mã, tên giống, đặc điểm, tình trạng…" value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a class="btn secondary" href="index.php?p=giongxoai">Xoá lọc</a><?php endif; ?>
  </form>
</div>

<!-- ===== LIST ===== -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>📋 Danh sách giống xoài</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> giống</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Mã giống</th>
          <th>Tên giống</th>
          <th>Thời gian TT</th>
          <th>Năng suất TB</th>
          <th>Đặc điểm</th>
          <th>Tình trạng</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['MaGiong']) ?></strong></td>
          <td><?= htmlspecialchars($r['TenGiong']) ?></td>
          <td><?= htmlspecialchars($r['ThoiGianTruongThanh'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['NangSuatTrungBinh'] ?? '-') ?></td>
          <td title="<?= htmlspecialchars($r['DacDiem'] ?? '') ?>"><?= htmlspecialchars(u_trim($r['DacDiem'] ?? '-', 60)) ?></td>
          <td>
            <?php $tg = $r['TinhTrang'] ?? ''; ?>
            <span class="badge b-<?= $tg==='Còn sử dụng'?'ok':($tg==='Tạm ngưng'?'warn':'stop') ?>">
              <?= htmlspecialchars($tg ?: '-') ?>
            </span>
          </td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=giongxoai&action=edit&id=<?= urlencode($r['MaGiong']) ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=giongxoai&action=delete&id=<?= urlencode($r['MaGiong']) ?>"
               title="Xoá" data-confirm="Xoá giống '<?= htmlspecialchars($r['TenGiong']) ?>'?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/giongxoai.js"></script>

  </div>
</div>
