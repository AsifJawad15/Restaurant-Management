<?php
require_once 'bootstrap.php';

// Initialize Auth
$auth = new Auth();

// Check if customer is logged in
if (!$auth->isCustomer()) {
    header('Location: login.php?redirect=reviews.php');
    exit;
}

$user = $auth->getCurrentUser();
$db = getDB();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $order_id = intval($_POST['order_id']);
    $menu_item_id = intval($_POST['menu_item_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Validate that this customer actually ordered this item
    $validation_stmt = $db->prepare("
        SELECT oi.id 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.customer_id = ? AND oi.order_id = ? AND oi.menu_item_id = ? 
        LIMIT 1
    ");
    $validation_stmt->execute([$user['id'], $order_id, $menu_item_id]);
    
    if ($validation_stmt->fetch()) {
        // Check if review already exists
        $existing_stmt = $db->prepare("
            SELECT id FROM reviews 
            WHERE customer_id = ? AND order_id = ? AND menu_item_id = ? 
            LIMIT 1
        ");
        $existing_stmt->execute([$user['id'], $order_id, $menu_item_id]);
        
        if (!$existing_stmt->fetch()) {
            // Insert new review
            $insert_stmt = $db->prepare("
                INSERT INTO reviews (customer_id, order_id, menu_item_id, rating, comment) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($insert_stmt->execute([$user['id'], $order_id, $menu_item_id, $rating, $comment])) {
                $success_message = "Review submitted successfully!";
            } else {
                $error_message = "Failed to submit review. Please try again.";
            }
        } else {
            $error_message = "You have already reviewed this item.";
        }
    } else {
        $error_message = "You can only review items you have ordered.";
    }
}

// Get customer's orders with items that can be reviewed
$orders_stmt = $db->prepare("
    SELECT DISTINCT
        o.id as order_id,
        o.created_at as order_date,
        o.status as order_status,
        oi.menu_item_id,
        mi.name as item_name,
        mi.description as item_description,
        mi.price as item_price,
        c.name as category_name,
        r.id as review_id,
        r.rating as current_rating,
        r.comment as current_comment,
        r.created_at as review_date
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    LEFT JOIN reviews r ON (r.customer_id = ? AND r.order_id = o.id AND r.menu_item_id = oi.menu_item_id)
    WHERE o.customer_id = ? AND o.status IN ('completed', 'delivered')
    ORDER BY o.created_at DESC, mi.name ASC
");
$orders_stmt->execute([$user['id'], $user['id']]);
$reviewable_items = $orders_stmt->fetchAll();

// Get customer's submitted reviews
$reviews_stmt = $db->prepare("
    SELECT 
        r.*,
        mi.name as item_name,
        mi.description as item_description,
        c.name as category_name,
        o.created_at as order_date
    FROM reviews r
    JOIN menu_items mi ON r.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    LEFT JOIN orders o ON r.order_id = o.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$user['id']]);
$my_reviews = $reviews_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/home-style.css" rel="stylesheet">
    <style>
        .review-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .star-rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .star-rating-input {
            display: flex;
            gap: 5px;
            font-size: 1.5rem;
        }
        .star-rating-input i {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating-input i.active,
        .star-rating-input i:hover,
        .star-rating-input i.hover {
            color: #ffc107;
        }
        .item-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .item-card:hover {
            transform: scale(1.02);
        }
        .review-status {
            font-size: 0.9rem;
        }
        .navbar-brand {
            font-weight: bold;
            color: #667eea !important;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="home.php">
            <i class="fas fa-utensils me-2"></i><?php echo APP_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="home.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reservations.php">Reservations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="reviews.php">Reviews</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($user['first_name'] ?? $user['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="my-reservations.php"><i class="fas fa-calendar me-2"></i>My Reservations</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-star text-warning me-3"></i>
                My Reviews & Ratings
            </h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4" id="reviewTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="write-reviews-tab" data-bs-toggle="pill" data-bs-target="#write-reviews" type="button" role="tab">
                <i class="fas fa-edit me-2"></i>Write Reviews
                <?php if (count(array_filter($reviewable_items, fn($item) => !$item['review_id'])) > 0): ?>
                    <span class="badge bg-warning text-dark ms-2"><?php echo count(array_filter($reviewable_items, fn($item) => !$item['review_id'])); ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="my-reviews-tab" data-bs-toggle="pill" data-bs-target="#my-reviews" type="button" role="tab">
                <i class="fas fa-history me-2"></i>My Reviews
                <?php if (count($my_reviews) > 0): ?>
                    <span class="badge bg-primary ms-2"><?php echo count($my_reviews); ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="reviewTabsContent">
        <!-- Write Reviews Tab -->
        <div class="tab-pane fade show active" id="write-reviews" role="tabpanel">
            <?php if (empty($reviewable_items)): ?>
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-shopping-bag text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Orders Yet</h4>
                        <p class="text-muted">You need to place and complete orders before you can write reviews.</p>
                        <a href="home.php" class="btn btn-primary">
                            <i class="fas fa-utensils me-2"></i>Browse Menu
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php 
                    $pending_items = array_filter($reviewable_items, fn($item) => !$item['review_id']);
                    $reviewed_items = array_filter($reviewable_items, fn($item) => $item['review_id']);
                    ?>
                    
                    <?php if (!empty($pending_items)): ?>
                        <div class="col-12 mb-4">
                            <h5 class="text-primary">
                                <i class="fas fa-clock me-2"></i>
                                Items Awaiting Your Review
                            </h5>
                        </div>
                        
                        <?php foreach ($pending_items as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card item-card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-utensils me-2"></i>
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </h6>
                                        <p class="card-text">
                                            <small class="opacity-75">
                                                <?php echo htmlspecialchars($item['category_name']); ?> • 
                                                Ordered on <?php echo formatDateFlexible($item['order_date'], 'M d, Y'); ?>
                                            </small>
                                        </p>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#reviewModal"
                                                data-order-id="<?php echo $item['order_id']; ?>"
                                                data-item-id="<?php echo $item['menu_item_id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                            <i class="fas fa-star me-1"></i>Write Review
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($reviewed_items)): ?>
                        <div class="col-12 mb-3 mt-4">
                            <h5 class="text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Already Reviewed
                            </h5>
                        </div>
                        
                        <?php foreach ($reviewed_items as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card review-card">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <div class="star-rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $item['current_rating'] ? '' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="card-text small text-muted">
                                            Reviewed on <?php echo formatDateFlexible($item['review_date'], 'M d, Y'); ?>
                                        </p>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Reviewed
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Reviews Tab -->
        <div class="tab-pane fade" id="my-reviews" role="tabpanel">
            <?php if (empty($my_reviews)): ?>
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-star text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Reviews Yet</h4>
                        <p class="text-muted">You haven't written any reviews yet. Order some delicious food and share your experience!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($my_reviews as $review): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card review-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($review['item_name']); ?></h6>
                                    <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($review['category_name']); ?></small>
                                    
                                    <div class="star-rating mb-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                    
                                    <?php if (!empty($review['comment'])): ?>
                                        <p class="card-text">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDateFlexible($review['created_at'], 'M d, Y'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-star text-warning me-2"></i>
                    Write Review
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    <input type="hidden" name="menu_item_id" id="modalItemId">
                    <input type="hidden" name="rating" id="modalRating" value="5">
                    
                    <h6 id="modalItemName" class="mb-3"></h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Rating</label>
                        <div class="star-rating-input" id="starRatingInput">
                            <i class="fas fa-star" data-rating="1"></i>
                            <i class="fas fa-star" data-rating="2"></i>
                            <i class="fas fa-star" data-rating="3"></i>
                            <i class="fas fa-star" data-rating="4"></i>
                            <i class="fas fa-star active" data-rating="5"></i>
                        </div>
                        <small class="text-muted">Click stars to rate</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Your Review (Optional)</label>
                        <textarea class="form-control" name="comment" id="comment" rows="4" 
                                  placeholder="Share your experience with this dish..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_review" class="btn btn-warning">
                        <i class="fas fa-star me-2"></i>Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="scripts/reviews.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Legacy compatibility for existing modal functionality
    const reviewModal = document.getElementById('reviewModal');
    const starRatingInput = document.getElementById('starRatingInput');
    const ratingInput = document.getElementById('modalRating');
    
    if (reviewModal && starRatingInput && ratingInput) {
        reviewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalItemName').textContent = itemName;
            document.getElementById('comment').value = '';
            
            // Reset stars to 5 (legacy)
            updateStarRating(5);
        });
        
        // Handle star rating clicks (legacy)
        starRatingInput.addEventListener('click', function(e) {
            if (e.target.classList.contains('fa-star')) {
                const rating = parseInt(e.target.getAttribute('data-rating'));
                updateStarRating(rating);
            }
        });
        
        // Handle star rating hover (legacy)
        starRatingInput.addEventListener('mouseover', function(e) {
            if (e.target.classList.contains('fa-star')) {
                const rating = parseInt(e.target.getAttribute('data-rating'));
                highlightStars(rating);
            }
        });
        
        starRatingInput.addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            highlightStars(currentRating);
        });
        
        function updateStarRating(rating) {
            ratingInput.value = rating;
            highlightStars(rating);
        }
        
        function highlightStars(rating) {
            const stars = starRatingInput.querySelectorAll('.fa-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
    }
});
</script>

</body>
</html>