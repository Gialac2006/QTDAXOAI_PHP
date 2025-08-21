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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a8d5ba 0%, #c8e6c9 30%, #fff9c4 70%, #f0f4c3 100%);
            min-height: 100vh;
        }

        .farmer-header {
            background: linear-gradient(rgba(255,140,0,0.7), rgba(255,165,0,0.7)), 
                        url('assets/img/xoai-background.jpg') center/cover;
            color: white;
            padding: 100px 0 60px;
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 5px solid #ff8c00;
        }

        .farmer-header h1 {
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 20px;
        }

        .farmer-header p {
            font-size: 1.2rem;
            margin-bottom: 0;
        }

        .farmer-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(255, 140, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 165, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .farmer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(255, 140, 0, 0.2);
            border-color: rgba(255, 165, 0, 0.4);
        }

        .farmer-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #81c784, #aed581, #dcedc8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2e7d32;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px rgba(129, 199, 132, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.8);
        }

        .farmer-name {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }

        .farmer-info {
            color: #34495e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .farmer-info i {
            width: 25px;
            color: #689f38;
            margin-right: 10px;
        }

        .stats-section {
            background: rgba(255,255,255,0.9);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            text-align: center;
        }

        .stat-item {
            margin: 0 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #ff8c00;
            display: block;
            text-shadow: 2px 2px 4px rgba(255, 140, 0, 0.3);
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .search-box {
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .search-input {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #ff8c00;
            box-shadow: 0 0 0 0.2rem rgba(255, 140, 0, 0.25);
        }

        .badge-custom {
            background: linear-gradient(45deg, #ff8c00, #ffa500);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(255, 140, 0, 0.3);
        }

        .no-farmers {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .no-farmers i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>

<!-- Navbar giống như trang chủ -->
<nav class="navbar">
    <ul class="navbar-menu">
        <li class="navbar-item"><a href="home.html">Trang chủ</a></li>
        <li class="navbar-item"><a href="aboutus.html">Tụi tui</a></li>
        <li class="navbar-item active">Nông dân
            <div class="navbar-dropdown">
                <a href="honongdan.php">Danh sách nông dân</a>
                <a href="#">Thêm mới</a>
            </div>
        </li>
        <li class="navbar-item">Đất đai
            <div class="navbar-dropdown">
                <a href="#">Sổ thửa & bản đồ</a>
            </div>
        </li>
        <li class="navbar-item">Sản xuất
            <div class="navbar-dropdown">
                <a href="#">Theo dõi mùa vụ</a>
                <a href="#">Chất lượng & bệnh hại</a>
            </div>
        </li>
        <li class="navbar-item">Thị trường
            <div class="navbar-dropdown">
                <a href="#">Giá nông sản</a>
                <a href="#">Báo cáo xuất khẩu</a>
            </div>
        </li>
        <li class="navbar-item">Đào tạo
            <div class="navbar-dropdown">
                <a href="#">Tài liệu hướng dẫn</a>
                <a href="#">Lịch tập huấn</a>
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
<section class="farmer-header">
    <div class="container">
        <h1>HỘ NÔNG DÂN VÙNG XOÀI CÁT HÒA LỘC</h1>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterLoaiDat = document.getElementById('filterLoaiDat');
    const farmersContainer = document.getElementById('farmersContainer');
    const noResults = document.getElementById('noResults');
    const farmerItems = document.querySelectorAll('.farmer-item');

    function filterFarmers() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedLoaiDat = filterLoaiDat.value;
        let visibleCount = 0;

        farmerItems.forEach(item => {
            const name = item.dataset.name;
            const cccd = item.dataset.cccd;
            const address = item.dataset.address;
            const loaiDat = item.dataset.loaidat;

            const matchesSearch = !searchTerm || 
                                name.includes(searchTerm) || 
                                cccd.includes(searchTerm) || 
                                address.includes(searchTerm);
            
            const matchesLoaiDat = !selectedLoaiDat || loaiDat === selectedLoaiDat;

            if (matchesSearch && matchesLoaiDat) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm || selectedLoaiDat)) {
            noResults.style.display = 'block';
            farmersContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            farmersContainer.style.display = 'flex';
        }
    }

    // Event listeners
    searchInput.addEventListener('input', filterFarmers);
    filterLoaiDat.addEventListener('change', filterFarmers);

    // Animation for cards on load
    farmerItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

</body>
</html>