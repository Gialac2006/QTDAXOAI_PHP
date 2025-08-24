<?php
// Kết nối CSDL
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT * FROM bao_cao_san_luong ORDER BY ID DESC";
    $result = $conn->query($sql);

    if ($result) {
        $reports = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BÁO CÁO SẢN LƯỢNG - VÙNG XOÀI ĐỒNG THÁP</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <link rel="stylesheet" href="assets/css/baocaosanluong.css">
</head>
<body>
<!-- Navbar giống như trang chủ -->
<nav class="navbar">
    <ul class="navbar-menu">
       <li class="navbar-item active"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item active"><a href="aboutus.html">Đội ngũ</a></li>
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
                <a href="login.html">QUẢN LÝ</a>
            </div>
        </li>
    </ul>
</nav>
<!-- Header -->
<section class="report-header">
  <div class="container">
    <h1>Báo Cáo Sản Lượng</h1>
    <p>Thống kê sản lượng, chất lượng xoài từng mùa vụ</p>
  </div>
</section>

<div class="container">
  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo mã vùng, mùa vụ, chất lượng...">
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="reportContainer">
    <?php if (empty($reports)): ?>
      <div class="col-12">
        <div class="no-report">
          <i class="fas fa-chart-bar"></i>
          <h3>Chưa có báo cáo sản lượng</h3>
          <p>Hãy thêm dữ liệu để theo dõi mùa vụ xoài</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($reports as $r): ?>
        <div class="col-lg-6 report-item" 
             data-vung="<?php echo strtolower($r['MaVung']); ?>"
             data-muavu="<?php echo strtolower($r['MaMuaVu']); ?>"
             data-chatluong="<?php echo strtolower($r['ChatLuong']); ?>">
          <div class="report-card">
            <div class="report-avatar">📈</div>
            <h3 class="report-name">Mùa vụ: <?php echo htmlspecialchars($r['MaMuaVu']); ?></h3>

            <div class="report-info">
              <i class="fas fa-map-marker-alt"></i>
              <span><strong>Mã vùng:</strong> <?php echo htmlspecialchars($r['MaVung']); ?></span>
            </div>

            <div class="report-info">
              <i class="fas fa-weight-hanging"></i>
              <span><strong>Sản lượng:</strong> <?php echo number_format($r['SanLuong'], 2); ?> tấn</span>
            </div>

            <div class="report-info">
              <i class="fas fa-star"></i>
              <span><strong>Chất lượng:</strong> <span class="badge-custom"><?php echo htmlspecialchars($r['ChatLuong']); ?></span></span>
            </div>

            <div class="report-info">
              <i class="fas fa-info-circle"></i>
              <span><strong>Ghi chú:</strong> <?php echo htmlspecialchars($r['GhiChu']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-report" style="display: none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
    <p>Vui lòng thử từ khóa khác</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/baocaosanluong.js"></script>

</body>
</html>
