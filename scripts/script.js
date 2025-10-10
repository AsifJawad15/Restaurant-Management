// Restaurant Management Landing Page Scripts

document.addEventListener('DOMContentLoaded', function() {
    
    // Reviews Carousel Functionality
    const reviewsContainer = document.querySelector('.reviews-carousel-container');
    if (reviewsContainer) {
        initializeReviewsCarousel();
    }
    
    function initializeReviewsCarousel() {
        console.log('Initializing reviews carousel with content replacement...');
        
        // Get reviews data from the JSON script tag
        const reviewsDataElement = document.getElementById('reviewsData');
        let reviewsData = [];
        
        if (reviewsDataElement) {
            try {
                reviewsData = JSON.parse(reviewsDataElement.textContent);
                console.log('Parsed reviews data:', reviewsData);
            } catch (e) {
                console.error('Error parsing reviews data:', e);
                return;
            }
        }
        
        // Check if reviews data exists
        if (!reviewsData || reviewsData.length === 0) {
            console.log('No reviews data found');
            return;
        }
        
        console.log(`Found ${reviewsData.length} reviews for carousel`);
        
        // Get carousel elements by ID (matching the HTML structure)
        const displayElement = document.querySelector('.single-review-display');
        const ratingElement = document.getElementById('reviewStars');
        const commentElement = document.getElementById('reviewComment');
        const customerNameElement = document.getElementById('reviewCustomer');
        const itemElement = document.getElementById('reviewItem');
        const reviewDateElement = document.getElementById('reviewDate');
        
        if (!displayElement || !ratingElement || !commentElement || !customerNameElement || !reviewDateElement) {
            console.log('Required carousel elements not found');
            console.log('Display:', displayElement);
            console.log('Rating:', ratingElement);
            console.log('Comment:', commentElement);
            console.log('Customer:', customerNameElement);
            console.log('Date:', reviewDateElement);
            return;
        }
        
        let currentIndex = 0;
        let intervalId;
        
        function createStarRating(rating) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    starsHtml += '<i class="fas fa-star"></i>';
                } else {
                    starsHtml += '<i class="fas fa-star text-muted"></i>';
                }
            }
            return starsHtml;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        function showReview(index) {
            console.log(`Displaying review ${index + 1} of ${reviewsData.length}`);
            
            const review = reviewsData[index];
            
            // Add fade out effect
            displayElement.style.opacity = '0.7';
            displayElement.style.transform = 'scale(0.95)';
            
            // Update content after short delay for smooth transition
            setTimeout(() => {
                ratingElement.innerHTML = createStarRating(parseInt(review.rating));
                commentElement.textContent = review.comment;
                customerNameElement.textContent = review.customer_name;
                itemElement.innerHTML = `<i class="fas fa-utensils me-2"></i>${review.item_name || 'Menu Item'}`;
                reviewDateElement.innerHTML = `<i class="fas fa-calendar me-2"></i>${formatDate(review.created_at)}`;
                
                // Update dots if they exist
                const dots = document.querySelectorAll('.carousel-dots .dot');
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
                
                // Fade back in
                displayElement.style.opacity = '1';
                displayElement.style.transform = 'scale(1)';
            }, 200);
        }
        
        function nextReview() {
            currentIndex = (currentIndex + 1) % reviewsData.length;
            showReview(currentIndex);
        }
        
        function prevReview() {
            currentIndex = (currentIndex - 1 + reviewsData.length) % reviewsData.length;
            showReview(currentIndex);
        }
        
        function startAutoSlide() {
            intervalId = setInterval(nextReview, 4000); // Change review every 4 seconds
        }
        
        function stopAutoSlide() {
            if (intervalId) {
                clearInterval(intervalId);
            }
        }
        
        // Initialize with first review
        showReview(0);
        startAutoSlide();
        
        // Pause on hover
        displayElement.addEventListener('mouseenter', stopAutoSlide);
        displayElement.addEventListener('mouseleave', startAutoSlide);
        
        // Add navigation buttons if they exist
        const prevBtn = document.getElementById('prevReview');
        const nextBtn = document.getElementById('nextReview');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                stopAutoSlide();
                prevReview();
                setTimeout(startAutoSlide, 3000); // Resume after 3 seconds
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                stopAutoSlide();
                nextReview();
                setTimeout(startAutoSlide, 3000); // Resume after 3 seconds
            });
        }
        
        // Add click handlers for dots
        const dots = document.querySelectorAll('.carousel-dots .dot');
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                stopAutoSlide();
                currentIndex = index;
                showReview(currentIndex);
                setTimeout(startAutoSlide, 3000); // Resume after 3 seconds
            });
        });
        
        console.log('Reviews carousel initialized with content replacement approach');
    }
    
    // Enhanced star rating animations
    function enhanceStarRatings() {
        const starRatings = document.querySelectorAll('.star-rating');
        
        starRatings.forEach(rating => {
            const stars = rating.querySelectorAll('.fa-star');
            
            stars.forEach((star, index) => {
                // Add a slight delay for each star to create a cascade effect
                star.style.animationDelay = `${index * 0.1}s`;
                
                // Add hover effect for interactive ratings
                if (!rating.classList.contains('readonly')) {
                    star.addEventListener('mouseenter', () => {
                        stars.forEach((s, i) => {
                            if (i <= index) {
                                s.style.transform = 'scale(1.2)';
                                s.style.color = '#ffc107';
                            } else {
                                s.style.transform = 'scale(1)';
                                s.style.color = '#dee2e6';
                            }
                        });
                    });
                    
                    rating.addEventListener('mouseleave', () => {
                        stars.forEach(s => {
                            s.style.transform = 'scale(1)';
                        });
                    });
                }
            });
        });
    }
    
    // Smooth scrolling for anchor links
    function initializeSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    // Form enhancements
    function enhanceForms() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                // Add floating label effect
                input.addEventListener('focus', () => {
                    input.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', () => {
                    if (!input.value) {
                        input.parentElement.classList.remove('focused');
                    }
                });
                
                // Check if input has value on load
                if (input.value) {
                    input.parentElement.classList.add('focused');
                }
            });
        });
    }
    
    // Initialize all enhancements
    enhanceStarRatings();
    initializeSmoothScrolling();
    enhanceForms();
    
    // Add intersection observer for animations on scroll
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        // Observe elements for scroll animations
        const animateElements = document.querySelectorAll('.feature-card, .user-type-card, .reviews-section');
        animateElements.forEach(el => observer.observe(el));
    }
    
    console.log('Restaurant Management Landing Page Scripts Loaded Successfully');
});

// Add CSS for scroll animations
const scrollAnimationCSS = `
    .feature-card, .user-type-card, .reviews-section {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .feature-card.animate-in, .user-type-card.animate-in, .reviews-section.animate-in {
        opacity: 1;
        transform: translateY(0);
    }
    
    @keyframes starShine {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .star-rating .fa-star.text-warning {
        animation: starShine 2s ease-in-out infinite;
    }
`;

// Inject scroll animation styles
const styleSheet = document.createElement('style');
styleSheet.textContent = scrollAnimationCSS;
document.head.appendChild(styleSheet);