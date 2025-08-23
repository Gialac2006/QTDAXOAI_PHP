// JavaScript đơn giản cho trang nhật ký phun thuốc
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterThuoc = document.getElementById('filterThuoc');
    const filterDate = document.getElementById('filterDate');
    const container = document.getElementById('sprayContainer');
    const noResults = document.getElementById('noResults');

    // Hàm lọc nhật ký phun thuốc
    function filterSprayLogs() {
        const searchText = searchInput.value.toLowerCase();
        const selectedThuoc = filterThuoc.value;
        const selectedDate = filterDate.value;
        
        const personSections = document.querySelectorAll('.person-spray-section');
        let totalVisible = 0;

        personSections.forEach(section => {
            const items = section.querySelectorAll('.spray-item');
            let visibleInSection = 0;

            items.forEach(item => {
                const person = item.dataset.person || '';
                const thuoc = item.dataset.thuoc || '';
                const date = item.dataset.date || '';

                // Kiểm tra điều kiện lọc
                const matchSearch = !searchText || 
                                 person.includes(searchText) || 
                                 thuoc.toLowerCase().includes(searchText);
                const matchThuoc = !selectedThuoc || thuoc === selectedThuoc;
                const matchDate = !selectedDate || date === selectedDate;

                if (matchSearch && matchThuoc && matchDate) {
                    item.style.display = 'block';
                    visibleInSection++;
                    totalVisible++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Hiện/ẩn section theo số lần phun hiện
            if (visibleInSection > 0) {
                section.style.display = 'block';
                // Cập nhật số lượng lần phun
                const countText = section.querySelector('.spray-count');
                if (countText) {
                    countText.textContent = visibleInSection + ' lần phun';
                }
            } else {
                section.style.display = 'none';
            }
        });

        // Hiển thị thông báo "không tìm thấy"
        if (totalVisible === 0 && (searchText || selectedThuoc || selectedDate)) {
            noResults.style.display = 'block';
            container.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            container.style.display = 'block';
        }
    }

    // Gắn sự kiện
    searchInput.addEventListener('input', filterSprayLogs);
    filterThuoc.addEventListener('change', filterSprayLogs);
    filterDate.addEventListener('change', filterSprayLogs);

    // Hiệu ứng xuất hiện từ từ
    const items = document.querySelectorAll('.spray-item');
    items.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
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
                filterThuoc.value = '';
                filterDate.value = '';
                filterSprayLogs();
            };
        }
    }
    addClearButton();

    // Click vào badge thuốc để lọc nhanh
    document.querySelectorAll('.medicine-badge').forEach(badge => {
        badge.style.cursor = 'pointer';
        badge.addEventListener('click', function() {
            const thuocText = this.textContent.trim();
            filterThuoc.value = thuocText;
            filterSprayLogs();
            
            // Scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });

    // Click vào ngày để lọc nhanh  
    document.querySelectorAll('.date-circle').forEach(dateEl => {
        dateEl.style.cursor = 'pointer';
        dateEl.addEventListener('click', function() {
            const sprayCard = this.closest('.spray-item');
            const date = sprayCard.dataset.date;
            filterDate.value = date;
            filterSprayLogs();
            
            // Scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
});