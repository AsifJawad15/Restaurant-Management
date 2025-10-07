
<?php
require_once 'includes/config.php';

// Fetch recent reviews for the landing page carousel
$reviews = [];
try {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT 
            r.*,
            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
            mi.name as item_name,
            c.name as category_name
        FROM reviews r
        JOIN users u ON r.customer_id = u.id
        JOIN menu_items mi ON r.menu_item_id = mi.id
        JOIN categories c ON mi.category_id = c.id
        WHERE r.is_verified = 1 AND r.comment IS NOT NULL AND r.comment != ''
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {
    // Silently handle errors for landing page
    $reviews = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left Side - Features -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-5">
                <div class="text-center text-white">
                    <h1 class="restaurant-logo mb-4">
                        <i class="fas fa-utensils me-3"></i>
                        Delicious Restaurant
                    </h1>
                    <p class="lead mb-5">Experience fine dining with our comprehensive management system</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h5>Easy Reservations</h5>
                                <p>Book your table online with just a few clicks</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <h5>Mobile Ordering</h5>
                                <p>Order food directly from your table</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h5>Reviews & Ratings</h5>
                                <p>Share your dining experience</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-gift"></i>
                                </div>
                                <h5>Loyalty Program</h5>
                                <p>Earn points with every visit</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Login/Register Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-5">
                <div class="main-container p-5 w-100" style="max-width: 600px;">
                    <div class="text-center mb-4">
                        <h3 class="text-dark">Welcome!</h3>
                        <p class="text-muted">Choose how you'd like to access the system</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- User Type Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <a class="user-type-card d-block text-decoration-none" href="admin/login.php">
                                <div class="mb-3 text-center">
                                    <i class="fas fa-user-shield text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="text-dark text-center">Admin Login</h5>
                                <p class="text-muted text-center">Access admin dashboard for restaurant management</p>
                                <div class="d-grid">
                                    <span class="btn btn-outline-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Go to Admin Login
                                    </span>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a class="user-type-card d-block text-decoration-none" href="customer/login.php">
                                <div class="mb-3 text-center">
                                    <i class="fas fa-user text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="text-dark text-center">Customer Login</h5>
                                <p class="text-muted text-center">Login to view your personalized homepage</p>
                                <div class="d-grid">
                                    <span class="btn btn-outline-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Go to Customer Login
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="text-center">
                                <h5 class="text-dark mb-3">Quick Actions</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="customer/login.php?redirect=reservations.php" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-calendar-alt me-2"></i>Make a Reservation
                                        </a>
                                        <small class="text-muted d-block mt-1">Book your table online</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="customer/login.php?redirect=home.php" class="btn btn-outline-primary btn-lg w-100">
                                            <i class="fas fa-utensils me-2"></i>View Menu
                                        </a>
                                        <small class="text-muted d-block mt-1">Browse our delicious menu</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Reviews Carousel Section -->
    <?php if (!empty($reviews)): ?>
    <section class="reviews-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3">
                    <i class="fas fa-star text-warning me-3"></i>
                    What Our Customers Say
                </h2>
                <p class="lead">Real reviews from our valued customers</p>
            </div>
            
            <div class="reviews-carousel-container">
                <!-- Single Fixed Review Display -->
                <div class="single-review-display" id="reviewDisplay">
                    <div class="review-card">
                        <div class="card h-100 border-0">
                            <div class="card-body text-center p-5">
                                <!-- Star Rating -->
                                <div class="star-rating mb-4" id="reviewStars">
                                    <!-- Stars will be populated by JavaScript -->
                                </div>
                                
                                <!-- Review Comment -->
                                <blockquote class="blockquote">
                                    <p class="mb-0" id="reviewComment">Loading review...</p>
                                </blockquote>
                                
                                <!-- Customer Info -->
                                <div class="customer-info">
                                    <h6 class="mb-2" id="reviewCustomer">Customer Name</h6>
                                    <small class="text-muted d-block" id="reviewItem">
                                        <i class="fas fa-utensils me-2"></i>
                                        Menu Item
                                    </small>
                                    <small class="text-muted d-block" id="reviewDate">
                                        <i class="fas fa-calendar me-2"></i>
                                        Date
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden Reviews Data for JavaScript -->
                <script type="application/json" id="reviewsData">
                    <?php echo json_encode($reviews); ?>
                </script>
                
                <!-- Carousel Navigation -->
                <?php if (count($reviews) > 1): ?>
                <div class="carousel-navigation text-center">
                    <button class="btn btn-outline-primary me-3" id="prevReview" title="Previous Review">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <!-- Dots indicator -->
                    <div class="carousel-dots d-inline-block mx-3">
                        <?php for ($i = 0; $i < count($reviews); $i++): ?>
                            <button class="dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>" title="Review <?php echo $i + 1; ?>"></button>
                        <?php endfor; ?>
                    </div>
                    
                    <button class="btn btn-outline-primary ms-3" id="nextReview" title="Next Review">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Call to Action -->
                <div class="text-center">
                    <a href="customer/login.php?redirect=reviews.php" class="btn btn-warning">
                        <i class="fas fa-star me-2"></i>
                        Share Your Experience
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts/script.js"></script>
</body>
</html>
