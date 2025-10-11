<?php
/**
 * Admin Sidebar Navigation
 * ASIF - Backend & Database Developer
 * Common sidebar component for all admin pages
 */

// Include config file if not already included
if (!function_exists('getDBConnection')) {
    require_once '../includes/config.php';
}

// Get current page name for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get pending orders count if not already set
if (!isset($pendingOrders)) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('pending', 'confirmed', 'preparing')");
        $pendingOrders = $stmt->fetch()['total'];
    } catch (PDOException $e) {
        $pendingOrders = 0;
    }
}
?>

<!-- Sidebar -->
<nav class="admin-sidebar">
    <div class="sidebar-header">
        <i class="fas fa-utensils sidebar-logo"></i>
        <h4 class="sidebar-title">Delicious Restaurant</h4>
        <small class="text-muted">Admin Panel</small>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <div class="nav-section">Menu Management</div>
        <li class="nav-item">
            <a href="categories.php" class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="menu-items.php" class="nav-link <?php echo $current_page === 'menu-items.php' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i>
                <span>Menu Items</span>
            </a>
        </li>
        
        <div class="nav-section">Orders & Sales</div>
        <li class="nav-item">
            <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <?php if ($pendingOrders > 0): ?>
                    <span class="badge bg-warning ms-auto"><?php echo $pendingOrders; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a href="reservations.php" class="nav-link <?php echo $current_page === 'reservations.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Reservations</span>
            </a>
        </li>
        
        <div class="nav-section">Customer Management</div>
        <li class="nav-item">
            <a href="customers.php" class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="loyalty-points.php" class="nav-link <?php echo $current_page === 'loyalty-points.php' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i>
                <span>Loyalty Points</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="reviews.php" class="nav-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>
        </li>
        
        <div class="nav-section">Restaurant Management</div>
        <li class="nav-item">
            <a href="tables.php" class="nav-link <?php echo $current_page === 'tables.php' ? 'active' : ''; ?>">
                <i class="fas fa-chair"></i>
                <span>Tables</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="staff.php" class="nav-link <?php echo $current_page === 'staff.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Staff</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="inventory.php" class="nav-link <?php echo $current_page === 'inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span>Inventory</span>
            </a>
        </li>
        
        <div class="nav-section">Reports & Analytics</div>
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="analytics.php" class="nav-link <?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
        </li>
        
        <div class="nav-section">Settings</div>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
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
