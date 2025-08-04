// Navbar scroll behavior
let lastScrollTop = 0;
const navbar = document.querySelector('.navbar');

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Navbar scroll functionality
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Hide navbar when scrolling down, show when scrolling up
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            navbar.style.top = "-80px";
        } else {
            navbar.style.top = "0";
        }
        
        // Prevent negative scroll values
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });

    // Video autoplay fallback (for mobile devices)
    const video = document.querySelector('.video-bg');
    if (video) {
        video.addEventListener('loadeddata', function() {
            if (video.paused) {
                video.play().catch(function(error) {
                    console.log('Auto-play was prevented:', error);
                });
            }
        });
    }

    // Smooth scrolling for navigation links (if needed)
    const navLinks = document.querySelectorAll('.navbar-item a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Button click handlers
    const experienceBtn = document.querySelector('.poster-btn:first-child');
    if (experienceBtn) {
        experienceBtn.addEventListener('click', function() {
            // Add your system experience logic here
            alert('Chức năng trải nghiệm hệ thống sẽ được phát triển!');
        });
    }
});