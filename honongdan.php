<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM ho_nong_dan ORDER BY NgayDangKy DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $farmers = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $farmers = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HỘ NÔNG DÂN - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/honongdan.css">
</head>
<body>

<!-- Navbar giống như trang chủ -->
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

<!-- Header Section -->
<section class="farmer-header">
    <div class="container">
        <h1>HỘ NÔNG DÂN VÙNG XOÀI </h1>
        <p>Đồng Tháp - Vùng đất màu mỡ, nơi sinh ra những trái xoài ngọt ngào nhất</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($farmers); ?></span>
                    <span class="stat-label">Hộ nông dân</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo array_sum(array_column($farmers, 'DienTich')); ?></span>
                    <span class="stat-label">m² Tổng diện tích</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo array_sum(array_column($farmers, 'SoThanhVien')); ?></span>
                    <span class="stat-label">Thành viên</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-8">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo tên, CCCD, địa chỉ...">
            </div>
            <div class="col-md-4">
                <select class="form-select search-input" id="filterLoaiDat">
                    <option value="">Tất cả loại đất</option>
                    <option value="Phù Sa">Đất phù sa</option>
                    <option value="Đồng ruộng">Đồng ruộng</option>
                    <option value="Đất vườn">Đất vườn</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Farmers List -->
    <div class="row" id="farmersContainer">
        <?php if (empty($farmers)): ?>
            <div class="col-12">
                <div class="no-farmers">
                    <i class="fas fa-users"></i>
                    <h3>Chưa có hộ nông dân nào được đăng ký</h3>
                    <p>Hãy thêm thông tin hộ nông dân đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($farmers as $farmer): ?>
                <div class="col-lg-6 farmer-item" 
                     data-name="<?php echo strtolower($farmer['TenChuHo']); ?>"
                     data-cccd="<?php echo $farmer['CCCD']; ?>"
                     data-address="<?php echo strtolower($farmer['DiaChi']); ?>"
                     data-loaidat="<?php echo $farmer['LoaiDat']; ?>">
                    <div class="farmer-card">
                        <div class="farmer-avatar">
                            <?php echo strtoupper(substr($farmer['TenChuHo'], 0, 1)); ?>
                        </div>
                        
                        <h3 class="farmer-name"><?php echo htmlspecialchars($farmer['TenChuHo']); ?></h3>
                        
                        <div class="farmer-info">
                            <i class="fas fa-id-card"></i>
                            <span><strong>Mã hộ:</strong> <?php echo htmlspecialchars($farmer['MaHo']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-credit-card"></i>
                            <span><strong>CCCD:</strong> <?php echo htmlspecialchars($farmer['CCCD']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-birthday-cake"></i>
                            <span><strong>Ngày sinh:</strong> <?php echo date('d/m/Y', strtotime($farmer['NgaySinh'])); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-phone"></i>
                            <span><strong>Điện thoại:</strong> <?php echo htmlspecialchars($farmer['SoDienThoai']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($farmer['DiaChi']); ?></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-users"></i>
                            <span><strong>Thành viên:</strong> <?php echo $farmer['SoThanhVien']; ?> người</span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-seedling"></i>
                            <span><strong>Loại đất:</strong> <span class="badge-custom"><?php echo htmlspecialchars($farmer['LoaiDat']); ?></span></span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-expand-arrows-alt"></i>
                            <span><strong>Diện tích:</strong> <?php echo number_format($farmer['DienTich'], 0, ',', '.'); ?> m²</span>
                        </div>
                        
                        <div class="farmer-info">
                            <i class="fas fa-calendar-plus"></i>
                            <span><strong>Ngày đăng ký:</strong> <?php echo date('d/m/Y', strtotime($farmer['NgayDangKy'])); ?></span>
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
<script src="assets/js/honongdan.js"></script>

</body>
</html>