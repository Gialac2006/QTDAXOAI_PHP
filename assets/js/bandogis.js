document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const gisContainer = document.getElementById('gisContainer');
    const noResults = document.getElementById('noResults');
    const gisItems = document.querySelectorAll('.gis-item');

    function filterGIS() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        gisItems.forEach(item => {
            const mavung = item.dataset.mavung;
            const nhanten = item.dataset.nhanten;

            const matchesSearch = !searchTerm || 
                                  mavung.includes(searchTerm) || 
                                  nhanten.includes(searchTerm);

            if (matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            gisContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            gisContainer.style.display = 'flex';
            gisContainer.style.flexWrap = 'wrap';
            gisContainer.style.gap = '20px';
        }
    }

    searchInput.addEventListener('input', filterGIS);

    // Animation
    gisItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 120);
    });
});
