// Giong Xoai Page JavaScript - giongxoai.js
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterTinhTrang = document.getElementById('filterTinhTrang');
    const giongContainer = document.getElementById('giongContainer');
    const noResults = document.getElementById('noResults');
    const giongItems = document.querySelectorAll('.giong-item');

    /**
     * Filter giong xoai based on search input and status selection
     */
    function filterGiongXoai() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedTinhTrang = filterTinhTrang.value;
        let visibleCount = 0;

        giongItems.forEach(item => {
            const name = item.dataset.name;
            const code = item.dataset.code;
            const features = item.dataset.features;
            const status = item.dataset.status;

            // Check if item matches search criteria
            const matchesSearch = !searchTerm || 
                                name.includes(searchTerm) || 
                                code.includes(searchTerm) || 
                                features.includes(searchTerm);
            
            // Check if item matches status filter
            const matchesTinhTrang = !selectedTinhTrang || status === selectedTinhTrang;

            // Show/hide item based on matches
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
            giongContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            giongContainer.style.display = 'flex';
        }
    }

    /**
     * Initialize page animations with mango theme
     */
    function initializeAnimations() {
        // Animation for cards on load with staggered effect
        giongItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(50px) rotateY(-15deg)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.8s cubic-bezier(0.4, 0.0, 0.2, 1)';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0) rotateY(0)';
            }, index * 150);
        });
    }

    /**
     * Add mango-themed hover effects
     */
    function addHoverEffects() {
        giongItems.forEach(item => {
            const card = item.querySelector('.giong-card');
            const avatar = item.querySelector('.giong-avatar');
            
            card.addEventListener('mouseenter', () => {
                avatar.style.transform = 'scale(1.1) rotate(5deg)';
                avatar.style.boxShadow = '0 15px 35px rgba(76, 175, 80, 0.4)';
            });
            
            card.addEventListener('mouseleave', () => {
                avatar.style.transform = 'scale(1) rotate(0deg)';
                avatar.style.boxShadow = '0 10px 25px rgba(76, 175, 80, 0.3)';
            });
        });
    }

    /**
     * Add smooth scrolling for better UX
     */
    function addSmoothScrolling() {
        const searchBox = document.querySelector('.search-box');
        if (searchBox) {
            searchInput.addEventListener('focus', () => {
                searchBox.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            });
        }
    }

    /**
     * Add keyboard shortcuts
     */
    function addKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Focus search input with Ctrl+F
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }
            
            // Clear search with Escape
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.value = '';
                filterGiongXoai();
            }
            
            // Navigate with arrow keys when search is focused
            if (document.activeElement === searchInput) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    filterTinhTrang.focus();
                }
            }
        });
    }

    /**
     * Add loading states for better UX
     */
    function addLoadingStates() {
        let filterTimeout;
        
        const originalFilterGiongXoai = filterGiongXoai;
        filterGiongXoai = function() {
            clearTimeout(filterTimeout);
            
            // Add loading state
            giongContainer.style.opacity = '0.7';
            
            filterTimeout = setTimeout(() => {
                originalFilterGiongXoai();
                giongContainer.style.opacity = '1';
            }, 200);
        };
    }

    /**
     * Add status indicator animations
     */
    function addStatusAnimations() {
        const statusBadges = document.querySelectorAll('.badge-status');
        statusBadges.forEach(badge => {
            if (badge.classList.contains('còn-sử-dụng')) {
                badge.addEventListener('mouseenter', () => {
                    badge.style.animation = 'pulse 1s infinite';
                });
                badge.addEventListener('mouseleave', () => {
                    badge.style.animation = 'none';
                });
            }
        });
    }

    /**
     * Add search suggestions based on existing data
     */
    function addSearchSuggestions() {
        const suggestions = [];
        giongItems.forEach(item => {
            const name = item.dataset.name;
            const code = item.dataset.code;
            if (name) suggestions.push(name);
            if (code) suggestions.push(code);
        });
        
        // You could implement autocomplete here
        console.log('Available search terms:', [...new Set(suggestions)]);
    }

    /**
     * Initialize all functionality
     */
    function init() {
        // Add event listeners
        searchInput.addEventListener('input', filterGiongXoai);
        filterTinhTrang.addEventListener('change', filterGiongXoai);
        
        // Initialize features
        initializeAnimations();
        addHoverEffects();
        addSmoothScrolling();
        addKeyboardShortcuts();
        addLoadingStates();
        addStatusAnimations();
        addSearchSuggestions();
        
        // Add dynamic placeholder text for mango varieties
        let placeholderTexts = [
            'Tìm kiếm theo tên giống, mã giống, đặc điểm...',
            'Nhập tên giống xoài...',
            'Nhập mã giống...',
            'Tìm theo đặc điểm...',
            'Ví dụ: Cát Hòa Lộc, Tượng, Keo...'
        ];
        
        let currentPlaceholder = 0;
        setInterval(() => {
            searchInput.placeholder = placeholderTexts[currentPlaceholder];
            currentPlaceholder = (currentPlaceholder + 1) % placeholderTexts.length;
        }, 4000);
    }

    // Add CSS for pulse animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);

    // Initialize the page
    init();
});