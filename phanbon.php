<?php
// Kết nối CSDL
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT * FROM phan_bon ORDER BY TenPhanBon ASC";
    $result = $conn->query($sql);

    if ($result) {
        $phanbons = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $phanbons = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHÂN BÓN - VÙNG XOÀI ĐỒNG THÁP</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <link rel="stylesheet" href="assets/css/phanbon.css">
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
<section class="phanbon-header">
  <div class="container">
    <h1>Phân Bón</h1>
    <p>Danh sách các loại phân bón sử dụng trong canh tác xoài</p>
  </div>
</section>

<div class="container">
  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo tên phân bón, loại...">
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="phanbonContainer">
    <?php if (empty($phanbons)): ?>
      <div class="col-12">
        <div class="no-phanbon">
          <i class="fas fa-seedling"></i>
          <h3>Chưa có dữ liệu phân bón</h3>
          <p>Hãy thêm thông tin phân bón vào hệ thống</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($phanbons as $p): ?>
        <div class="col-lg-6 phanbon-item" 
             data-ten="<?php echo strtolower($p['TenPhanBon']); ?>"
             data-loai="<?php echo strtolower($p['Loai']); ?>">
          <div class="phanbon-card">
            <div class="phanbon-avatar">🌿</div>
            <h3 class="phanbon-name"><?php echo htmlspecialchars($p['TenPhanBon']); ?></h3>

            <div class="phanbon-info">
              <i class="fas fa-tags"></i>
              <span><strong>Loại:</strong> <?php echo htmlspecialchars($p['Loai']); ?></span>
            </div>

            <div class="phanbon-info">
              <i class="fas fa-box"></i>
              <span><strong>Đơn vị tính:</strong> <?php echo htmlspecialchars($p['DonViTinh']); ?></span>
            </div>

            <div class="phanbon-info">
              <i class="fas fa-info-circle"></i>
              <span><strong>Ghi chú:</strong> <?php echo htmlspecialchars($p['GhiChu']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-phanbon" style="display: none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
    <p>Vui lòng thử từ khóa khác</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/phanbon.js"></script>

</body>
</html>
