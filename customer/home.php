<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('../index.php');
}

$db = getDB();
$user = $auth->getCurrentUser();

// Fetch categories and menu items
$categories = $db->query("SELECT id, name FROM categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll();

// Get menu items grouped by category with reviews
$stmt = $db->query("
    SELECT 
        m.id, 
        m.category_id, 
        m.name, 
        m.description, 
        m.price, 
        m.image_url, 
        c.name AS category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count
    FROM menu_items m 
    JOIN categories c ON c.id = m.category_id 
    LEFT JOIN reviews r ON r.menu_item_id = m.id
    WHERE m.is_available=1 
    GROUP BY m.id, m.category_id, m.name, m.description, m.price, m.image_url, c.name, c.sort_order
    ORDER BY c.sort_order, m.name
");
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/home-style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-utensils me-2"></i>Delicious</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($categories as $cat): ?>
                    <li class="nav-item"><a class="nav-link" href="#cat-<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item me-3 d-flex align-items-center">
                        <i class="fas fa-user text-muted me-2"></i>
                        <span class="text-muted">Hi, <?php echo htmlspecialchars($user['first_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reservations.php"><i class="fas fa-calendar-alt me-1"></i>Reservations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reviews.php"><i class="fas fa-star me-1"></i>Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php"><i class="fas fa-receipt me-1"></i>Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-secondary" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            <div class="col-lg-8">
                <?php 
                $currentCat = null; 
                foreach ($items as $i): 
                    if ($currentCat !== $i['category_id']) {
                        if ($currentCat !== null) echo '</div></div>'; // close previous grid
                        $currentCat = $i['category_id'];
                        echo '<div class="category-block mb-4" id="cat-'.(int)$currentCat.'">';
                        echo '<h4 class="mb-3">'.htmlspecialchars($i['category_name']).'</h4>';
                        echo '<div class="row g-3">';
                    }
                ?>
                    <div class="col-md-6">
                        <div class="card menu-card h-100">
                            <?php if (!empty($i['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($i['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($i['name']); ?>">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title d-flex justify-content-between align-items-start">
                                    <span><?php echo htmlspecialchars($i['name']); ?></span>
                                    <span class="price">$<?php echo number_format((float)$i['price'], 2); ?></span>
                                </h5>
                                
                                <!-- Rating Display -->
                                <?php if ($i['review_count'] > 0): ?>
                                    <div class="mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="star-rating text-warning me-2">
                                                <?php 
                                                $rating = round($i['avg_rating'], 1);
                                                for ($star = 1; $star <= 5; $star++): 
                                                    if ($star <= $rating): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php elseif ($star - 0.5 <= $rating): ?>
                                                        <i class="fas fa-star-half-alt"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif;
                                                endfor; ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo number_format($rating, 1); ?> (<?php echo $i['review_count']; ?> review<?php echo $i['review_count'] > 1 ? 's' : ''; ?>)
                                            </small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="far fa-star me-1"></i>No reviews yet
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars($i['description']); ?></p>
                                <div class="d-flex">
                                    <div class="input-group me-2" style="width: 120px;">
                                        <button class="btn btn-outline-secondary btn-qty" type="button" data-op="-">-</button>
                                        <input type="number" class="form-control text-center qty-input" min="1" value="1">
                                        <button class="btn btn-outline-secondary btn-qty" type="button" data-op="+">+</button>
                                    </div>
                                    <button class="btn btn-primary btn-add" data-id="<?php echo (int)$i['id']; ?>" data-name="<?php echo htmlspecialchars($i['name']); ?>" data-price="<?php echo htmlspecialchars($i['price']); ?>">
                                        <i class="fas fa-cart-plus me-1"></i>Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; if ($currentCat !== null) echo '</div></div>'; ?>
            </div>
            <div class="col-lg-4">
                <div class="card cart-card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong>Your Cart</strong>
                        <button class="btn btn-sm btn-outline-danger" id="btnClearCart"><i class="fas fa-trash me-1"></i>Clear</button>
                    </div>
                    <div class="card-body p-0">
                        <div id="cartItems" class="list-group list-group-flush small">
                            <!-- items go here -->
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between"><span>Subtotal</span><span id="subtotal">$0.00</span></div>
                        <div class="d-flex justify-content-between"><span>Tax</span><span id="tax">$0.00</span></div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span id="total">$0.00</span></div>
                        <button class="btn btn-success w-100 mt-3" id="btnCheckout"><i class="fas fa-credit-card me-1"></i>Checkout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.CART_ENDPOINT = 'cart.php';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts/home-script.js"></script>
</body>
</html>

