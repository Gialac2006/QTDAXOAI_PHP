<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database
    $sql = "SELECT * FROM vung_trong ORDER BY NgayBatDau DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $vungs = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $vungs = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>VÙNG TRỒNG - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/vungtrong.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <ul class="navbar-menu">
       <li class="navbar-item active"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item active"><a href="aboutus.html">Tụi tui</a></li>
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
                <a href="login.html">QUẢN LÍ</a>
            </div>
        </li>
    </ul>
</nav>

<!-- Header Section -->
<section class="farmer-header">
    <div class="container">
        <h1>VÙNG TRỒNG XOÀI</h1>
        <p>Thông tin các vùng trồng xoài tại Đồng Tháp</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($vungs); ?></span>
                    <span class="stat-label">Vùng trồng</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo array_sum(array_column($vungs, 'DienTich')); ?></span>
                    <span class="stat-label">m² Tổng diện tích</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($vungs, 'MaHo'))); ?></span>
                    <span class="stat-label">Hộ tham gia</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-8">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo tên vùng, địa chỉ...">
            </div>
            <div class="col-md-4">
                <select class="form-select search-input" id="filterTinhTrang">
                    <option value="">Tất cả tình trạng</option>
                    <option value="Đang trồng">Đang trồng</option>
                    <option value="Chuẩn bị">Chuẩn bị</option>
                    <option value="Bảo trì">Bảo trì</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Vùng Trồng List -->
    <div class="row" id="vungtrongContainer">
        <?php if (empty($vungs)): ?>
            <div class="col-12">
                <div class="no-farmers">
                    <i class="fas fa-map"></i>
                    <h3>Chưa có vùng trồng nào</h3>
                    <p>Hãy thêm thông tin vùng trồng đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($vungs as $vung): ?>
                <div class="col-lg-6 vung-item" 
                     data-name="<?php echo strtolower($vung['TenVung']); ?>"
                     data-address="<?php echo strtolower($vung['DiaChi']); ?>"
                     data-tinhtrang="<?php echo strtolower($vung['TinhTrang']); ?>">
                    <div class="farmer-card">
                        <div class="farmer-avatar">🌍</div>
                        
                        <h3 class="farmer-name"><?php echo htmlspecialchars($vung['TenVung']); ?></h3>
                        
                        <div class="farmer-info">
                            <i class="fas fa-id-card"></i>
                            <span><strong>Mã vùng:</strong> <?php echo htmlspecialchars($vung['MaVung']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($vung['DiaChi']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-ruler-combined"></i>
                            <span><strong>Diện tích:</strong> <?php echo number_format($vung['DienTich'], 0, ',', '.'); ?> m²</span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-seedling"></i>
                            <span><strong>Tình trạng:</strong> <span class="badge-custom"><?php echo htmlspecialchars($vung['TinhTrang']); ?></span></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-calendar-plus"></i>
                            <span><strong>Ngày bắt đầu:</strong> <?php echo date('d/m/Y', strtotime($vung['NgayBatDau'])); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-user"></i>
                            <span><strong>Mã hộ:</strong> <?php echo htmlspecialchars($vung['MaHo']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-leaf"></i>
                            <span><strong>Mã giống:</strong> <?php echo htmlspecialchars($vung['MaGiong']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="no-farmers" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/vungtrong.js"></script>

</body>
</html>
