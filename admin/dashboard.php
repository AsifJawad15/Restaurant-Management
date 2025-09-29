<?php
/**
 * Admin Dashboard
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

// Get dashboard statistics
try {
    $conn = getDBConnection();
    
    // Total orders today
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $todaysOrders = $stmt->fetch()['total'];
    
    // Total revenue today
    $stmt = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'");
    $todaysRevenue = $stmt->fetch()['total'];
    
    // Total menu items
    $stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1");
    $totalMenuItems = $stmt->fetch()['total'];
    
    // Pending orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('pending', 'confirmed', 'preparing')");
    $pendingOrders = $stmt->fetch()['total'];
    
    // Recent orders
    $stmt = $conn->query("
        SELECT o.*, u.first_name, u.last_name, u.email 
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
    
    // Popular menu items
    $stmt = $conn->query("
        SELECT mi.name, mi.price, COUNT(oi.id) as order_count,
               SUM(oi.total_price) as total_revenue
        FROM menu_items mi
        JOIN order_items oi ON mi.id = oi.menu_item_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
        GROUP BY mi.id
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $popularItems = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Delicious Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <div class="nav-section">Menu Management</div>
                <li class="nav-item">
                    <a href="menu-items.php" class="nav-link">
                        <i class="fas fa-utensils"></i>
                        <span>Menu Items</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                
                <div class="nav-section">Orders & Sales</div>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                        <?php if ($pendingOrders > 0): ?>
                            <span class="badge bg-warning ms-auto"><?php echo $pendingOrders; ?></span>
                        <?php endif; ?>
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
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
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
                    <h1 class="page-title">Dashboard</h1>
                    <div class="header-actions">
                        <div class="dropdown admin-dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <div class="admin-avatar">
                                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                                </div>
                                <span><?php echo $_SESSION['admin_name']; ?></span>
                                <i class="fas fa-chevron-down ms-2"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Welcome Message -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-success border-0">
                            <h5 class="alert-heading">
                                <i class="fas fa-check-circle me-2"></i>
                                Welcome back, <?php echo $_SESSION['admin_name']; ?>!
                            </h5>
                            <p class="mb-0">Here's what's happening at Delicious Restaurant today.</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $todaysOrders; ?></h3>
                            <p class="stats-label">Today's Orders</p>
                            <p class="stats-change positive">
                                <i class="fas fa-arrow-up"></i> +12% from yesterday
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h3 class="stats-number"><?php echo formatPrice($todaysRevenue); ?></h3>
                            <p class="stats-label">Today's Revenue</p>
                            <p class="stats-change positive">
                                <i class="fas fa-arrow-up"></i> +8% from yesterday
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon info">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $totalMenuItems; ?></h3>
                            <p class="stats-label">Active Menu Items</p>
                            <p class="stats-change">
                                <i class="fas fa-minus"></i> No change
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $pendingOrders; ?></h3>
                            <p class="stats-label">Pending Orders</p>
                            <p class="stats-change">
                                <i class="fas fa-exclamation-triangle"></i> Needs attention
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Popular Items -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="data-table">
                            <div class="table-header p-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Recent Orders
                                </h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentOrders)): ?>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                    <td><?php echo formatPrice($order['final_amount']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDateTime($order['created_at']); ?></td>
                                                    <td>
                                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    No recent orders found
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="data-table">
                            <div class="table-header p-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-fire me-2"></i>
                                    Popular Items (7 days)
                                </h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Orders</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($popularItems)): ?>
                                            <?php foreach ($popularItems as $item): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo formatPrice($item['price']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $item['order_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <small><?php echo formatPrice($item['total_revenue']); ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3">
                                                    <small class="text-muted">No data available</small>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>