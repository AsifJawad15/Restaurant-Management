<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('../index.php');
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    redirect('orders.php');
}

$db = getDB();
$user = $auth->getCurrentUser();

// Get order details
$stmt = $db->prepare("
    SELECT o.*, t.table_number 
    FROM orders o 
    LEFT JOIN tables t ON o.table_id = t.id 
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php');
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, mi.name as item_name 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    WHERE oi.order_id = ? 
    ORDER BY oi.id
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/checkout-style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-success mb-3">Order Confirmed!</h2>
                        <p class="lead text-muted mb-4">Thank you for your order. We'll start preparing it right away.</p>
                        
                        <div class="alert alert-info">
                            <h5 class="mb-2">Order #<?php echo $order['id']; ?></h5>
                            <p class="mb-0">
                                <strong>Status:</strong> 
                                <span class="badge <?php echo getStatusBadgeClass($order['status']); ?> ms-2">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Order Information</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Order ID:</strong> #<?php echo $order['id']; ?></li>
                                    <li><strong>Order Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></li>
                                    <?php if ($order['table_number']): ?>
                                    <li><strong>Table:</strong> <?php echo htmlspecialchars($order['table_number']); ?></li>
                                    <?php endif; ?>
                                    <li><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></li>
                                    <li><strong>Order Date:</strong> <?php echo formatDateFlexible($order['created_at'], 'M j, Y g:i A'); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Payment Summary</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Subtotal:</strong> $<?php echo number_format($order['total_amount'], 2); ?></li>
                                    <li><strong>Tax:</strong> $<?php echo number_format($order['tax_amount'], 2); ?></li>
                                    <li><strong>Total:</strong> $<?php echo number_format($order['final_amount'], 2); ?></li>
                                </ul>
                            </div>
                        </div>

                        <?php if (!empty($order['special_instructions'])): ?>
                        <div class="mb-4">
                            <h6>Special Instructions</h6>
                            <p class="text-muted"><?php echo htmlspecialchars($order['special_instructions']); ?></p>
                        </div>
                        <?php endif; ?>

                        <h6>Items Ordered</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="orders.php" class="btn btn-primary me-2">
                        <i class="fas fa-list me-2"></i>View All Orders
                    </a>
                    <a href="home.php" class="btn btn-outline-primary">
                        <i class="fas fa-utensils me-2"></i>Order More
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
