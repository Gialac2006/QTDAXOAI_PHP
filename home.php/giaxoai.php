<?php
require_once __DIR__.'/public/connect.php';

try {
    $sql = "SELECT * FROM gia_xoai ORDER BY NgayCapNhat DESC";
    $result = $conn->query($sql);

    if ($result) {
        $giaList = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $giaList = [];
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
<section class="gia-header">
  <div class="container">
    <h1>Giá Xoài</h1>
    <p>Cập nhật giá xoài theo loại và ngày</p>
  </div>
</section>

<div class="container">
  <!-- Stats Section -->
  <div class="stats-section">
    <div class="row">
      <div class="col-md-4">
        <div class="stat-item">
          <span class="stat-number"><?php echo count($giaList); ?></span>
          <span class="stat-label">Lượt cập nhật</span>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-item">
          <span class="stat-number">
            <?php echo number_format(array_sum(array_column($giaList,'GiaBan'))/max(count($giaList),1),0,',','.'); ?>
          </span>
          <span class="stat-label">Giá TB (đ/kg)</span>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-item">
          <span class="stat-number">
            <?php echo number_format(max(array_column($giaList,'GiaBan')),0,',','.'); ?>
          </span>
          <span class="stat-label">Giá cao nhất (đ/kg)</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Search Box -->
  <div class="search-box">
    <div class="row align-items-center">
      <div class="col-md-8">
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm theo loại xoài, ghi chú...">
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="row" id="giaContainer">
    <?php if (empty($giaList)): ?>
      <div class="col-12">
        <div class="no-gia">
          <i class="fas fa-ban"></i>
          <h3>Chưa có dữ liệu giá xoài</h3>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($giaList as $g): ?>
        <div class="col-lg-6 gia-item"
             data-loai="<?php echo strtolower($g['LoaiXoai']); ?>"
             data-ghichu="<?php echo strtolower($g['GhiChu']); ?>">
          <div class="gia-card">
            <div class="gia-avatar">🥭</div>
            <h3 class="gia-name"><?php echo htmlspecialchars($g['LoaiXoai']); ?></h3>

            <div class="gia-info"><i class="fas fa-calendar-alt"></i>
              <span><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($g['NgayCapNhat'])); ?></span>
            </div>

            <div class="gia-info"><i class="fas fa-money-bill"></i>
              <span><strong>Giá bán:</strong> <?php echo number_format($g['GiaBan'], 0, ',', '.'); ?> đ/<?php echo $g['DonViTinh']; ?></span>
            </div>

            <div class="gia-info"><i class="fas fa-info-circle"></i>
              <span><strong>Ghi chú:</strong> <?php echo htmlspecialchars($g['GhiChu']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="noResults" class="no-gia" style="display:none;">
    <i class="fas fa-search"></i>
    <h3>Không tìm thấy kết quả</h3>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/giaxoai.js"></script>
</body>
</html>
