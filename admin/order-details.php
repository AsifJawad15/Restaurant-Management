<?php
/**
 * Order Details Page
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    header('Location: dashboard.php');
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get order details with customer information
    $stmt = $conn->prepare("
        SELECT o.*, 
               u.first_name, u.last_name, u.email, u.phone,
               t.table_number,
               CONCAT(u.first_name, ' ', u.last_name) as customer_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $_SESSION['error'] = "Order not found!";
        header('Location: dashboard.php');
        exit;
    }
    
    // Get order items with menu details
    $stmt = $conn->prepare("
        SELECT oi.*, mi.name as item_name, mi.description, mi.image_url,
               c.name as category_name
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();
    
    // Initialize default values if not set
    if (!isset($order['status'])) $order['status'] = 'pending';
    if (!isset($order['payment_status'])) $order['payment_status'] = 'pending';
    if (!isset($order['created_at'])) $order['created_at'] = date('Y-m-d H:i:s');
    
    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $newStatus = $_POST['order_status'];
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$newStatus, $orderId])) {
            $success = "Order status updated successfully!";
            // Refresh order data
            $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = array_merge($order, $stmt->fetch());
        }
    }
    
    // Handle payment status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
        $paymentStatus = $_POST['payment_status'];
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$paymentStatus, $orderId])) {
            $success = "Payment status updated successfully!";
            $order['payment_status'] = $paymentStatus;
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Status badge colors
function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'preparing' => 'primary',
        'ready' => 'success',
        'served' => 'success',
        'completed' => 'dark',
        'cancelled' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

// Payment status badge colors
function getPaymentBadge($status) {
    $badges = [
        'pending' => 'warning',
        'paid' => 'success',
        'failed' => 'danger',
        'refunded' => 'info'
    ];
    return $badges[$status] ?? 'secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $orderId; ?> - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .order-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        .info-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            background: white;
            margin-bottom: 1.5rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-weight: 500;
        }
        .info-value {
            color: #333;
            font-weight: 600;
        }
        .item-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: white;
        }
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        .item-category {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .item-price {
            color: #c5a572;
            font-weight: 600;
        }
        .total-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        .total-row.grand-total {
            border-top: 2px solid #c5a572;
            margin-top: 0.5rem;
            padding-top: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #c5a572;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn-update {
            background: #c5a572;
            color: white;
            border: none;
        }
        .btn-update:hover {
            background: #b08d5f;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <i class="fas fa-utensils sidebar-logo"></i>
                <h4 class="sidebar-title">Delicious Restaurant</h4>
                <small class="text-muted">Admin Panel</small>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <div class="nav-section">Menu Management</div>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="menu-items.php" class="nav-link">
                        <i class="fas fa-utensils"></i>
                        <span>Menu Items</span>
                    </a>
                </li>
                
                <div class="nav-section">Orders & Sales</div>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link active">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Top Header -->
            <header class="admin-header">
                <div class="header-content">
                    <div>
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        <h1 class="page-title d-inline">Order Details #<?php echo $orderId; ?></h1>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($order) && $order): ?>
                <!-- Order Header -->
                <div class="order-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Order #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></h2>
                            <p class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-<?php echo getStatusBadge($order['status']); ?> order-badge me-2">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <span class="badge bg-<?php echo getPaymentBadge($order['payment_status']); ?> order-badge">
                                Payment: <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Order Items -->
                        <div class="info-card">
                            <h5 class="mb-3">
                                <i class="fas fa-utensils text-warning me-2"></i>
                                Order Items
                            </h5>
                            
                            <?php foreach ($orderItems as $item): ?>
                                <div class="item-card">
                                    <?php if ($item['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                             class="item-image">
                                    <?php else: ?>
                                        <div class="item-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-utensils text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                        <div class="item-category">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                        </div>
                                        <?php if ($item['special_instructions']): ?>
                                            <div class="text-muted small">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?php echo htmlspecialchars($item['special_instructions']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-end">
                                        <div class="item-price"><?php echo formatPrice($item['unit_price']); ?></div>
                                        <div class="text-muted small">Qty: <?php echo $item['quantity']; ?></div>
                                        <div class="fw-bold mt-1"><?php echo formatPrice($item['total_price']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Total Section -->
                            <div class="total-section">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($order['total_amount'] ?? 0); ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Tax:</span>
                                    <span><?php echo formatPrice($order['tax_amount'] ?? 0); ?></span>
                                </div>
                                <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                    <div class="total-row text-success">
                                        <span>Discount:</span>
                                        <span>-<?php echo formatPrice($order['discount_amount']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="total-row grand-total">
                                    <span>Grand Total:</span>
                                    <span><?php echo formatPrice($order['final_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Special Instructions -->
                        <?php if ($order['special_instructions']): ?>
                            <div class="info-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-comment text-info me-2"></i>
                                    Special Instructions
                                </h5>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-4">
                        <!-- Customer Information -->
                        <div class="info-card">
                            <h5 class="mb-3">
                                <i class="fas fa-user text-primary me-2"></i>
                                Customer Information
                            </h5>
                            <div class="info-row">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <!-- Order Information -->
                        <div class="info-card">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle text-success me-2"></i>
                                Order Information
                            </h5>
                            <div class="info-row">
                                <span class="info-label">Order Type:</span>
                                <span class="info-value"><?php echo ucfirst($order['order_type']); ?></span>
                            </div>
                            <?php if ($order['table_id']): ?>
                                <div class="info-row">
                                    <span class="info-label">Table:</span>
                                    <span class="info-value">
                                        Table #<?php echo $order['table_number']; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">Payment Method:</span>
                                <span class="info-value"><?php echo ucfirst($order['payment_method']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Created:</span>
                                <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Last Updated:</span>
                                <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></span>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <div class="info-card">
                            <h5 class="mb-3">
                                <i class="fas fa-edit text-warning me-2"></i>
                                Update Order Status
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Order Status</label>
                                    <select name="order_status" class="form-select" required>
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                        <option value="ready" <?php echo $order['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                        <option value="served" <?php echo $order['status'] === 'served' ? 'selected' : ''; ?>>Served</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-update w-100">
                                    <i class="fas fa-save me-2"></i>Update Status
                                </button>
                            </form>
                        </div>

                        <!-- Update Payment Status -->
                        <div class="info-card">
                            <h5 class="mb-3">
                                <i class="fas fa-dollar-sign text-success me-2"></i>
                                Update Payment Status
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-select" required>
                                        <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_payment" class="btn btn-update w-100">
                                    <i class="fas fa-save me-2"></i>Update Payment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Order data could not be loaded. Please try again.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
