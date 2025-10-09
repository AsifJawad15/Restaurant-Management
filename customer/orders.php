<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('../index.php');
}

$db = getDB();
$user = $auth->getCurrentUser();

// Get customer orders with items
$stmt = $db->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(oi.menu_item_id) as menu_items
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.customer_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

// Get order items for each order
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
    $stmt = $db->prepare("
        SELECT oi.*, mi.name as item_name 
        FROM order_items oi 
        JOIN menu_items mi ON oi.menu_item_id = mi.id 
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id, oi.id
    ");
    $stmt->execute($orderIds);
    $items = $stmt->fetchAll();
    
    // Group items by order_id
    foreach ($items as $item) {
        $orderItems[$item['order_id']][] = $item;
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'confirmed': return 'bg-info';
        case 'preparing': return 'bg-primary';
        case 'ready': return 'bg-success';
        case 'delivered': return 'bg-success';
        case 'completed': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'paid': return 'bg-success';
        case 'refunded': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/orders-style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="home.php"><i class="fas fa-utensils me-2"></i>Delicious</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i>Home</a>
                <a class="nav-link active" href="orders.php"><i class="fas fa-receipt me-1"></i>Orders</a>
                <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a>
                <a class="nav-link" href="../admin/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-receipt me-2"></i>My Orders</h2>
            <a href="home.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Order
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No orders yet</h4>
                <p class="text-muted">Start ordering from our delicious menu!</p>
                <a href="home.php" class="btn btn-primary">
                    <i class="fas fa-utensils me-2"></i>Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($orders as $order): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card order-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Order #<?php echo $order['id']; ?></h6>
                                <small class="text-muted"><?php echo formatDateFlexible($order['created_at'], 'M j, Y g:i A'); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Type</small>
                                    <div><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Items</small>
                                    <div><?php echo (int)$order['item_count']; ?> item(s)</div>
                                </div>
                            </div>
                            
                            <?php if (isset($orderItems[$order['id']])): ?>
                            <div class="order-items mb-3">
                                <small class="text-muted">Items:</small>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($orderItems[$order['id']] as $item): ?>
                                    <li class="d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($item['item_name']); ?> x<?php echo $item['quantity']; ?></span>
                                        <span>$<?php echo number_format($item['total_price'], 2); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Total</small>
                                    <div class="fw-bold">$<?php echo number_format($order['final_amount'], 2); ?></div>
                                </div>
                                <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($order['special_instructions'])): ?>
                            <div class="mt-2">
                                <small class="text-muted">Special Instructions:</small>
                                <div class="small"><?php echo htmlspecialchars($order['special_instructions']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // TODO: Implement order cancellation
                alert('Order cancellation feature coming soon.');
            }
        }
    </script>
</body>
</html>
