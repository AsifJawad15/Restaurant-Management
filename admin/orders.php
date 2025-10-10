<?php
/**
 * Orders Management
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$conn = getDBConnection();
$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_status') {
            $order_id = $_POST['order_id'];
            $new_status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $order_id]);
            
            $success = "Order status updated successfully!";
        } elseif ($_POST['action'] === 'update_payment') {
            $order_id = $_POST['order_id'];
            $payment_status = $_POST['payment_status'];
            
            $stmt = $conn->prepare("UPDATE orders SET payment_status = :payment_status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':payment_status' => $payment_status, ':id' => $order_id]);
            
            $success = "Payment status updated successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error updating order: " . $e->getMessage();
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'recent';

// Build query
$query = "
    SELECT o.*, 
           CONCAT(u.first_name, ' ', u.last_name) as customer_name,
           u.email as customer_email,
           u.phone as customer_phone,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($search) {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE :search 
                 OR u.email LIKE :search 
                 OR u.phone LIKE :search 
                 OR o.id LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if ($payment_filter) {
    $query .= " AND o.payment_status = :payment_status";
    $params[':payment_status'] = $payment_filter;
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " GROUP BY o.id";

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY o.created_at ASC";
        break;
    case 'amount_high':
        $query .= " ORDER BY o.final_amount DESC";
        break;
    case 'amount_low':
        $query .= " ORDER BY o.final_amount ASC";
        break;
    default:
        $query .= " ORDER BY o.created_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
        SUM(CASE WHEN DATE(created_at) = CURDATE() AND payment_status = 'paid' THEN final_amount ELSE 0 END) as today_revenue
    FROM orders
";
$stats_stmt = $conn->query($stats_query);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .order-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: #fff; }
        .status-preparing { background: #fd7e14; color: #fff; }
        .status-ready { background: #28a745; color: #fff; }
        .status-delivered { background: #6c757d; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        
        .payment-paid { background: #28a745; color: #fff; }
        .payment-pending { background: #ffc107; color: #000; }
        .payment-failed { background: #dc3545; color: #fff; }
        
        .quick-action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .order-id {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #0d6efd;
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
                <li class="nav-item">
                    <a href="reservations.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Reservations</span>
                    </a>
                </li>
                
                <div class="nav-section">Customer Management</div>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="loyalty-points.php" class="nav-link">
                        <i class="fas fa-gift"></i>
                        <span>Loyalty Points</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reviews.php" class="nav-link">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </li>
                
                <div class="nav-section">Restaurant Management</div>
                <li class="nav-item">
                    <a href="tables.php" class="nav-link">
                        <i class="fas fa-chair"></i>
                        <span>Tables</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="staff.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        <span>Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link">
                        <i class="fas fa-boxes"></i>
                        <span>Inventory</span>
                    </a>
                </li>
                
                <div class="nav-section">Reports & Analytics</div>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                
                <div class="nav-section">Settings</div>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Top Header -->
            <header class="admin-header">
                <div class="header-content">
                    <h1 class="page-title">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Orders Management
                    </h1>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $stats['total_orders']; ?></h3>
                            <p class="stats-label">Total Orders</p>
                            <p class="stats-change">
                                <i class="fas fa-calendar-day"></i> <?php echo $stats['today_orders']; ?> today
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $stats['pending_orders']; ?></h3>
                            <p class="stats-label">Pending Orders</p>
                            <p class="stats-change">
                                <i class="fas fa-hourglass-half"></i> Need attention
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $stats['delivered_orders']; ?></h3>
                            <p class="stats-label">Delivered Orders</p>
                            <p class="stats-change positive">
                                <i class="fas fa-truck"></i> Completed
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon info">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h3 class="stats-number"><?php echo formatPrice($stats['total_revenue']); ?></h3>
                            <p class="stats-label">Total Revenue</p>
                            <p class="stats-change">
                                <i class="fas fa-calendar-day"></i> <?php echo formatPrice($stats['today_revenue']); ?> today
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Order Status Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3">
                                <h5 class="mb-3">
                                    <i class="fas fa-tasks me-2"></i>
                                    Order Status Summary
                                </h5>
                                <div class="row text-center">
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <span class="order-badge status-confirmed d-block mb-2">Confirmed</span>
                                            <h4 class="mb-0"><?php echo $stats['confirmed_orders']; ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <span class="order-badge status-preparing d-block mb-2">Preparing</span>
                                            <h4 class="mb-0"><?php echo $stats['preparing_orders']; ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <span class="order-badge status-ready d-block mb-2">Ready</span>
                                            <h4 class="mb-0"><?php echo $stats['ready_orders']; ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <span class="order-badge status-delivered d-block mb-2">Delivered</span>
                                            <h4 class="mb-0"><?php echo $stats['delivered_orders']; ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <span class="order-badge status-cancelled d-block mb-2">Cancelled</span>
                                            <h4 class="mb-0"><?php echo $stats['cancelled_orders']; ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <span class="order-badge status-pending d-block mb-2">Pending</span>
                                            <h4 class="mb-0"><?php echo $stats['pending_orders']; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="data-table mb-4">
                    <div class="p-3">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Order ID, customer name..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Payment</label>
                                <select name="payment_status" class="form-select">
                                    <option value="">All Payments</option>
                                    <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="data-table">
                    <div class="table-header p-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            All Orders (<?php echo count($orders); ?>)
                        </h5>
                        <div>
                            <form method="GET" class="d-inline">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                <input type="hidden" name="payment_status" value="<?php echo htmlspecialchars($payment_filter); ?>">
                                <select name="sort_by" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                    <option value="recent" <?php echo $sort_by === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                                    <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="amount_high" <?php echo $sort_by === 'amount_high' ? 'selected' : ''; ?>>Amount (High-Low)</option>
                                    <option value="amount_low" <?php echo $sort_by === 'amount_low' ? 'selected' : ''; ?>>Amount (Low-High)</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <span class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $order['item_count']; ?> items</span>
                                            </td>
                                            <td>
                                                <strong><?php echo formatPrice($order['final_amount']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="order-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="order-badge payment-<?php echo $order['payment_status']; ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                                <br>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')"
                                                            title="Update Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="openPaymentModal(<?php echo $order['id']; ?>, '<?php echo $order['payment_status']; ?>')"
                                                            title="Update Payment">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No orders found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="status_order_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Update Order Status
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select name="status" id="status_select" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="preparing">Preparing</option>
                                <option value="ready">Ready</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_payment">
                    <input type="hidden" name="order_id" id="payment_order_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-dollar-sign me-2"></i>Update Payment Status
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" id="payment_select" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Update Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status_select').value = currentStatus;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        function openPaymentModal(orderId, currentPayment) {
            document.getElementById('payment_order_id').value = orderId;
            document.getElementById('payment_select').value = currentPayment;
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
    </script>
</body>
</html>
