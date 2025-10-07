// Enhanced Review System JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Enhanced Star Rating System
    class StarRating {
        constructor(container, options = {}) {
            this.container = container;
            this.options = {
                maxRating: 5,
                currentRating: 0,
                readOnly: false,
                size: 'medium',
                onRate: null,
                ...options
            };
            this.stars = [];
            this.init();
        }
        
        init() {
            this.container.innerHTML = '';
            this.container.className += ` star-rating-container star-rating-${this.options.size}`;
            
            for (let i = 1; i <= this.options.maxRating; i++) {
                const star = document.createElement('i');
                star.className = 'fas fa-star star-rating-star';
                star.dataset.rating = i;
                
                if (!this.options.readOnly) {
                    star.style.cursor = 'pointer';
                    star.addEventListener('click', () => this.setRating(i));
                    star.addEventListener('mouseenter', () => this.highlightStars(i));
                    star.addEventListener('mouseleave', () => this.highlightStars(this.options.currentRating));
                }
                
                this.container.appendChild(star);
                this.stars.push(star);
            }
            
            this.highlightStars(this.options.currentRating);
        }
        
        setRating(rating) {
            if (this.options.readOnly) return;
            
            this.options.currentRating = rating;
            this.highlightStars(rating);
            
            // Add click animation
            this.stars[rating - 1].classList.add('clicked');
            setTimeout(() => {
                this.stars[rating - 1].classList.remove('clicked');
            }, 300);
            
            if (this.options.onRate) {
                this.options.onRate(rating);
            }
        }
        
        highlightStars(rating) {
            this.stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                    star.classList.remove('inactive');
                } else {
                    star.classList.remove('active');
                    star.classList.add('inactive');
                }
            });
        }
        
        getRating() {
            return this.options.currentRating;
        }
        
        setReadOnly(readOnly) {
            this.options.readOnly = readOnly;
            this.stars.forEach(star => {
                star.style.cursor = readOnly ? 'default' : 'pointer';
            });
        }
    }
    
    // Initialize star ratings in review forms
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        let modalStarRating = null;
        
        reviewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            
            // Set form data
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalItemName').textContent = itemName;
            document.getElementById('comment').value = '';
            
            // Initialize star rating
            const starContainer = document.getElementById('starRatingInput');
            if (starContainer) {
                modalStarRating = new StarRating(starContainer, {
                    currentRating: 5,
                    onRate: (rating) => {
                        document.getElementById('modalRating').value = rating;
                        updateRatingText(rating);
                    }
                });
            }
            
            // Set initial rating
            document.getElementById('modalRating').value = 5;
            updateRatingText(5);
        });
        
        function updateRatingText(rating) {
            const ratingTexts = {
                1: "Poor",
                2: "Fair", 
                3: "Good",
                4: "Very Good",
                5: "Excellent"
            };
            
            let textElement = document.getElementById('ratingText');
            if (!textElement) {
                textElement = document.createElement('small');
                textElement.id = 'ratingText';
                textElement.className = 'text-muted ms-2';
                document.getElementById('starRatingInput').parentNode.appendChild(textElement);
            }
            
            textElement.textContent = `(${ratingTexts[rating] || ''})`;
        }
    }
    
    // Enhanced form validation
    const reviewForm = document.querySelector('#reviewModal form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const rating = document.getElementById('modalRating').value;
            if (!rating || rating < 1 || rating > 5) {
                e.preventDefault();
                showAlert('Please select a rating between 1 and 5 stars.', 'warning');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Restore button after a moment (form will submit)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    }
    
    // Smooth scrolling for navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(alert => {
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 150);
            }, 3000);
        }
    });
    
    // Utility function to show alerts
    function showAlert(message, type = 'info') {
        const alertContainer = document.querySelector('.container .row .col-12');
        if (alertContainer) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            alertContainer.appendChild(alert);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }, 5000);
        }
    }
    
    // Loading state for cards
    document.querySelectorAll('.btn[data-bs-toggle="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-star me-1"></i>Write Review';
            }, 500);
        });
    });
    
    // Enhanced hover effects for review cards
    document.querySelectorAll('.review-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Progress indicator for star ratings
    function createRatingProgress(container, ratings) {
        const total = ratings.reduce((sum, count) => sum + count, 0);
        
        ratings.forEach((count, index) => {
            const percentage = total > 0 ? (count / total) * 100 : 0;
            const stars = 5 - index;
            
            const progressBar = document.createElement('div');
            progressBar.className = 'rating-progress mb-2';
            progressBar.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="rating-label me-2">${stars} star${stars !== 1 ? 's' : ''}</span>
                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" 
                             style="width: ${percentage}%" aria-valuenow="${percentage}" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span class="rating-count text-muted">${count}</span>
                </div>
            `;
            
            container.appendChild(progressBar);
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    console.log('Review system JavaScript initialized successfully');
});

// CSS Injection for dynamic styles
const reviewStyles = `
    .star-rating-container {
        display: inline-flex;
        gap: 2px;
    }
    
    .star-rating-star {
        transition: all 0.2s ease;
        color: #dee2e6;
    }
    
    .star-rating-star.active {
        color: #ffc107;
    }
    
    .star-rating-star.inactive {
        color: #dee2e6;
    }
    
    .star-rating-medium .star-rating-star {
        font-size: 1.2rem;
    }
    
    .star-rating-large .star-rating-star {
        font-size: 1.5rem;
    }
    
    .star-rating-small .star-rating-star {
        font-size: 1rem;
    }
    
    .rating-progress .progress {
        height: 6px;
        background-color: #e9ecef;
    }
    
    .rating-label {
        min-width: 60px;
        font-size: 0.875rem;
    }
    
    .rating-count {
        min-width: 30px;
        text-align: right;
        font-size: 0.875rem;
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = reviewStyles;
document.head.appendChild(styleSheet);