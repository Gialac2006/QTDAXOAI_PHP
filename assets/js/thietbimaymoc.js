document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const deviceContainer = document.getElementById('deviceContainer');
    const noResults = document.getElementById('noResults');
    const deviceItems = document.querySelectorAll('.device-item');

    function filterDevices() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        deviceItems.forEach(item => {
            const ten = item.dataset.ten;
            const loai = item.dataset.loai;
            const tinhtrang = item.dataset.tinhtrang;

            const matchesSearch = !searchTerm || 
                                  ten.includes(searchTerm) || 
                                  loai.includes(searchTerm) ||
                                  tinhtrang.includes(searchTerm);

            if (matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            deviceContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            deviceContainer.style.display = 'flex';
            deviceContainer.style.flexWrap = 'wrap';
            deviceContainer.style.gap = '20px';
        }
    }

    searchInput.addEventListener('input', filterDevices);

    // Animation
    deviceItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 120);
    });
});
