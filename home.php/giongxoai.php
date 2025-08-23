<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM giong_xoai ORDER BY TenGiong ASC";
    $result = $conn->query($sql);
    
    if ($result) {
        $giongxoai = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $giongxoai = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GIỐNG XOÀI - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/giongxoai.css">
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
                <a href="login.html">QUẢN LÝ</a>
            </div>
        </li>
    </ul>
</nav>

<!-- Header Section -->
<section class="giong-header">
    <div class="container">
        <h1>GIỐNG XOÀI CHẤT LƯỢNG CAO</h1>
        <p>Đồng Tháp - Nơi những giống xoài đặc sản được nông dân tin tưởng lựa chọn</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($giongxoai); ?></span>
                    <span class="stat-label">Giống xoài</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_filter($giongxoai, function($item) { return $item['TinhTrang'] == 'Còn sử dụng'; })); ?></span>
                    <span class="stat-label">Đang sử dụng</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($giongxoai, 'TenGiong'))); ?></span>
                    <span class="stat-label">Loại giống</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-8">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo tên giống, mã giống, đặc điểm...">
            </div>
            <div class="col-md-4">
                <select class="form-select search-input" id="filterTinhTrang">
                    <option value="">Tất cả tình trạng</option>
                    <option value="Còn sử dụng">Còn sử dụng</option>
                    <option value="Tạm ngưng">Tạm ngưng</option>
                    <option value="Ngưng hoàn toàn">Ngưng hoàn toàn</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Giong Xoai List -->
    <div class="row" id="giongContainer">
        <?php if (empty($giongxoai)): ?>
            <div class="col-12">
                <div class="no-giong">
                    <i class="fas fa-seedling"></i>
                    <h3>Chưa có giống xoài nào được đăng ký</h3>
                    <p>Hãy thêm thông tin giống xoài đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($giongxoai as $giong): ?>
                <div class="col-lg-6 giong-item" 
                     data-name="<?php echo strtolower($giong['TenGiong']); ?>"
                     data-code="<?php echo strtolower($giong['MaGiong']); ?>"
                     data-features="<?php echo strtolower($giong['DacDiem']); ?>"
                     data-status="<?php echo $giong['TinhTrang']; ?>">
                    <div class="giong-card">
                        <div class="giong-avatar">
                            <?php echo strtoupper(substr($giong['TenGiong'], -1)); ?>
                        </div>
                        
                        <h3 class="giong-name"><?php echo htmlspecialchars($giong['TenGiong']); ?></h3>
                        
                        <div class="giong-info">
                            <i class="fas fa-barcode"></i>
                            <span><strong>Mã giống:</strong> <?php echo htmlspecialchars($giong['MaGiong']); ?></span>
                        </div>
                        
                        <div class="giong-info">
                            <i class="fas fa-clock"></i>
                            <span><strong>Thời gian trưởng thành:</strong> <?php echo htmlspecialchars($giong['ThoiGianTruongThanh']); ?></span>
                        </div>
                        
                        <div class="giong-info">
                            <i class="fas fa-weight-hanging"></i>
                            <span><strong>Năng suất TB:</strong> <?php echo htmlspecialchars($giong['NangSuatTrungBinh']); ?></span>
                        </div>
                        
                        <div class="giong-info">
                            <i class="fas fa-star"></i>
                            <span><strong>Đặc điểm:</strong> <?php echo htmlspecialchars($giong['DacDiem']); ?></span>
                        </div>
                        
                        <div class="giong-info">
                            <i class="fas fa-info-circle"></i>
                            <span><strong>Tình trạng:</strong> 
                                <span class="badge-status <?php echo strtolower(str_replace(' ', '-', $giong['TinhTrang'])); ?>">
                                    <?php echo htmlspecialchars($giong['TinhTrang']); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="no-giong" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/giongxoai.js"></script>

</body>
</html>