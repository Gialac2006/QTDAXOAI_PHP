<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM thoi_tiet ORDER BY ThoiDiem DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $thoitiet = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    // Lấy danh sách vùng để filter
    $sqlVung = "SELECT DISTINCT MaVung FROM thoi_tiet ORDER BY MaVung";
    $resultVung = $conn->query($sqlVung);
    $vungList = [];
    if ($resultVung) {
        $vungList = $resultVung->fetch_all(MYSQLI_ASSOC);
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $thoitiet = [];
    $vungList = [];
}

// Function để xác định tình trạng thời tiết dựa trên các chỉ số
function getTrangThaiThoiTiet($nhietDo, $doAm, $luongMua, $tocDoGio, $chiSoUV) {
    if ($luongMua > 0) {
        return ['status' => 'Mưa', 'class' => 'badge-rain', 'icon' => 'fas fa-cloud-rain'];
    } elseif ($chiSoUV > 8) {
        return ['status' => 'Nắng nóng', 'class' => 'badge-hot', 'icon' => 'fas fa-sun'];
    } elseif ($tocDoGio > 3) {
        return ['status' => 'Có gió', 'class' => 'badge-windy', 'icon' => 'fas fa-wind'];
    } elseif ($doAm > 80) {
        return ['status' => 'Ẩm ướt', 'class' => 'badge-humid', 'icon' => 'fas fa-tint'];
    } else {
        return ['status' => 'Bình thường', 'class' => 'badge-normal', 'icon' => 'fas fa-cloud-sun'];
    }
}

// Function để chuyển đổi hướng gió
function getHuongGioText($huongGio) {
    $huongGioMap = [
        'N' => 'Bắc',
        'NE' => 'Đông Bắc', 
        'E' => 'Đông',
        'SE' => 'Đông Nam',
        'S' => 'Nam',
        'SW' => 'Tây Nam',
        'W' => 'Tây',
        'NW' => 'Tây Bắc'
    ];
    return isset($huongGioMap[$huongGio]) ? $huongGioMap[$huongGio] : $huongGio;
}

