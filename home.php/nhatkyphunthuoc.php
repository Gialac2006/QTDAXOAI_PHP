<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM nhat_ky_phun_thuoc ORDER BY TenNguoiPhun, NgayPhun DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $sprayLogs = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    // Nhóm nhật ký theo TenNguoiPhun
    $logsByPerson = [];
    foreach ($sprayLogs as $log) {
        $logsByPerson[$log['TenNguoiPhun']][] = $log;
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $sprayLogs = [];
    $logsByPerson = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NHẬT KÝ PHUN THUỐC - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/nhatkyphunthuoc.css">
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
                <a href="thietbitheoho.php">Thiết bị theo hộ</a>
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

<!-- Header Section -->
<section class="spray-header">
    <div class="container">
        <h1>NHẬT KÝ PHUN THUỐC VÙNG XOÀI</h1>
        <p>Đồng Tháp - Theo dõi và quản lý hoạt động phun thuốc bảo vệ thực vật</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($logsByPerson); ?></span>
                    <span class="stat-label">Người phun</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($sprayLogs); ?></span>
                    <span class="stat-label">Lần phun</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($sprayLogs, 'MaVung'))); ?></span>
                    <span class="stat-label">Vùng đã phun</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_unique(array_column($sprayLogs, 'TenThuoc'))); ?></span>
                    <span class="stat-label">Loại thuốc</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo tên người phun, tên thuốc...">
            </div>
            <div class="col-md-4">
                <select class="form-select search-input" id="filterThuoc">
                    <option value="">Tất cả loại thuốc</option>
                    <option value="Thuốc trừ sâu">Thuốc trừ sâu</option>
                    <option value="Thuốc trừ bệnh">Thuốc trừ bệnh</option>
                    <option value="Thuốc sinh trường">Thuốc sinh trường</option>
                    <option value="Thuốc diệt côn trùng">Thuốc diệt côn trùng</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="date" id="filterDate" class="form-control search-input" placeholder="Lọc theo ngày phun">
            </div>
        </div>
    </div>

    <!-- Spray Logs by Person -->
    <div class="row" id="sprayContainer">
        <?php if (empty($logsByPerson)): ?>
            <div class="col-12">
                <div class="no-spray">
                    <i class="fas fa-spray-can"></i>
                    <h3>Chưa có nhật ký phun thuốc nào được ghi nhận</h3>
                    <p>Hãy thêm thông tin phun thuốc đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($logsByPerson as $tenNguoiPhun => $sprayList): ?>
                <div class="col-12 person-spray-section" 
                     data-person="<?php echo strtolower($tenNguoiPhun); ?>">
                    
                    <!-- Person Header -->
                    <div class="person-header">
                        <div class="person-info">
                            <h2>Người phun: <?php echo htmlspecialchars($tenNguoiPhun); ?></h2>
                            <span class="spray-count"><?php echo count($sprayList); ?> lần phun</span>
                        </div>
                        <div class="person-stats">
                            <?php
                            $recentCount = count(array_filter($sprayList, function($log) { 
                                return strtotime($log['NgayPhun']) >= strtotime('-7 days'); 
                            }));
                            $totalAmount = array_sum(array_column($sprayList, 'LieuLuong'));
                            ?>
                            <span class="stat-badge recent"><?php echo $recentCount; ?> tuần qua</span>
                            <span class="stat-badge total"><?php echo number_format($totalAmount, 1); ?>L tổng</span>
                        </div>
                    </div>

                    <!-- Spray Log Cards -->
                    <div class="row spray-cards">
                        <?php foreach ($sprayList as $log): ?>
                            <div class="col-lg-6 col-md-12 spray-item" 
                                 data-person="<?php echo strtolower($log['TenNguoiPhun']); ?>"
                                 data-thuoc="<?php echo $log['TenThuoc']; ?>"
                                 data-date="<?php echo $log['NgayPhun']; ?>">
                                <div class="spray-card">
                                    <div class="spray-date">
                                        <div class="date-circle">
                                            <span class="day"><?php echo date('d', strtotime($log['NgayPhun'])); ?></span>
                                            <span class="month"><?php echo date('m/Y', strtotime($log['NgayPhun'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="spray-content">
                                        <h4 class="spray-medicine"><?php echo htmlspecialchars($log['TenThuoc']); ?></h4>
                                        
                                        <div class="spray-details">
                                            <div class="detail-row">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span>Vùng: <strong><?php echo htmlspecialchars($log['MaVung']); ?></strong></span>
                                            </div>
                                            
                                            <div class="detail-row">
                                                <i class="fas fa-tint"></i>
                                                <span>Liều lượng: <strong><?php echo $log['LieuLuong']; ?> L</strong></span>
                                            </div>
                                            
                                            <?php if (!empty($log['GhiChu'])): ?>
                                            <div class="detail-row">
                                                <i class="fas fa-sticky-note"></i>
                                                <span class="note-text"><?php echo htmlspecialchars($log['GhiChu']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="spray-type">
                                            <?php
                                            $typeClass = 'type-default';
                                            $iconClass = 'fas fa-spray-can';
                                            switch($log['TenThuoc']) {
                                                case 'Thuốc trừ sâu':
                                                    $typeClass = 'type-insect';
                                                    $iconClass = 'fas fa-bug';
                                                    break;
                                                case 'Thuốc trừ bệnh':
                                                    $typeClass = 'type-disease';
                                                    $iconClass = 'fas fa-shield-alt';
                                                    break;
                                                case 'Thuốc sinh trường':
                                                    $typeClass = 'type-growth';
                                                    $iconClass = 'fas fa-seedling';
                                                    break;
                                                case 'Thuốc diệt côn trùng':
                                                    $typeClass = 'type-pest';
                                                    $iconClass = 'fas fa-spider';
                                                    break;
                                            }
                                            ?>
                                            <span class="medicine-badge <?php echo $typeClass; ?>">
                                                <i class="<?php echo $iconClass; ?>"></i>
                                                <?php echo htmlspecialchars($log['TenThuoc']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="no-spray" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/nhatkyphunthuoc.js"></script>

</body>
</html>