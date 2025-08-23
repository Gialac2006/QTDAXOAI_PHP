// JavaScript cho trang thời tiết
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterVung = document.getElementById('filterVung');
    const filterTrangThai = document.getElementById('filterTrangThai');
    const filterDate = document.getElementById('filterDate');
    const thoitietContainer = document.getElementById('thoitietContainer');
    const noResults = document.getElementById('noResults');
    const thoitietItems = document.querySelectorAll('.thoitiet-item');

    function filterThoiTiet() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedVung = filterVung.value.toLowerCase();
        const selectedTrangThai = filterTrangThai.value;
        const selectedDate = filterDate.value;
        let visibleCount = 0;

        thoitietItems.forEach(item => {
            const vung = item.dataset.vung;
            const mota = item.dataset.mota;
            const trangThai = item.dataset.trangthai;
            const date = item.dataset.date;

            const matchesSearch = !searchTerm || 
                                vung.includes(searchTerm) || 
                                mota.includes(searchTerm);
            
            const matchesVung = !selectedVung || vung.includes(selectedVung);
            const matchesTrangThai = !selectedTrangThai || trangThai === selectedTrangThai;
            const matchesDate = !selectedDate || date === selectedDate;

            if (matchesSearch && matchesVung && matchesTrangThai && matchesDate) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm || selectedVung || selectedTrangThai || selectedDate !== new Date().toISOString().split('T')[0])) {
            noResults.style.display = 'block';
            thoitietContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            thoitietContainer.style.display = 'flex';
        }
    }

    // Event listeners
    searchInput.addEventListener('input', filterThoiTiet);
    filterVung.addEventListener('change', filterThoiTiet);
    filterTrangThai.addEventListener('change', filterThoiTiet);
    filterDate.addEventListener('change', filterThoiTiet);

    // Animation for cards on load
    thoitietItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Add weather effects to cards
    function addWeatherEffects() {
        thoitietItems.forEach(item => {
            const card = item.querySelector('.weather-card');
            const trangThai = item.dataset.trangthai;
            
            // Add specific effects based on weather condition
            switch(trangThai) {
                case 'mua':
                    card.classList.add('rain-effect');
                    break;
                case 'nang-nong':
                    card.classList.add('sunny-effect');
                    break;
                case 'co-gio':
                    card.classList.add('windy-effect');
                    break;
            }
        });
    }

    // Temperature color coding
    function applyTemperatureColors() {
        const tempElements = document.querySelectorAll('.temp-main');
        
        tempElements.forEach(element => {
            const temp = parseFloat(element.textContent);
            
            if (temp >= 35) {
                element.style.color = '#e74c3c';
            } else if (temp >= 30) {
                element.style.color = '#f39c12';
            } else if (temp >= 25) {
                element.style.color = '#27ae60';
            } else {
                element.style.color = '#3498db';
            }
        });
    }

    // UV Index warning
    function checkUVWarnings() {
        const uvElements = document.querySelectorAll('.uv-very-high, .uv-extreme');
        
        if (uvElements.length > 0) {
            // Create UV warning notification
            const uvWarning = document.createElement('div');
            uvWarning.className = 'alert alert-warning alert-dismissible fade show';
            uvWarning.style.position = 'fixed';
            uvWarning.style.top = '20px';
            uvWarning.style.right = '20px';
            uvWarning.style.zIndex = '9999';
            uvWarning.style.maxWidth = '300px';
            uvWarning.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Cảnh báo UV cao!</strong><br>
                Chỉ số UV đang ở mức nguy hiểm. Hãy tránh ra ngoài vào giờ nắng gắt.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(uvWarning);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (uvWarning.parentNode) {
                    uvWarning.parentNode.removeChild(uvWarning);
                }
            }, 5000);
        }
    }

    // Real-time weather update simulation
    function simulateWeatherUpdate() {
        const currentWeather = document.querySelector('.current-weather');
        if (currentWeather) {
            // Add update animation
            currentWeather.style.transform = 'scale(1.02)';
            currentWeather.style.boxShadow = '0 25px 50px rgba(30, 144, 255, 0.2)';
            
            setTimeout(() => {
                currentWeather.style.transform = 'scale(1)';
                currentWeather.style.boxShadow = '0 20px 40px rgba(30, 144, 255, 0.1)';
            }, 1000);
        }
    }

    // Weather advice based on conditions
    function showWeatherAdvice() {
        const latestWeather = thoitietItems[0];
        if (!latestWeather) return;

        const trangThai = latestWeather.dataset.trangthai;
        let advice = '';
        let adviceClass = '';

        switch(trangThai) {
            case 'mua':
                advice = '🌧️ Có mưa - Hãy hoãn việc phun thuốc và thu hoạch';
                adviceClass = 'alert-info';
                break;
            case 'nang-nong':
                advice = '☀️ Nắng nóng - Tưới nước nhiều hơn và tránh làm việc ngoài trời';
                adviceClass = 'alert-warning';
                break;
            case 'co-gio':
                advice = '💨 Gió mạnh - Không nên phun thuốc, có thể làm đổ cây';
                adviceClass = 'alert-warning';
                break;
            case 'am-uot':
                advice = '💧 Độ ẩm cao - Chú ý phòng chống bệnh nấm';
                adviceClass = 'alert-success';
                break;
            default:
                advice = '🌤️ Thời tiết thuận lợi cho hoạt động nông nghiệp';
                adviceClass = 'alert-success';
        }

        // Show advice notification
        if (advice) {
            const adviceElement = document.createElement('div');
            adviceElement.className = `alert ${adviceClass} alert-dismissible fade show weather-advice`;
            adviceElement.style.position = 'sticky';
            adviceElement.style.top = '20px';
            adviceElement.style.zIndex = '1000';
            adviceElement.style.marginBottom = '20px';
            adviceElement.innerHTML = `
                ${advice}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            const firstChild = container.children[1]; // After header
            container.insertBefore(adviceElement, firstChild);
        }
    }

    // Initialize effects
    addWeatherEffects();
    applyTemperatureColors();
    checkUVWarnings();
    showWeatherAdvice();

    // Auto-refresh simulation every 5 minutes
    setInterval(() => {
        simulateWeatherUpdate();
        
        // Update timestamp
        const updateTime = document.querySelector('.update-time');
        if (updateTime) {
            const now = new Date();
            updateTime.textContent = `Cập nhật lúc: ${now.toLocaleTimeString('vi-VN')} ${now.toLocaleDateString('vi-VN')}`;
        }
    }, 300000); // 5 minutes

    // Quick filter buttons
    function createQuickFilters() {
        const quickFilters = document.createElement('div');
        quickFilters.className = 'quick-filters mb-3';
        quickFilters.innerHTML = `
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" data-filter="all">Tất cả</button>
                <button type="button" class="btn btn-outline-danger btn-sm" data-filter="nang-nong">Nắng nóng</button>
                <button type="button" class="btn btn-outline-info btn-sm" data-filter="mua">Mưa</button>
                <button type="button" class="btn btn-outline-success btn-sm" data-filter="binh-thuong">Bình thường</button>
            </div>
        `;
        
        const searchBox = document.querySelector('.search-box');
        searchBox.parentNode.insertBefore(quickFilters, searchBox);
        
        // Add click events
        const filterButtons = quickFilters.querySelectorAll('button');
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                // Set filter value
                const filterValue = this.dataset.filter === 'all' ? '' : this.dataset.filter;
                filterTrangThai.value = filterValue;
                filterThoiTiet();
            });
        });
        
        // Set first button as active
        filterButtons[0].classList.add('active');
    }

    createQuickFilters();
});