// Function để đánh giá chỉ số UV
function getUVLevel($chiSoUV) {
    if ($chiSoUV <= 2) return ['level' => 'Thấp', 'class' => 'uv-low'];
    elseif ($chiSoUV <= 5) return ['level' => 'Trung bình', 'class' => 'uv-medium'];
    elseif ($chiSoUV <= 7) return ['level' => 'Cao', 'class' => 'uv-high'];
    elseif ($chiSoUV <= 10) return ['level' => 'Rất cao', 'class' => 'uv-very-high'];
    else return ['level' => 'Cực cao', 'class' => 'uv-extreme'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>THỜI TIẾT - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/thoitiet.css">
</head>
<body>

<!-- Navbar -->
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
<section class="thoitiet-header">
    <div class="container">
        <h1>THỜI TIẾT VÙNG XOÀI CÁT HÒA LỘC</h1>
        <p>Đồng Tháp - Theo dõi điều kiện thời tiết để tối ưu hóa sản xuất xoài</p>
    </div>
</section>

<!-- Current Weather Summary -->
<?php if (!empty($thoitiet)): 
    $latest = $thoitiet[0]; // Dữ liệu mới nhất
    $trangThai = getTrangThaiThoiTiet($latest['NhietDo'], $latest['DoAm'], $latest['LuongMua'], $latest['TocDoGio'], $latest['ChiSoUV']);
?>
<div class="container">
    <div class="current-weather">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="current-info">
                    <div class="weather-icon">
                        <i class="<?php echo $trangThai['icon']; ?>"></i>
                    </div>
                    <div class="weather-details">
                        <div class="temperature"><?php echo number_format($latest['NhietDo'], 1); ?>°C</div>
                        <div class="description"><?php echo htmlspecialchars($latest['MoTa']); ?></div>
                        <div class="location">Vùng: <?php echo htmlspecialchars($latest['MaVung']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="weather-stats">
                    <div class="stat-row">
                        <div class="stat-item">
                            <i class="fas fa-tint"></i>
                            <span>Độ ẩm: <?php echo $latest['DoAm']; ?>%</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-wind"></i>
                            <span>Gió: <?php echo $latest['TocDoGio']; ?>m/s</span>
                        </div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-item">
                            <i class="fas fa-cloud-rain"></i>
                            <span>Mưa: <?php echo $latest['LuongMua']; ?>mm</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-sun"></i>
                            <span>UV: <?php echo $latest['ChiSoUV']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="update-time">
            Cập nhật lúc: <?php echo date('H:i d/m/Y', strtotime($latest['ThoiDiem'])); ?>
        </div>
    </div>
<?php endif; ?>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($thoitiet); ?></span>
                    <span class="stat-label">Bản ghi thời tiết</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($vungList); ?></span>
                    <span class="stat-label">Vùng theo dõi</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php 
                        $avgTemp = 0;
                        if (!empty($thoitiet)) {
                            $avgTemp = array_sum(array_column($thoitiet, 'NhietDo')) / count($thoitiet);
                        }
                        echo number_format($avgTemp, 1); 
                    ?>°C</span>
                    <span class="stat-label">Nhiệt độ trung bình</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?php 
                        $totalRain = array_sum(array_column($thoitiet, 'LuongMua'));
                        echo number_format($totalRain, 1); 
                    ?>mm</span>
                    <span class="stat-label">Tổng lượng mưa</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo vùng, mô tả...">
            </div>
            <div class="col-md-3">
                <select class="form-select search-input" id="filterVung">
                    <option value="">Tất cả vùng</option>
                    <?php foreach($vungList as $vung): ?>
                        <option value="<?php echo $vung['MaVung']; ?>"><?php echo $vung['MaVung']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select search-input" id="filterTrangThai">
                    <option value="">Tất cả tình trạng</option>
                    <option value="mua">Có mưa</option>
                    <option value="nang-nong">Nắng nóng</option>
                    <option value="co-gio">Có gió</option>
                    <option value="am-uot">Ẩm ướt</option>
                    <option value="binh-thuong">Bình thường</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" id="filterDate" class="form-control search-input" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
    </div>

    <!-- Weather List -->
    <div class="row" id="thoitietContainer">
        <?php if (empty($thoitiet)): ?>
            <div class="col-12">
                <div class="no-weather">
                    <i class="fas fa-cloud"></i>
                    <h3>Chưa có dữ liệu thời tiết</h3>
                    <p>Hãy thêm thông tin thời tiết đầu tiên</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($thoitiet as $weather): 
                $trangThai = getTrangThaiThoiTiet($weather['NhietDo'], $weather['DoAm'], $weather['LuongMua'], $weather['TocDoGio'], $weather['ChiSoUV']);
                $uvLevel = getUVLevel($weather['ChiSoUV']);
                $huongGioText = getHuongGioText($weather['HuongGio']);
                
                // Phân loại tình trạng cho filter
                $filterClass = '';
                if ($weather['LuongMua'] > 0) $filterClass = 'mua';
                elseif ($weather['ChiSoUV'] > 8) $filterClass = 'nang-nong';
                elseif ($weather['TocDoGio'] > 3) $filterClass = 'co-gio';
                elseif ($weather['DoAm'] > 80) $filterClass = 'am-uot';
                else $filterClass = 'binh-thuong';
            ?>
                <div class="col-lg-6 thoitiet-item" 
                     data-vung="<?php echo strtolower($weather['MaVung']); ?>"
                     data-mota="<?php echo strtolower($weather['MoTa']); ?>"
                     data-trangthai="<?php echo $filterClass; ?>"
                     data-date="<?php echo date('Y-m-d', strtotime($weather['ThoiDiem'])); ?>">
                    <div class="weather-card">
                        <div class="weather-main">
                            <div class="weather-icon-small">
                                <i class="<?php echo $trangThai['icon']; ?>"></i>
                            </div>
                            <div class="weather-primary">
                                <div class="temp-main"><?php echo number_format($weather['NhietDo'], 1); ?>°C</div>
                                <div class="status-badge <?php echo $trangThai['class']; ?>">
                                    <?php echo $trangThai['status']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="weather-info">
                            <div class="info-row">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><strong>Vùng:</strong> <?php echo htmlspecialchars($weather['MaVung']); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span><strong>Thời điểm:</strong> <?php echo date('H:i d/m/Y', strtotime($weather['ThoiDiem'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-item">
                                    <i class="fas fa-tint"></i>
                                    <span><strong>Độ ẩm:</strong> <?php echo $weather['DoAm']; ?>%</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-cloud-rain"></i>
                                    <span><strong>Lượng mưa:</strong> <?php echo $weather['LuongMua']; ?>mm</span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-item">
                                    <i class="fas fa-wind"></i>
                                    <span><strong>Gió:</strong> <?php echo $weather['TocDoGio']; ?>m/s (<?php echo $huongGioText; ?>)</span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-item">
                                    <i class="fas fa-sun"></i>
                                    <span><strong>Chỉ số UV:</strong> 
                                        <span class="<?php echo $uvLevel['class']; ?>">
                                            <?php echo $weather['ChiSoUV']; ?> (<?php echo $uvLevel['level']; ?>)
                                        </span>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="weather-description">
                                <i class="fas fa-comment-alt"></i>
                                <span><?php echo htmlspecialchars($weather['MoTa']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="no-weather" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/thoitiet.js"></script>

</body>
</html>