// JavaScript đơn giản cho trang thiết bị máy móc
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterLoai = document.getElementById('filterLoaiThietBi');
    const filterTinhTrang = document.getElementById('filterTinhTrang');
    const container = document.getElementById('equipmentContainer');
    const noResults = document.getElementById('noResults');

    // Hàm lọc thiết bị
    function filterEquipment() {
        const searchText = searchInput.value.toLowerCase();
        const selectedLoai = filterLoai.value;
        const selectedTinhTrang = filterTinhTrang.value;
        
        const hoSections = document.querySelectorAll('.ho-equipment-section');
        let totalVisible = 0;

        hoSections.forEach(section => {
            const items = section.querySelectorAll('.equipment-item');
            let visibleInSection = 0;

            items.forEach(item => {
                const name = item.dataset.name || '';
                const maho = item.dataset.maho || '';
                const loai = item.dataset.loai || '';
                const tinhTrang = item.dataset.tinhtrang || '';

                // Kiểm tra điều kiện lọc
                const matchSearch = !searchText || 
                                 name.includes(searchText) || 
                                 maho.includes(searchText);
                const matchLoai = !selectedLoai || loai === selectedLoai;
                const matchTinhTrang = !selectedTinhTrang || tinhTrang === selectedTinhTrang;

                if (matchSearch && matchLoai && matchTinhTrang) {
                    item.style.display = 'block';
                    visibleInSection++;
                    totalVisible++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Hiện/ẩn section theo số thiết bị hiện
            if (visibleInSection > 0) {
                section.style.display = 'block';
                // Cập nhật số lượng thiết bị
                const countText = section.querySelector('.equipment-count');
                if (countText) {
                    countText.textContent = visibleInSection + ' thiết bị';
                }
            } else {
                section.style.display = 'none';
            }
        });

        // Hiển thị thông báo "không tìm thấy"
        if (totalVisible === 0 && (searchText || selectedLoai || selectedTinhTrang)) {
            noResults.style.display = 'block';
            container.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            container.style.display = 'block';
        }
    }

    // Gắn sự kiện
    searchInput.addEventListener('input', filterEquipment);
    filterLoai.addEventListener('change', filterEquipment);
    filterTinhTrang.addEventListener('change', filterEquipment);

    // Hiệu ứng xuất hiện từ từ
    const items = document.querySelectorAll('.equipment-item');
    items.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Nút xóa bộ lọc
    function addClearButton() {
        const searchBox = document.querySelector('.search-box .row');
        if (searchBox && !document.getElementById('clearBtn')) {
            const clearDiv = document.createElement('div');
            clearDiv.className = 'col-12 text-center mt-2';
            clearDiv.innerHTML = '<button id="clearBtn" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</button>';
            searchBox.appendChild(clearDiv);
            
            document.getElementById('clearBtn').onclick = function() {
                searchInput.value = '';
                filterLoai.value = '';
                filterTinhTrang.value = '';
                filterEquipment();
            };
        }
    }
    addClearButton();
});