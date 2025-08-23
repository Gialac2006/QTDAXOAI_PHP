<?php
// Include file connect từ thư mục public
require_once __DIR__.'/public/connect.php';

try {
    // Lấy dữ liệu từ database sử dụng MySQLi
    $sql = "SELECT * FROM mua_vu ORDER BY Nam DESC, NgayBatDau DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $muavu = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
} catch(Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    $muavu = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MÙA VỤ - VÙNG XOÀI ĐỒNG THÁP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/giaodien.css">
    <link rel="stylesheet" href="assets/css/muavu.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <ul class="navbar-menu">
       <li class="navbar-item active"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item active"><a href="aboutus.html">Túi tui</a></li>
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
<section class="muavu-header">
    <div class="container">
        <h1>MÙA VỤ </h1>
        <p>Theo dõi chu kỳ mùa vụ và kế hoạch sản xuất xoài</p>
    </div>
</section>

<!-- Stats Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($muavu); ?></span>
                    <span class="stat-label">Mùa vụ</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php 
                        $currentYear = date('Y');
                        $currentSeasonCount = 0;
                        foreach($muavu as $season) {
                            if ($season['Nam'] == $currentYear) {
                                $currentSeasonCount++;
                            }
                        }
                        echo $currentSeasonCount; 
                    ?></span>
                    <span class="stat-label">Mùa vụ năm nay</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <span class="stat-number"><?php 
                        $activeSeason = 0;
                        $today = date('Y-m-d');
                        foreach($muavu as $season) {
                            if ($season['NgayBatDau'] <= $today && $season['NgayKetThuc'] >= $today) {
                                $activeSeason++;
                            }
                        }
                        echo $activeSeason; 
                    ?></span>
                    <span class="stat-label">Đang diễn ra</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <div class="row align-items-center">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Tìm kiếm theo mã mùa vụ, năm...">
            </div>
            <div class="col-md-3">
                <select class="form-select search-input" id="filterNam">
                    <option value="">Tất cả năm</option>
                    <?php 
                    $years = array_unique(array_column($muavu, 'Nam'));
                    rsort($years);
                    foreach($years as $year): 
                    ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select search-input" id="filterTrangThai">
                    <option value="">Tất cả trạng thái</option>
                    <option value="chua-bat-dau">Chưa bắt đầu</option>
                    <option value="dang-dien-ra">Đang diễn ra</option>
                    <option value="da-ket-thuc">Đã kết thúc</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Mua Vu List -->
    <div class="row" id="muavuContainer">
        <?php if (empty($muavu)): ?>
            <div class="col-12">
                <div class="no-muavu">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Chưa có mùa vụ nào được đăng ký</h3>
                    <p>Hãy thêm thông tin mùa vụ đầu tiên của bạn</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($muavu as $season): 
                // Tính trạng thái mùa vụ
                $today = date('Y-m-d');
                $trangThai = '';
                $badgeClass = '';
                
                if ($today < $season['NgayBatDau']) {
                    $trangThai = 'Chưa bắt đầu';
                    $badgeClass = 'badge-warning';
                    $statusClass = 'chua-bat-dau';
                } elseif ($today > $season['NgayKetThuc']) {
                    $trangThai = 'Đã kết thúc';
                    $badgeClass = 'badge-secondary';
                    $statusClass = 'da-ket-thuc';
                } else {
                    $trangThai = 'Đang diễn ra';
                    $badgeClass = 'badge-success';
                    $statusClass = 'dang-dien-ra';
                }
                
                // Tính số ngày còn lại hoặc đã trôi qua
                $ngayBatDau = new DateTime($season['NgayBatDau']);
                $ngayKetThuc = new DateTime($season['NgayKetThuc']);
                $ngayHienTai = new DateTime();
                
                $tongSoNgay = $ngayBatDau->diff($ngayKetThuc)->days;
                
                if ($statusClass == 'dang-dien-ra') {
                    $ngayConLai = $ngayHienTai->diff($ngayKetThuc)->days;
                    $tieuDe = "Còn lại: {$ngayConLai} ngày";
                } elseif ($statusClass == 'chua-bat-dau') {
                    $ngayConLai = $ngayHienTai->diff($ngayBatDau)->days;
                    $tieuDe = "Bắt đầu sau: {$ngayConLai} ngày";
                } else {
                    $ngayKetThuc = $ngayHienTai->diff(new DateTime($season['NgayKetThuc']))->days;
                    $tieuDe = "Đã kết thúc: {$ngayKetThuc} ngày";
                }
            ?>
                <div class="col-lg-6 muavu-item" 
                     data-ma="<?php echo strtolower($season['MaMuaVu']); ?>"
                     data-nam="<?php echo $season['Nam']; ?>"
                     data-trangthai="<?php echo $statusClass; ?>">
                    <div class="muavu-card">
                        <div class="muavu-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        
                        <div class="status-badge <?php echo $badgeClass; ?>">
                            <?php echo $trangThai; ?>
                        </div>
                        
                        <h3 class="muavu-name"><?php echo htmlspecialchars($season['MaMuaVu']); ?></h3>
                        
                        <div class="muavu-info">
                            <i class="fas fa-calendar-alt"></i>
                            <span><strong>Năm:</strong> <?php echo $season['Nam']; ?></span>
                        </div>
                        
                        <div class="muavu-info">
                            <i class="fas fa-tag"></i>
                            <span><strong>Đợt:</strong> <?php echo htmlspecialchars($season['Dot']); ?></span>
                        </div>
                        
                        <div class="muavu-info">
                            <i class="fas fa-play-circle"></i>
                            <span><strong>Ngày bắt đầu:</strong> <?php echo date('d/m/Y', strtotime($season['NgayBatDau'])); ?></span>
                        </div>
                        
                        <div class="muavu-info">
                            <i class="fas fa-stop-circle"></i>
                            <span><strong>Ngày kết thúc:</strong> <?php echo date('d/m/Y', strtotime($season['NgayKetThuc'])); ?></span>
                        </div>
                        
                        <div class="muavu-info">
                            <i class="fas fa-clock"></i>
                            <span><strong>Thời gian:</strong> <?php echo $tongSoNgay; ?> ngày</span>
                        </div>
                        
                        <div class="muavu-progress">
                            <div class="progress-info">
                                <span><?php echo $tieuDe; ?></span>
                            </div>
                            <?php if ($statusClass == 'dang-dien-ra'): 
                                $phanTram = (($tongSoNgay - $ngayConLai) / $tongSoNgay) * 100;
                            ?>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo $phanTram; ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="no-muavu" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Vui lòng thử tìm kiếm với từ khóa khác</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
<script src="assets/js/muavu.js"></script>

</body>
</html>