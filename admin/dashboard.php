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
    
    // Total categories
    $stmt = $conn->query("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
    $totalCategories = $stmt->fetch()['total'];
    
    // Featured items count
    $stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items WHERE is_featured = 1 AND is_available = 1");
    $featuredItems = $stmt->fetch()['total'];
    
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

<?php
// Set page-specific variables
$page_title = 'Dashboard';
$page_icon = 'fas fa-tachometer-alt';
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
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>

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
                            <p class="stats-label">Menu Items</p>
                            <p class="stats-change">
                                <i class="fas fa-tags"></i> <?php echo $totalCategories; ?> categories
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon warning">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $featuredItems; ?></h3>
                            <p class="stats-label">Featured Items</p>
                            <p class="stats-change">
                                <i class="fas fa-clock"></i> <?php echo $pendingOrders; ?> pending orders
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Menu Management Quick Actions -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="stats-card">
                            <h5 class="mb-3">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Quick Menu Actions
                            </h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="categories.php" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-tags me-2"></i>
                                        Manage Categories
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="menu-items.php" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-utensils me-2"></i>
                                        Manage Menu Items
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="menu-items.php?category=0" class="btn btn-outline-info w-100 mb-2">
                                        <i class="fas fa-star me-2"></i>
                                        View Featured Items
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" onclick="alert('Coming soon!')" class="btn btn-outline-success w-100 mb-2">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Menu Analytics
                                    </a>
                                </div>
                            </div>
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