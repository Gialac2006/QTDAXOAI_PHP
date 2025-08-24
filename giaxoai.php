<?php
// Kết nối CSDL
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT gx.*, gxo.TenGiong 
            FROM gia_xoai gx 
            LEFT JOIN giong_xoai gxo ON gx.MaGiong = gxo.MaGiong 
            ORDER BY gx.NgayCapNhat DESC, gx.ID DESC";
    $result = $conn->query($sql);

    if ($result) {
        $prices = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $prices = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GIÁ XOÀI - VÙNG XOÀI ĐỒNG THÁP</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/giaodien.css">
  <link rel="stylesheet" href="assets/css/giaxoai.css">
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
<section class="price-header">
  <div class="container">
    <h1>Giá Xoài Thị Trường</h1>
    <p>Cập nhật giá xoài các loại theo thời gian thực</p>
  </div>
</section>

<div class="container">
  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo mã giống, tên giống, giá bán...">
      </div>
      <div class="col-md-4">
        <select id="sortSelect" class="form-select search-input">
          <option value="">Sắp xếp theo</option>
          <option value="price-high">Giá cao nhất</option>
          <option value="price-low">Giá thấp nhất</option>
          <option value="date-new">Mới cập nhật</option>
          <option value="date-old">Cũ nhất</option>
        </select>
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="priceContainer">
    <?php if (empty($prices)): ?>
      <div class="col-12">
        <div class="no-price">
          <i class="fas fa-tags"></i>
          <h3>Chưa có thông tin giá</h3>
          <p>Hãy thêm dữ liệu để theo dõi giá xoài</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($prices as $p): ?>
        <div class="col-lg-6 price-item" 
             data-magiong="<?php echo strtolower($p['MaGiong']); ?>"
             data-tengiong="<?php echo strtolower($p['TenGiong'] ?? ''); ?>"
             data-giaban="<?php echo $p['GiaBan']; ?>"
             data-ngaycapnhat="<?php echo $p['NgayCapNhat']; ?>">
          <div class="price-card">
            <div class="price-avatar">🥭</div>
            <h3 class="price-name">
              <?php echo htmlspecialchars($p['TenGiong'] ?? $p['MaGiong']); ?>
            </h3>

            <div class="price-info">
              <i class="fas fa-barcode"></i>
              <span><strong>Mã giống:</strong> <?php echo htmlspecialchars($p['MaGiong']); ?></span>
            </div>

            <div class="price-info main-price">
              <i class="fas fa-money-bill-wave"></i>
              <span><strong>Giá bán:</strong> 
                <span class="price-value"><?php echo number_format($p['GiaBan'], 0, ',', '.'); ?>₫</span>
                <small>/<?php echo htmlspecialchars($p['DonViTinh']); ?></small>
              </span>
            </div>

            <div class="price-info">
              <i class="fas fa-calendar-alt"></i>
              <span><strong>Cập nhật:</strong> 
                <span class="date-badge"><?php echo date('d/m/Y', strtotime($p['NgayCapNhat'])); ?></span>
              </span>
            </div>

            <?php if (!empty($p['GhiChu'])): ?>
            <div class="price-info">
              <i class="fas fa-info-circle"></i>
              <span><strong>Ghi chú:</strong> <?php echo htmlspecialchars($p['GhiChu']); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-price" style="display: none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
    <p>Vui lòng thử từ khóa khác</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/giaxoai.js"></script>

</body>
</html>