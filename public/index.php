<!DOCTYPE html>
<html lang="vi">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap"rel="stylesheet"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Vùng Trồng</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Quản Lý Vùng Trồng</h1>
            <p>Hệ thống quản lý thông tin vùng trồng thông minh</p>
        </div>

        <div class="controls">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Tìm kiếm theo mã vùng hoặc tên vùng...">
                <button class="btn btn-primary" onclick="searchVung()">Tìm kiếm</button>
                <button class="btn btn-success" onclick="openModal('add')">Thêm mới</button>
                <button class="btn btn-primary" onclick="loadData()">Làm mới</button>
            </div>
            
            <div class="pagination">
                <button class="btn btn-primary" id="prevBtn" onclick="changePage(-1)">← Trước</button>
                <span id="pageInfo">Trang 1 / 1</span>
                <button class="btn btn-primary" id="nextBtn" onclick="changePage(1)">Sau →</button>
            </div>
        </div>

        <div class="table-container">
            <div id="loading" class="loading">
                <div class="loading-spinner"></div>
                <p>Đang tải dữ liệu...</p>
            </div>

            <div id="alertContainer"></div>

            <table class="table" id="dataTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Mã Vùng</th>
                        <th>Tên Vùng</th>
                        <th>Địa Chỉ</th>
                        <th>Diện Tích (ha)</th>
                        <th>Tình Trạng</th>
                        <th>Ngày Bắt Đầu</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                </tbody>
            </table>

            <div id="emptyState" class="empty-state" style="display: none;">
                <div style="font-size: 4rem; margin-bottom: 20px;"></div>
                <h3>Không có dữ liệu</h3>
                <p>Chưa có vùng trồng nào được thêm vào hệ thống</p>
                <button class="btn btn-success" onclick="openModal('add')" style="margin-top: 20px;">
                    Thêm vùng trồng đầu tiên
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Thêm Vùng Trồng Mới</h2>
            
            <form id="vungForm">
                <div class="form-group">
                    <label for="MaVung">Mã Vùng *</label>
                    <input type="text" id="MaVung" name="MaVung" required>
                </div>

                <div class="form-group">
                    <label for="TenVung">Tên Vùng *</label>
                    <input type="text" id="TenVung" name="TenVung" required>
                </div>

                <div class="form-group">
                    <label for="DiaChi">Địa Chỉ *</label>
                    <input type="text" id="DiaChi" name="DiaChi" required>
                </div>

                <div class="form-group">
                    <label for="DienTich">Diện Tích (ha) *</label>
                    <input type="number" id="DienTich" name="DienTich" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="TinhTrang">Tình Trạng *</label>
                    <select id="TinhTrang" name="TinhTrang" required>
                        <option value="">Chọn tình trạng</option>
                        <option value="Hoạt động">Đang Trồng</option>
                        <option value="Không hoạt động">Không trồng nữa</option>
                        <option value="Bảo trì">Tạm ngưng</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="NgayBatDau">Ngày Bắt Đầu *</label>
                    <input type="date" id="NgayBatDau" name="NgayBatDau" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>