document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const thuocContainer = document.getElementById('thuocContainer');
    const noResults = document.getElementById('noResults');
    const thuocItems = document.querySelectorAll('.thuoc-item');

    function filterThuoc() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        thuocItems.forEach(item => {
            const ten = item.dataset.ten;
            const hoatchat = item.dataset.hoatchat;

            const matchesSearch = !searchTerm || 
                                  ten.includes(searchTerm) || 
                                  hoatchat.includes(searchTerm);

            if (matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            thuocContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            thuocContainer.style.display = 'flex';
            thuocContainer.style.flexWrap = 'wrap';
            thuocContainer.style.gap = '20px';
        }
    }

    searchInput.addEventListener('input', filterThuoc);

    // Animation
    thuocItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 120);
    });
});
