document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterTinhTrang = document.getElementById('filterTinhTrang');
    const vungContainer = document.getElementById('vungtrongContainer');
    const noResults = document.getElementById('noResults');
    const vungItems = document.querySelectorAll('.vung-item');

    function filterVungs() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedTinhTrang = filterTinhTrang.value.toLowerCase();
        let visibleCount = 0;

        vungItems.forEach(item => {
            const name = item.dataset.name;
            const address = item.dataset.address;
            const tinhtrang = item.dataset.tinhtrang;

            const matchesSearch = !searchTerm || 
                                  name.includes(searchTerm) || 
                                  address.includes(searchTerm);

            const matchesTinhTrang = !selectedTinhTrang || tinhtrang === selectedTinhTrang;

            if (matchesSearch && matchesTinhTrang) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm || selectedTinhTrang)) {
            noResults.style.display = 'block';
            vungContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            vungContainer.style.display = 'flex';
        }
    }

    // Event listeners
    searchInput.addEventListener('input', filterVungs);
    filterTinhTrang.addEventListener('change', filterVungs);

    // Animation for cards on load
    vungItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
