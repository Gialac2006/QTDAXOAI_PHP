<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM thiet_bi_may_moc ORDER BY MaHo, TenThietBi";
    $result = $conn->query($sql);
    
    if ($result) {
        $equipment = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    // Nhóm thiết bị theo MaHo
    $equipmentByHo = [];
    foreach ($equipment as $item) {
        $equipmentByHo[$item['MaHo']][] = $item;
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $equipment = [];
    $equipmentByHo = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>THIẾT BỊ MÁY MÓC - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/thietbitheoho.css">
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

<!-- Header Section -->
<section class="equipment-header">
    <div class="container">
        <h1>THIẾT BỊ MÁY MÓC VÙNG XOÀI</h1>
        <p>Đồng Tháp - Quản lý và theo dõi trang thiết bị máy móc phục vụ sản xuất nông nghiệp</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($equipmentByHo); ?></span>
                    <span class="stat-label">Hộ có thiết bị</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($equipment); ?></span>
                    <span class="stat-label">Tổng thiết bị</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_filter($equipment, function($item) { return $item['TinhTrang'] == 'Đang sử dụng'; })); ?></span>
                    <span class="stat-label">Đang hoạt động</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_filter($equipment, function($item) { return $item['TinhTrang'] == 'Bảo trì' || $item['TinhTrang'] == 'Hỏng'; })); ?></span>
                    <span class="stat-label">Cần bảo trì</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo mã hộ, tên thiết bị...">
            </div>
            <div class="col-md-4">
                <select class="form-select search-input" id="filterLoaiThietBi">
                    <option value="">Tất cả loại thiết bị</option>
                    <option value="Vận chuyển">Vận chuyển</option>
                    <option value="Làm đất">Làm đất</option>
                    <option value="Tưới tiêu">Tưới tiêu</option>
                    <option value="Phun thuốc">Phun thuốc</option>
                    <option value="Cắt tỉa">Cắt tỉa</option>
                    <option value="Thu hoạch">Thu hoạch</option>
                    <option value="Phụ trợ">Phụ trợ</option>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select search-input" id="filterTinhTrang">
                    <option value="">Tất cả tình trạng</option>
                    <option value="Tốt">Tốt</option>
                    <option value="Đang sử dụng">Đang sử dụng</option>
                    <option value="Mới đưa vào">Mới đưa vào</option>
                    <option value="Bảo trì">Bảo trì</option>
                    <option value="Đang sửa chữa">Đang sửa chữa</option>
                    <option value="Hỏng">Hỏng</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Equipment List by Ho -->
    <div class="row" id="equipmentContainer">
        <?php if (empty($equipmentByHo)): ?>
            <div class="col-12">
                <div class="no-equipment">
                    <i class="fas fa-tools"></i>
                    <h3>Chưa có thiết bị máy móc nào được đăng ký</h3>
                    <p>Hãy thêm thông tin thiết bị máy móc đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($equipmentByHo as $maHo => $equipmentList): ?>
                <div class="col-12 ho-equipment-section" 
                     data-maho="<?php echo strtolower($maHo); ?>">
                    
                    <!-- Ho Header -->
                    <div class="ho-header">
                        <div class="ho-info">
                            <h2>Hộ: <?php echo htmlspecialchars($maHo); ?></h2>
                            <span class="equipment-count"><?php echo count($equipmentList); ?> thiết bị</span>
                        </div>
                        <div class="ho-stats">
                            <?php
                            $hoangCount = count(array_filter($equipmentList, function($item) { return $item['TinhTrang'] == 'Đang sử dụng'; }));
                            $baoTriCount = count(array_filter($equipmentList, function($item) { return in_array($item['TinhTrang'], ['Bảo trì', 'Đang sửa chữa', 'Hỏng']); }));
                            ?>
                            <span class="stat-badge working"><?php echo $hoangCount; ?> hoạt động</span>
                            <span class="stat-badge maintenance"><?php echo $baoTriCount; ?> bảo trì</span>
                        </div>
                    </div>

                    <!-- Equipment Cards -->
                    <div class="row equipment-cards">
                        <?php foreach ($equipmentList as $item): ?>
                            <div class="col-lg-4 col-md-6 equipment-item" 
                                 data-name="<?php echo strtolower($item['TenThietBi']); ?>"
                                 data-loai="<?php echo $item['LoaiThietBi']; ?>"
                                 data-tinhtrang="<?php echo $item['TinhTrang']; ?>"
                                 data-maho="<?php echo strtolower($item['MaHo']); ?>">
                                <div class="equipment-card">
                                    <div class="equipment-icon">
                                        <?php
                                        $icon = 'fas fa-cog';
                                        switch($item['LoaiThietBi']) {
                                            case 'Vận chuyển':
                                                $icon = 'fas fa-truck';
                                                break;
                                            case 'Làm đất':
                                                $icon = 'fas fa-seedling';
                                                break;
                                            case 'Tưới tiêu':
                                                $icon = 'fas fa-tint';
                                                break;
                                            case 'Phun thuốc':
                                                $icon = 'fas fa-spray-can';
                                                break;
                                            case 'Cắt tỉa':
                                                $icon = 'fas fa-cut';
                                                break;
                                            case 'Thu hoạch':
                                                $icon = 'fas fa-apple-alt';
                                                break;
                                            case 'Phụ trợ':
                                                $icon = 'fas fa-tools';
                                                break;
                                        }
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    
                                    <h4 class="equipment-name"><?php echo htmlspecialchars($item['TenThietBi']); ?></h4>
                                    
                                    <div class="equipment-info">
                                        <span class="equipment-code">Mã TB: <?php echo htmlspecialchars($item['MaThietBi']); ?></span>
                                        <span class="equipment-type badge-custom-type"><?php echo htmlspecialchars($item['LoaiThietBi']); ?></span>
                                    </div>
                                    
                                    <div class="equipment-details">
                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Năm sử dụng: <?php echo $item['NamSuDung']; ?></span>
                                        </div>
                                        
                                        <div class="detail-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>Vùng: <?php echo htmlspecialchars($item['MaVung']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="equipment-status">
                                        <?php
                                        $statusClass = 'status-default';
                                        switch($item['TinhTrang']) {
                                            case 'Tốt':
                                            case 'Đang sử dụng':
                                            case 'Mới đưa vào':
                                                $statusClass = 'status-good';
                                                break;
                                            case 'Bảo trì':
                                            case 'Đang sửa chữa':
                                                $statusClass = 'status-maintenance';
                                                break;
                                            case 'Hỏng':
                                                $statusClass = 'status-broken';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($item['TinhTrang']); ?>
                                        </span>
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
    <div id="noResults" class="no-equipment" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/thietbitheoho.js"></script>

</body>
</html>