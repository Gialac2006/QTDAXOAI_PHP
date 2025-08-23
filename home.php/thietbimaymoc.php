<?php
// Kết nối CSDL
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT * FROM thiet_bi_may_moc ORDER BY NamSuDung DESC";
    $result = $conn->query($sql);

    if ($result) {
        $devices = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $devices = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>THIẾT BỊ MÁY MÓC - VÙNG XOÀI ĐỒNG THÁP</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <link rel="stylesheet" href="assets/css/thietbimaymoc.css">
</head>
<body>
<!-- Navbar giống như trang chủ -->
<nav class="navbar">
    <ul class="navbar-menu">
       <li class="navbar-item active"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item active"><a href="aboutus.html">Tụi tui</a></li>
        <li class="navbar-item">Nông dân
            <div class="navbar-dropdown">
                <a href="honongdan.php">Danh sách nông dân</a>
                <a href="thietbiho.php">Thiết bị theo hộ</a>
                <a href="hotro.php">Hỗ trợ</a>
            </div>
        </li>
        <li class="navbar-item">Đất đai
            <div class="navbar-dropdown">
                <a href="vungtrong.php">Vùng trồng</a>
                <a href="muavu.php">Mùa vụ</a>
                <a href="giongxoai.php">Giống Xoài</a>
                <a href="bandogis.php">Sở thừa & bản đồ</a>
                <a href="thoitiet.php">Thời tiết</a>
            </div>
        </li>
        <li class="navbar-item">Sản xuất
            <div class="navbar-dropdown">
                <a href="#">Theo dõi mùa vụ</a>
                <a href="canhtac.php">Canh tác</a>
                <a href="nhatkyphunthuoc.php">Nhật ký phun thuốc</a>
                <a href="thuocbvtv.php">Thuốc bảo vệ thực vật</a>
                <a href="phanbon.php">Phân bón</a>
                <a href="thietbimaymoc.php">Thiết bị máy móc</a>
                <a href="baocaosanluong.php">Báo cáo sản lượng</a>
            </div>
        </li>
        <li class="navbar-item">Thị trường
            <div class="navbar-dropdown">
                <a href="giaxoai.php">Giá xoài</a>
            </div>
        </li>
        <li class="navbar-item">Đào tạo
            <div class="navbar-dropdown">
                <a href="#">Tài liệu hướng dẫn</a>
            </div>
        </li>
        <li class="navbar-item">ADMIN
            <div class="navbar-dropdown">
                <a href="login.html">QUẢN LÝ</a>
            </div>
        </li>
    </ul>
</nav>
<!-- Header -->
<section class="device-header">
  <div class="container">
    <h1>Thiết Bị Máy Móc</h1>
    <p>Danh sách các thiết bị phục vụ canh tác xoài</p>
  </div>
</section>

<div class="container">
  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo tên thiết bị, loại, tình trạng...">
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="deviceContainer">
    <?php if (empty($devices)): ?>
      <div class="col-12">
        <div class="no-device">
          <i class="fas fa-tools"></i>
          <h3>Chưa có dữ liệu thiết bị</h3>
          <p>Hãy thêm thiết bị máy móc vào hệ thống</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($devices as $d): ?>
        <div class="col-lg-6 device-item" 
             data-ten="<?php echo strtolower($d['TenThietBi']); ?>"
             data-loai="<?php echo strtolower($d['LoaiThietBi']); ?>"
             data-tinhtrang="<?php echo strtolower($d['TinhTrang']); ?>">
          <div class="device-card">
            <div class="device-avatar">🔧</div>
            <h3 class="device-name"><?php echo htmlspecialchars($d['TenThietBi']); ?></h3>

            <div class="device-info">
              <i class="fas fa-id-card"></i>
              <span><strong>Mã thiết bị:</strong> <?php echo htmlspecialchars($d['MaThietBi']); ?></span>
            </div>

            <div class="device-info">
              <i class="fas fa-cogs"></i>
              <span><strong>Loại thiết bị:</strong> <?php echo htmlspecialchars($d['LoaiThietBi']); ?></span>
            </div>

            <div class="device-info">
              <i class="fas fa-calendar-alt"></i>
              <span><strong>Năm sử dụng:</strong> <?php echo htmlspecialchars($d['NamSuDung']); ?></span>
            </div>

            <div class="device-info">
              <i class="fas fa-check-circle"></i>
              <span><strong>Tình trạng:</strong> <span class="badge-custom"><?php echo htmlspecialchars($d['TinhTrang']); ?></span></span>
            </div>

            <div class="device-info">
              <i class="fas fa-user"></i>
              <span><strong>Mã hộ:</strong> <?php echo htmlspecialchars($d['MaHo']); ?></span>
            </div>

            <div class="device-info">
              <i class="fas fa-map-marker-alt"></i>
              <span><strong>Mã vùng:</strong> <?php echo htmlspecialchars($d['MaVung']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-device" style="display: none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
    <p>Vui lòng thử từ khóa khác</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/thietbimaymoc.js"></script>

</body>
</html>
