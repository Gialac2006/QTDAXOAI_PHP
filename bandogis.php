<?php
// Include file connect
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT * FROM ban_do_gis ORDER BY MaVung ASC";
    $result = $conn->query($sql);

    if ($result) {
        $maps = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $maps = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BẢN ĐỒ GIS - VÙNG XOÀI ĐỒNG THÁP</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <link rel="stylesheet" href="assets/css/bandogis.css">
</head>
<body>
<!-- Navbar -->
<nav class="navbar">
    <ul class="navbar-menu">
       <li class="navbar-item active"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item active"><a href="aboutus.html">Đội ngũ</a></li>
        <li class="navbar-item">Nông dân
            <div class="navbar-dropdown">
                <a href="honongdan.php">Danh sách nông dân</a>
                <a href="thietbitheoho.php">Thiết bị theo hộ</a>
                <a href="hotro.php">Hỗ trợ</a>
            </div>
        </li>
        <li class="navbar-item">Đất đai
            <div class="navbar-dropdown">
                <a href="vungtrong.php">Vùng trồng</a>
                <a href="muavu.php">Mùa vụ</a>
                <a href="giongxoai.php">Giống Xoài</a>
                <a href="bandogis.php">Sổ thửa & bản đồ</a>
                <a href="thoitiet.php">Thời tiết</a>
            </div>
        </li>
        <li class="navbar-item">Sản xuất
            <div class="navbar-dropdown">
                <a href="muavu.php">Theo dõi mùa vụ</a>
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
                <a href="login.html">QUẢN LÍ</a>
            </div>
        </li>
    </ul>
</nav>

<!-- Header -->
<section class="map-header">
  <div class="container">
    <h1>Bản đồ GIS</h1>
    <p>Quản lý thông tin tọa độ vùng trồng xoài</p>
  </div>
</section>

<div class="container">
  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo mã vùng, nhãn tên...">
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="gisContainer">
    <?php if (empty($maps)): ?>
      <div class="col-12">
        <div class="no-maps">
          <i class="fas fa-map"></i>
          <h3>Chưa có dữ liệu GIS</h3>
          <p>Hãy thêm vùng trồng vào hệ thống</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($maps as $m): ?>
        <div class="col-lg-6 gis-item" 
             data-mavung="<?php echo strtolower($m['MaVung']); ?>"
             data-nhanten="<?php echo strtolower($m['NhanTen']); ?>">
          <div class="gis-card">
            <div class="gis-avatar">📍</div>
            <h3 class="gis-name"><?php echo htmlspecialchars($m['NhanTen']); ?></h3>

            <div class="gis-info">
              <i class="fas fa-id-card"></i>
              <span><strong>Mã vùng:</strong> <?php echo htmlspecialchars($m['MaVung']); ?></span>
            </div>

            <div class="gis-info">
              <i class="fas fa-map-marker-alt"></i>
              <span><strong>Tọa độ:</strong> <?php echo htmlspecialchars($m['ToaDo']); ?></span>
            </div>

            <div class="gis-info">
              <i class="fas fa-info-circle"></i>
              <span><strong>Thông tin:</strong> <?php echo $m['ThongTinPopup']; ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-maps" style="display: none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
    <p>Vui lòng thử từ khóa khác</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/bandogis.js"></script>

</body>
</html>
