// JavaScript cho trang mùa vụ
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterNam = document.getElementById('filterNam');
    const filterTrangThai = document.getElementById('filterTrangThai');
    const muavuContainer = document.getElementById('muavuContainer');
    const noResults = document.getElementById('noResults');
    const muavuItems = document.querySelectorAll('.muavu-item');

    function filterMuaVu() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedNam = filterNam.value;
        const selectedTrangThai = filterTrangThai.value;
        let visibleCount = 0;

        muavuItems.forEach(item => {
            const ma = item.dataset.ma;
            const nam = item.dataset.nam;
            const trangThai = item.dataset.trangthai;

            const matchesSearch = !searchTerm || ma.includes(searchTerm) || nam.includes(searchTerm);
            const matchesNam = !selectedNam || nam === selectedNam;
            const matchesTrangThai = !selectedTrangThai || trangThai === selectedTrangThai;

            if (matchesSearch && matchesNam && matchesTrangThai) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm || selectedNam || selectedTrangThai)) {
            noResults.style.display = 'block';
            muavuContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            muavuContainer.style.display = 'flex';
        }
    }

    // Event listeners
    searchInput.addEventListener('input', filterMuaVu);
    filterNam.addEventListener('change', filterMuaVu);
    filterTrangThai.addEventListener('change', filterMuaVu);

    // Animation for cards on load
    muavuItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Progress bar animation for active seasons
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });

    // Highlight current season
    const today = new Date();
    muavuItems.forEach(item => {
        const card = item.querySelector('.muavu-card');
        const trangThai = item.dataset.trangthai;
        
        if (trangThai === 'dang-dien-ra') {
            card.style.borderColor = '#4caf50';
            card.style.borderWidth = '3px';
            
            // Add pulsing effect
            setInterval(() => {
                card.style.boxShadow = card.style.boxShadow === 'rgba(76, 175, 80, 0.4) 0px 25px 50px 0px' 
                    ? '0 15px 35px rgba(76, 175, 80, 0.1)' 
                    : '0 25px 50px rgba(76, 175, 80, 0.4)';
            }, 2000);
        }
    });

    // Update countdown timers
    function updateCountdowns() {
        const countdownElements = document.querySelectorAll('.progress-info span');
        
        countdownElements.forEach(element => {
            const item = element.closest('.muavu-item');
            const trangThai = item.dataset.trangthai;
            
            // Logic to update countdown could be added here
            // This would require more data from the backend
        });
    }

    // Update countdowns every minute
    setInterval(updateCountdowns, 60000);
});