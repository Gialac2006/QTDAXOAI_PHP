// Extracted from honongdan.php - combined JS
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterLoaiDat = document.getElementById('filterLoaiDat');
    const farmersContainer = document.getElementById('farmersContainer');
    const noResults = document.getElementById('noResults');
    const farmerItems = document.querySelectorAll('.farmer-item');

    function filterFarmers() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedLoaiDat = filterLoaiDat.value;
        let visibleCount = 0;

        farmerItems.forEach(item => {
            const name = item.dataset.name;
            const cccd = item.dataset.cccd;
            const address = item.dataset.address;
            const loaiDat = item.dataset.loaidat;

            const matchesSearch = !searchTerm || 
                                name.includes(searchTerm) || 
                                cccd.includes(searchTerm) || 
                                address.includes(searchTerm);
            
            const matchesLoaiDat = !selectedLoaiDat || loaiDat === selectedLoaiDat;

            if (matchesSearch && matchesLoaiDat) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm || selectedLoaiDat)) {
            noResults.style.display = 'block';
            farmersContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            farmersContainer.style.display = 'flex';
        }
    }

    // Event listeners
    searchInput.addEventListener('input', filterFarmers);
    filterLoaiDat.addEventListener('change', filterFarmers);

    // Animation for cards on load
    farmerItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});