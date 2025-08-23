document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const phanbonContainer = document.getElementById('phanbonContainer');
    const noResults = document.getElementById('noResults');
    const phanbonItems = document.querySelectorAll('.phanbon-item');

    function filterPhanBon() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        phanbonItems.forEach(item => {
            const ten = item.dataset.ten;
            const loai = item.dataset.loai;

            const matchesSearch = !searchTerm || 
                                  ten.includes(searchTerm) || 
                                  loai.includes(searchTerm);

            if (matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            phanbonContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            phanbonContainer.style.display = 'flex';
            phanbonContainer.style.flexWrap = 'wrap';
            phanbonContainer.style.gap = '20px';
        }
    }

    searchInput.addEventListener('input', filterPhanBon);

    // Animation
    phanbonItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 120);
    });
});
