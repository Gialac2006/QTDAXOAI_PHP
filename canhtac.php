<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM canh_tac ORDER BY NgayThucHien DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $activities = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $activities = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CANH TÁC - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/canhtac.css">
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
                <a href="login.html">QUẢN LÝ</a>
            </div>
        </li>
    </ul>
</nav>

<!-- Header Section -->
<section class="activity-header">
    <div class="container">
        <h1>HOẠT ĐỘNG CANH TÁC VÙNG XOÀI CÁT HÒA LỘC</h1>
        <p>Đồng Tháp - Theo dõi và quản lý các hoạt động canh tác xoài chuyên nghiệp</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($activities); ?></span>
                    <span class="stat-label">Hoạt động</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($activities, 'MaVung'))); ?></span>
                    <span class="stat-label">Vùng trồng</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($activities, 'NguoiThucHien'))); ?></span>
                    <span class="stat-label">Người thực hiện</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($activities, 'LoaiCongViec'))); ?></span>
                    <span class="stat-label">Loại công việc</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo người thực hiện, mã vùng...">
            </div>
            <div class="col-md-3">
                <select class="form-select search-input" id="filterLoaiCongViec">
                    <option value="">Tất cả công việc</option>
                    <option value="Tưới">Tưới</option>
                    <option value="Bón phân">Bón phân</option>
                    <option value="Phun thuốc">Phun thuốc</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" id="filterDate" class="form-control search-input">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary search-btn" onclick="clearFilters()">Xóa lọc</button>
            </div>
        </div>
    </div>

    <!-- Activities List -->
    <div class="row" id="activitiesContainer">
        <?php if (empty($activities)): ?>
            <div class="col-12">
                <div class="no-activities">
                    <i class="fas fa-seedling"></i>
                    <h3>Chưa có hoạt động canh tác nào được ghi nhận</h3>
                    <p>Hãy thêm thông tin hoạt động canh tác đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <div class="col-lg-6 activity-item" 
                     data-person="<?php echo strtolower($activity['NguoiThucHien']); ?>"
                     data-vung="<?php echo $activity['MaVung']; ?>"
                     data-loai="<?php echo $activity['LoaiCongViec']; ?>"
                     data-date="<?php echo date('Y-m-d', strtotime($activity['NgayThucHien'])); ?>">
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-icon">
                                <?php 
                                $icon = '';
                                switch($activity['LoaiCongViec']) {
                                    case 'Tưới': 
                                        $icon = '💧'; 
                                        break;
                                    case 'Bón phân': 
                                        $icon = '🌱'; 
                                        break;
                                    case 'Phun thuốc': 
                                        $icon = '🚿'; 
                                        break;
                                    default: 
                                        $icon = '🌿';
                                }
                                echo $icon;
                                ?>
                            </div>
                            <div class="activity-type">
                                <span class="badge-activity"><?php echo htmlspecialchars($activity['LoaiCongViec']); ?></span>
                            </div>
                        </div>
                        
                        <h3 class="activity-title">Hoạt động #<?php echo $activity['ID']; ?></h3>
                        
                        <div class="activity-info">
                            <i class="fas fa-calendar"></i>
                            <span><strong>Ngày thực hiện:</strong> <?php echo date('d/m/Y H:i', strtotime($activity['NgayThucHien'])); ?></span>
                        </div>
                        
                        <div class="activity-info">
                            <i class="fas fa-user"></i>
                            <span><strong>Người thực hiện:</strong> <?php echo htmlspecialchars($activity['NguoiThucHien']); ?></span>
                        </div>
                        
                        <div class="activity-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Mã vùng:</strong> <?php echo htmlspecialchars($activity['MaVung']); ?></span>
                        </div>
                        
                        <?php if (!empty($activity['TenPhanBon'])): ?>
                        <div class="activity-info">
                            <i class="fas fa-leaf"></i>
                            <span><strong>Phân bón/Thuốc:</strong> <?php echo htmlspecialchars($activity['TenPhanBon']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($activity['LieuLuong'])): ?>
                        <div class="activity-info">
                            <i class="fas fa-balance-scale"></i>
                            <span><strong>Liều lượng:</strong> <?php echo number_format($activity['LieuLuong'], 2); ?> kg/lít</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($activity['GhiChu'])): ?>
                        <div class="activity-info note">
                            <i class="fas fa-sticky-note"></i>
                            <span><strong>Ghi chú:</strong> <?php echo htmlspecialchars($activity['GhiChu']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="no-activities" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/canhtac.js"></script>

</body>
</html>