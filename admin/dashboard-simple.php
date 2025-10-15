<?php
/**
 * Admin Dashboard - Simplified Approach
 * ASIF - Backend & Database Developer
 */

// Include simple authentication
require_once 'includes/simple-auth.php';

// Require admin login
requireAdminLogin();

// Get database connection
$pdo = getDatabaseConnection();

$success = '';
$error = '';

// Initialize default values to prevent undefined variable errors
$todaysOrders = 0;
$todaysRevenue = 0;
$totalMenuItems = 0;
$totalCategories = 0;
$featuredItems = 0;
$pendingOrders = 0;
$recentOrders = [];
$popularItems = [];

try {
    // Get today's date
    $today = date('Y-m-d');
    
    // Calculate dashboard statistics using direct queries
    // Today's orders count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $todaysOrders = $result ? $result['count'] : 0;
    
    // Today's revenue
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $todaysRevenue = $result ? $result['revenue'] : 0;
    
    // Total menu items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalMenuItems = $result ? $result['count'] : 0;
    
    // Total categories  
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCategories = $result ? $result['count'] : 0;
    
    // Featured items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items WHERE is_featured = 1 AND is_available = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $featuredItems = $result ? $result['count'] : 0;
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pendingOrders = $result ? $result['count'] : 0;
    
    // Recent orders (last 10)
    $stmt = $pdo->query("
        SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
        FROM orders o 
        LEFT JOIN users c ON o.customer_id = c.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Popular items (top 5 by order frequency)
    $stmt = $pdo->query("
        SELECT mi.name, mi.price, COUNT(oi.menu_item_id) as order_count
        FROM menu_items mi
        LEFT JOIN order_items oi ON mi.id = oi.menu_item_id
        WHERE mi.is_available = 1
        GROUP BY mi.id, mi.name, mi.price
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $popularItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Helper function for price formatting
function formatPrice($amount) {
    return number_format($amount, 2);
}

// Helper function for status badge
function getStatusBadge($status) {
    switch($status) {
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'confirmed':
            return '<span class="badge bg-info">Confirmed</span>';
        case 'preparing':
            return '<span class="badge bg-primary">Preparing</span>';
        case 'ready':
            return '<span class="badge bg-success">Ready</span>';
        case 'delivered':
            return '<span class="badge bg-success">Delivered</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

// Get admin info for display
$adminInfo = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="container-fluid py-4">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <h1 class="h3 mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
                    </h1>
                </div>
            </div>
            
            <!-- Quick Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Today's Orders
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $todaysOrders; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Today's Revenue
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo formatPrice($todaysRevenue); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Menu Items
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalMenuItems; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-utensils fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending Orders
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingOrders; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders and Popular Items -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                            <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentOrders)): ?>
                                <p class="text-muted text-center py-4">No orders found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                                    <td>$<?php echo formatPrice($order['total_amount']); ?></td>
                                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Popular Items</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($popularItems)): ?>
                                <p class="text-muted text-center py-4">No data available.</p>
                            <?php else: ?>
                                <?php foreach ($popularItems as $item): ?>
                                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                                        <div>
                                            <div class="font-weight-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="text-muted small">$<?php echo formatPrice($item['price']); ?></div>
                                        </div>
                                        <span class="badge bg-primary"><?php echo $item['order_count']; ?> orders</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>