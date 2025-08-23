document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const giaContainer = document.getElementById('giaContainer');
    const noResults = document.getElementById('noResults');
    const giaItems = document.querySelectorAll('.gia-item');

    function filterGia() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        giaItems.forEach(item => {
            const loai = item.dataset.loai;
            const ghichu = item.dataset.ghichu;

            const matchesSearch = !searchTerm || 
                                  loai.includes(searchTerm) || 
                                  ghichu.includes(searchTerm);

            if (matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            giaContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            giaContainer.style.display = 'flex';
            giaContainer.style.flexWrap = 'wrap';
            giaContainer.style.gap = '20px';
        }
    }

    searchInput.addEventListener('input', filterGia);

    // Hiệu ứng xuất hiện
    giaItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 120);
    });
});
