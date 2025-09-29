
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
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts/script.js"></script>
</body>
</html>
