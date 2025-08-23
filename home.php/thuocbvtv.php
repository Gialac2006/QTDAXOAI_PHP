<?php
// Kết nối CSDL
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT * FROM thuoc_bvtv ORDER BY TenThuoc ASC";
    $result = $conn->query($sql);

    if ($result) {
        $thuocs = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $thuocs = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>THUỐC BẢO VỆ THỰC VẬT</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <link rel="stylesheet" href="assets/css/thuocbvtv.css">
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
<section class="thuoc-header">
  <div class="container">
    <h1>Thuốc Bảo Vệ Thực Vật</h1>
    <p>Danh sách các loại thuốc sử dụng trong canh tác xoài</p>
  </div>
</section>

<div class="container">
  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo tên thuốc, hoạt chất...">
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="thuocContainer">
    <?php if (empty($thuocs)): ?>
      <div class="col-12">
        <div class="no-thuoc">
          <i class="fas fa-vial"></i>
          <h3>Chưa có dữ liệu thuốc</h3>
          <p>Hãy thêm thuốc BVTV vào hệ thống</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($thuocs as $t): ?>
        <div class="col-lg-6 thuoc-item" 
             data-ten="<?php echo strtolower($t['TenThuoc']); ?>"
             data-hoatchat="<?php echo strtolower($t['HoatChat']); ?>">
          <div class="thuoc-card">
            <div class="thuoc-avatar">🧴</div>
            <h3 class="thuoc-name"><?php echo htmlspecialchars($t['TenThuoc']); ?></h3>

            <div class="thuoc-info">
              <i class="fas fa-flask"></i>
              <span><strong>Hoạt chất:</strong> <?php echo htmlspecialchars($t['HoatChat']); ?></span>
            </div>

            <div class="thuoc-info">
              <i class="fas fa-box"></i>
              <span><strong>Đơn vị tính:</strong> <?php echo htmlspecialchars($t['DonViTinh']); ?></span>
            </div>

            <div class="thuoc-info">
              <i class="fas fa-info-circle"></i>
              <span><strong>Ghi chú:</strong> <?php echo htmlspecialchars($t['GhiChu']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-thuoc" style="display: none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
    <p>Vui lòng thử từ khóa khác</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/thuocbvtv.js"></script>

</body>
</html>
