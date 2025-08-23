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

/* ===== Table ===== */
$TABLE_MAINTENANCE = findTable($conn, ['lich_bao_tri','lichbaotri']);
if (!$TABLE_MAINTENANCE) {
  echo "<h1>Quản lý Lịch bảo trì</h1>";
  echo "<div class='msg err' style='display:block'>Thiếu bảng <code>lich_bao_tri</code>/<code>lichbaotri</code> trong CSDL.</div>";
  return;
}

/* ===== Trạng thái options ===== */
$trangThaiOptions = ['Đang chờ', 'Đang xử lí', 'Hoàn thành', 'Hủy bỏ'];

$msg = $err = null;
$action = $_GET['action'] ?? '';
$editData = null;

/* ===== Actions ===== */
if ($action === 'delete') {
  $ID = intval($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("DELETE FROM `$TABLE_MAINTENANCE` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    if (!$stmt->execute()) $err = "Không xóa được: ".$stmt->error;
    else $msg = "Đã xóa lịch bảo trì ID: $ID";
    $stmt->close();
  }
}

if ($action === 'edit') {
  $ID = intval($_GET['id'] ?? 0);
  if ($ID > 0) {
    $stmt = $conn->prepare("SELECT * FROM `$TABLE_MAINTENANCE` WHERE ID=?");
    $stmt->bind_param('i', $ID);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

/* ===== Create / Update ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['NoiDung'])) {
  $isEdit = isset($_POST['isEdit']) && $_POST['isEdit']=='1';
  $ID = intval($_POST['ID'] ?? 0);

  $MaThietBi = trim($_POST['MaThietBi'] ?? '') ?: null;
  $NgayBaoTri = trim($_POST['NgayBaoTri'] ?? '') ?: null;
  $NoiDung = trim($_POST['NoiDung'] ?? '');
  $TrangThai = trim($_POST['TrangThai'] ?? '') ?: 'Đang chờ';

  if ($NoiDung === '') $err = 'Vui lòng nhập nội dung bảo trì';
  else {
    if ($isEdit && $ID > 0) {
      $sql = "UPDATE `$TABLE_MAINTENANCE` SET MaThietBi=?, NgayBaoTri=?, NoiDung=?, TrangThai=? WHERE ID=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssssi', $MaThietBi, $NgayBaoTri, $NoiDung, $TrangThai, $ID);
      if (!$stmt->execute()) $err = "Không cập nhật được: ".$stmt->error;
      else { $msg = "Đã cập nhật lịch bảo trì ID: $ID"; $editData = null; }
      $stmt->close();
    } else {
      $sql = "INSERT INTO `$TABLE_MAINTENANCE` (MaThietBi, NgayBaoTri, NoiDung, TrangThai) VALUES (?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ssss', $MaThietBi, $NgayBaoTri, $NoiDung, $TrangThai);
      if (!$stmt->execute()) $err = "Không thêm được: ".$stmt->error;
      else $msg = "Đã thêm lịch bảo trì mới";
      $stmt->close();
    }
  }
}

/* ===== Search + List ===== */
$search = trim($_GET['search'] ?? '');
$where = ''; $types=''; $params=[];
if ($search !== '') {
  $where = "WHERE MaThietBi LIKE ? OR NoiDung LIKE ? OR TrangThai LIKE ?";
  $kw = "%$search%"; $types='sss'; $params = [$kw,$kw,$kw];
}
$sql = "SELECT * FROM `$TABLE_MAINTENANCE` $where ORDER BY NgayBaoTri DESC, ID DESC LIMIT 300";
if ($params) { $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute(); $list=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
else { $q=$conn->query($sql); $list=$q?$q->fetch_all(MYSQLI_ASSOC):[]; }
?>

<h1>Quản lý Lịch bảo trì</h1>
<link rel="stylesheet" href="pages/layout/assets/css/lichbaotri.css">
<?php if ($msg): ?><div class="msg" style="display:block"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err" style="display:block"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FORM -->
<div class="card">
  <h3><?= $editData ? 'Chỉnh sửa lịch bảo trì' : 'Thêm lịch bảo trì mới' ?></h3>

  <form method="post" class="row form-3col">
    <?php if ($editData): ?>
      <input type="hidden" name="isEdit" value="1">
      <input type="hidden" name="ID" value="<?= $editData['ID'] ?>">
      <div class="full" style="background:#f5f5f5;padding:8px 12px;border-radius:10px">
        <strong>ID:</strong> <?= $editData['ID'] ?> - <strong>Trạng thái hiện tại:</strong> <?= htmlspecialchars($editData['TrangThai']) ?>
      </div>
    <?php endif; ?>

    <div class="col">
      <label><strong>Mã thiết bị</strong></label>
      <input name="MaThietBi" value="<?= htmlspecialchars($editData['MaThietBi'] ?? '') ?>" placeholder="VD: TB001">
    </div>

    <div class="col">
      <label><strong>Ngày bảo trì</strong></label>
      <input type="date" name="NgayBaoTri" value="<?= htmlspecialchars($editData['NgayBaoTri'] ?? '') ?>">
    </div>

    <div class="col">
      <label><strong>Trạng thái</strong></label>
      <?php $ttSel = $editData['TrangThai'] ?? 'Đang chờ'; ?>
      <select name="TrangThai">
        <?php foreach ($trangThaiOptions as $tt): ?>
          <option value="<?= htmlspecialchars($tt) ?>" <?= $ttSel===$tt?'selected':'' ?>><?= htmlspecialchars($tt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="full">
      <label><strong>Nội dung bảo trì *</strong></label>
      <textarea name="NoiDung" required placeholder="Mô tả chi tiết công việc bảo trì cần thực hiện..."><?= htmlspecialchars($editData['NoiDung'] ?? '') ?></textarea>
    </div>

    <div class="full" style="display:flex;gap:10px">
      <button class="btn"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($editData): ?><a class="btn secondary" href="index.php?p=lichbaotri">Hủy</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SEARCH -->
<div class="card">
  <form method="get" class="toolbar">
    <input type="hidden" name="p" value="lichbaotri">
    <input name="search" placeholder="Tìm theo mã thiết bị, nội dung, trạng thái..." value="<?= htmlspecialchars($search) ?>" style="min-width:320px">
    <button class="btn">Tìm kiếm</button>
    <?php if ($search): ?><a href="index.php?p=lichbaotri" class="btn secondary">Xóa lọc</a><?php endif; ?>
  </form>
</div>

<!-- LIST -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3>🔧 Danh sách lịch bảo trì</h3>
    <span class="muted">Tổng: <strong><?= count($list) ?></strong> lịch bảo trì</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Mã thiết bị</th>
          <th>Ngày bảo trì</th>
          <th>Nội dung</th>
          <th>Trạng thái</th>
          <th style="width:110px">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="muted" style="text-align:center;padding:36px">Chưa có dữ liệu.</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><strong><?= $r['ID'] ?></strong></td>
          <td>
            <?php if ($r['MaThietBi']): ?>
              <span class="badge device"><?= htmlspecialchars($r['MaThietBi']) ?></span>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($r['NgayBaoTri']): ?>
              <?= date('d/m/Y', strtotime($r['NgayBaoTri'])) ?>
            <?php else: ?>
              <span class="muted">Chưa đặt</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="content-preview"><?= htmlspecialchars(substr($r['NoiDung'], 0, 60)) ?><?= strlen($r['NoiDung']) > 60 ? '...' : '' ?></div>
          </td>
          <td>
            <?php 
            $status = $r['TrangThai'];
            $statusClass = '';
            switch($status) {
              case 'Đang chờ': $statusClass = 'waiting'; break;
              case 'Đang xử lí': $statusClass = 'processing'; break;
              case 'Hoàn thành': $statusClass = 'completed'; break;
              case 'Hủy bỏ': $statusClass = 'cancelled'; break;
              default: $statusClass = 'default';
            }
            ?>
            <span class="badge status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
          </td>
          <td class="actions">
            <a class="btn icon" href="index.php?p=lichbaotri&action=edit&id=<?= $r['ID'] ?>" title="Sửa">✏️</a>
            <a class="btn icon danger" href="index.php?p=lichbaotri&action=delete&id=<?= $r['ID'] ?>"
               title="Xóa" data-confirm="Xóa lịch bảo trì này không thể khôi phục?">🗑️</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <script src="pages/layout/assets/js/lichbaotri.js"></script>
  </div>
</div>