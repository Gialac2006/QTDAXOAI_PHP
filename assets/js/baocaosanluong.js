document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const reportContainer = document.getElementById('reportContainer');
    const noResults = document.getElementById('noResults');
    const reportItems = document.querySelectorAll('.report-item');

    function filterReports() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        reportItems.forEach(item => {
            const vung = item.dataset.vung;
            const muavu = item.dataset.muavu;
            const chatluong = item.dataset.chatluong;

            const matchesSearch = !searchTerm || 
                                  vung.includes(searchTerm) || 
                                  muavu.includes(searchTerm) || 
                                  chatluong.includes(searchTerm);

            if (matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            reportContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            reportContainer.style.display = 'flex';
            reportContainer.style.flexWrap = 'wrap';
            reportContainer.style.gap = '20px';
        }
    }

    searchInput.addEventListener('input', filterReports);

    // Animation
    reportItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 120);
    });
});
