<?php
require_once '../includes/config.php';

// Check admin authentication
requireAdminLogin();

$db = getDBConnection();

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['review_id'])) {
        $review_id = intval($_POST['review_id']);
        $action = $_POST['action'];
        
        if ($action === 'verify') {
            $stmt = $db->prepare("UPDATE reviews SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$review_id]);
            $success_message = "Review verified successfully!";
        } elseif ($action === 'unverify') {
            $stmt = $db->prepare("UPDATE reviews SET is_verified = 0 WHERE id = ?");
            $stmt->execute([$review_id]);
            $success_message = "Review unverified successfully!";
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            $success_message = "Review deleted successfully!";
        }
    }
}

// Get filter parameters
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : '';
$verification_filter = isset($_GET['verification']) ? $_GET['verification'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($rating_filter) {
    $where_conditions[] = "r.rating = ?";
    $params[] = $rating_filter;
}

if ($verification_filter === 'verified') {
    $where_conditions[] = "r.is_verified = 1";
} elseif ($verification_filter === 'unverified') {
    $where_conditions[] = "r.is_verified = 0";
}

if ($date_filter) {
    $where_conditions[] = "DATE(r.created_at) = ?";
    $params[] = $date_filter;
}

if ($search_term) {
    $where_conditions[] = "(mi.name LIKE ? OR r.comment LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = '%' . $search_term . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get reviews with customer and menu item details
$reviews_stmt = $db->prepare("
    SELECT 
        r.*,
        u.first_name,
        u.last_name,
        u.email,
        mi.name as item_name,
        mi.description as item_description,
        c.name as category_name,
        o.created_at as order_date
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    JOIN menu_items mi ON r.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    LEFT JOIN orders o ON r.order_id = o.id
    {$where_clause}
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute($params);
$reviews = $reviews_stmt->fetchAll();

// Get statistics
$stats_stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as average_rating,
        SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_reviews,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_reviews,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star_reviews
    FROM reviews
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Get rating distribution
$rating_dist_stmt = $db->prepare("
    SELECT rating, COUNT(*) as count 
    FROM reviews 
    GROUP BY rating 
    ORDER BY rating DESC
");
$rating_dist_stmt->execute();
$rating_distribution = $rating_dist_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
        }
        .verified-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        .unverified-badge {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-utensils me-2"></i>Restaurant Admin
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
            </a>
            <a class="nav-link" href="menu-items.php">
                <i class="fas fa-utensils me-1"></i>Menu
            </a>
            <a class="nav-link" href="reservations.php">
                <i class="fas fa-calendar me-1"></i>Reservations
            </a>
            <a class="nav-link active" href="reviews.php">
                <i class="fas fa-star me-1"></i>Reviews
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-star text-warning me-3"></i>
                Reviews Management
            </h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['total_reviews'] ?? 0); ?></h3>
                    <p class="mb-0">Total Reviews</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['average_rating'] ?? 0, 1); ?></h3>
                    <p class="mb-0">Average Rating</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['verified_reviews'] ?? 0); ?></h3>
                    <p class="mb-0">Verified Reviews</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fas fa-thumbs-up fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['five_star_reviews'] ?? 0); ?></h3>
                    <p class="mb-0">5-Star Reviews</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Rating Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($rating_distribution as $rating): ?>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="star-rating mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $rating['rating'] ? '' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <h4><?php echo $rating['count']; ?></h4>
                                    <small class="text-muted"><?php echo $rating['rating']; ?> Star<?php echo $rating['rating'] > 1 ? 's' : ''; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Rating</label>
                            <select name="rating" class="form-select">
                                <option value="">All Ratings</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $rating_filter == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Verification</label>
                            <select name="verification" class="form-select">
                                <option value="">All Reviews</option>
                                <option value="verified" <?php echo $verification_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                <option value="unverified" <?php echo $verification_filter === 'unverified' ? 'selected' : ''; ?>>Unverified</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by item name, comment, or customer..." 
                                   value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="row">
        <?php if (empty($reviews)): ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-star text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Reviews Found</h4>
                        <p class="text-muted">No reviews match your current filters.</p>
                        <a href="reviews.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card review-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="card-title mb-0"><?php echo htmlspecialchars($review['item_name']); ?></h6>
                                <?php if ($review['is_verified']): ?>
                                    <span class="badge verified-badge">
                                        <i class="fas fa-check me-1"></i>Verified
                                    </span>
                                <?php else: ?>
                                    <span class="badge unverified-badge">
                                        <i class="fas fa-clock me-1"></i>Unverified
                                    </span>
                                <?php endif; ?>
                            </div>
                            
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
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo formatDateTime($review['created_at']); ?>
                                </small>
                            </div>
                            
                            <div class="btn-group w-100" role="group">
                                <?php if (!$review['is_verified']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="btn btn-success btn-sm" title="Verify Review">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="unverify">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Unverify Review">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-info btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#reviewDetailsModal"
                                        data-review-id="<?php echo $review['id']; ?>"
                                        data-customer="<?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($review['email']); ?>"
                                        data-item="<?php echo htmlspecialchars($review['item_name']); ?>"
                                        data-category="<?php echo htmlspecialchars($review['category_name']); ?>"
                                        data-rating="<?php echo $review['rating']; ?>"
                                        data-comment="<?php echo htmlspecialchars($review['comment']); ?>"
                                        data-date="<?php echo formatDateTime($review['created_at']); ?>"
                                        data-verified="<?php echo $review['is_verified'] ? 'true' : 'false'; ?>"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <form method="POST" class="d-inline" 
                                      onsubmit="return confirm('Are you sure you want to delete this review?')">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Review">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Review Details Modal -->
<div class="modal fade" id="reviewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-star text-warning me-2"></i>
                    Review Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <span id="modalCustomerName"></span></p>
                        <p><strong>Email:</strong> <span id="modalCustomerEmail"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Menu Item</h6>
                        <p><strong>Item:</strong> <span id="modalItemName"></span></p>
                        <p><strong>Category:</strong> <span id="modalItemCategory"></span></p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Rating</h6>
                        <div id="modalStarRating" class="star-rating mb-3"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Status</h6>
                        <span id="modalVerificationStatus"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Review Comment</h6>
                        <div id="modalComment" class="p-3 bg-light rounded"></div>
                    </div>
                </div>
                <hr>
                <p class="text-muted mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Submitted on <span id="modalDate"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reviewDetailsModal = document.getElementById('reviewDetailsModal');
    
    reviewDetailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        document.getElementById('modalCustomerName').textContent = button.getAttribute('data-customer');
        document.getElementById('modalCustomerEmail').textContent = button.getAttribute('data-email');
        document.getElementById('modalItemName').textContent = button.getAttribute('data-item');
        document.getElementById('modalItemCategory').textContent = button.getAttribute('data-category');
        document.getElementById('modalDate').textContent = button.getAttribute('data-date');
        
        const rating = parseInt(button.getAttribute('data-rating'));
        const starRatingElement = document.getElementById('modalStarRating');
        starRatingElement.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('i');
            star.className = `fas fa-star ${i <= rating ? '' : 'text-muted'}`;
            starRatingElement.appendChild(star);
        }
        starRatingElement.innerHTML += ` <span class="ms-2">${rating}/5</span>`;
        
        const comment = button.getAttribute('data-comment');
        document.getElementById('modalComment').textContent = comment || 'No comment provided.';
        
        const verified = button.getAttribute('data-verified') === 'true';
        const statusElement = document.getElementById('modalVerificationStatus');
        if (verified) {
            statusElement.innerHTML = '<span class="badge verified-badge"><i class="fas fa-check me-1"></i>Verified</span>';
        } else {
            statusElement.innerHTML = '<span class="badge unverified-badge"><i class="fas fa-clock me-1"></i>Unverified</span>';
        }
    });
});
</script>

</body>
</html>