<?php
/**
 * Reports Page - Generate Restaurant Reports
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$dateRange = isset($_GET['range']) ? $_GET['range'] : 'today';
$customStart = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$customEnd = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Define date range SQL conditions
switch ($dateRange) {
    case 'today':
        $dateCondition = "DATE(created_at) = CURDATE()";
        $reportTitle = "Today's Report";
        break;
    case 'yesterday':
        $dateCondition = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $reportTitle = "Yesterday's Report";
        break;
    case 'week':
        $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $reportTitle = "Last 7 Days Report";
        break;
    case 'month':
        $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $reportTitle = "Last 30 Days Report";
        break;
    case 'year':
        $dateCondition = "YEAR(created_at) = YEAR(NOW())";
        $reportTitle = "This Year Report";
        break;
    case 'custom':
        $dateCondition = "DATE(created_at) BETWEEN '$customStart' AND '$customEnd'";
        $reportTitle = "Custom Report ($customStart to $customEnd)";
        break;
    default:
        $dateCondition = "DATE(created_at) = CURDATE()";
        $reportTitle = "Today's Report";
}

try {
    $conn = getDBConnection();
    
    // Sales Summary
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(final_amount), 0) as total_revenue,
            COALESCE(AVG(final_amount), 0) as avg_order_value,
            COALESCE(SUM(tax_amount), 0) as total_tax,
            COALESCE(SUM(discount_amount), 0) as total_discounts
        FROM orders 
        WHERE $dateCondition AND payment_status = 'paid'
    ");
    $salesSummary = $stmt->fetch();
    
    // Order Status Breakdown
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(final_amount) as revenue
        FROM orders 
        WHERE $dateCondition
        GROUP BY status
        ORDER BY count DESC
    ");
    $orderStatus = $stmt->fetchAll();
    
    // Order Type Breakdown
    $stmt = $conn->query("
        SELECT 
            order_type,
            COUNT(*) as count,
            SUM(final_amount) as revenue
        FROM orders 
        WHERE $dateCondition AND payment_status = 'paid'
        GROUP BY order_type
    ");
    $orderTypes = $stmt->fetchAll();
    
    // Top Selling Items
    $stmt = $conn->query("
        SELECT 
            mi.name,
            mi.price,
            c.name as category,
            COUNT(oi.id) as orders_count,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.total_price) as total_revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        LEFT JOIN categories c ON mi.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE $dateCondition AND o.payment_status = 'paid'
        GROUP BY mi.id
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $topItems = $stmt->fetchAll();
    
    // Revenue by Category
    $stmt = $conn->query("
        SELECT 
            c.name as category,
            COUNT(DISTINCT oi.order_id) as orders,
            SUM(oi.quantity) as items_sold,
            SUM(oi.total_price) as revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN categories c ON mi.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE $dateCondition AND o.payment_status = 'paid'
        GROUP BY c.id
        ORDER BY revenue DESC
    ");
    $categoryRevenue = $stmt->fetchAll();
    
    // Customer Statistics
    $stmt = $conn->query("
        SELECT 
            COUNT(DISTINCT customer_id) as unique_customers,
            COUNT(*) / COUNT(DISTINCT customer_id) as avg_orders_per_customer
        FROM orders
        WHERE $dateCondition
    ");
    $customerStats = $stmt->fetch();
    
    // Top Customers
    $stmt = $conn->query("
        SELECT 
            u.first_name,
            u.last_name,
            u.email,
            COUNT(o.id) as order_count,
            SUM(o.final_amount) as total_spent,
            cp.loyalty_points
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN customer_profiles cp ON u.id = cp.user_id
        WHERE $dateCondition AND o.payment_status = 'paid'
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $topCustomers = $stmt->fetchAll();
    
    // Payment Method Breakdown
    $stmt = $conn->query("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(final_amount) as revenue
        FROM orders 
        WHERE $dateCondition AND payment_status = 'paid'
        GROUP BY payment_method
    ");
    $paymentMethods = $stmt->fetchAll();
    
    // Hourly Sales (for today/yesterday)
    if ($dateRange == 'today' || $dateRange == 'yesterday') {
        $stmt = $conn->query("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as orders,
                SUM(final_amount) as revenue
            FROM orders 
            WHERE $dateCondition AND payment_status = 'paid'
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ");
        $hourlySales = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .report-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .report-card h5 {
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #c5a572;
        }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #c5a572;
        }
        .metric-label {
            color: #666;
            font-size: 0.875rem;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }
        .print-btn {
            background: #c5a572;
            color: white;
            border: none;
        }
        .print-btn:hover {
            background: #b08d5f;
            color: white;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .report-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar no-print">
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
                
                <div class="nav-section">Reports & Analytics</div>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link active">
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
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Top Header -->
            <header class="admin-header no-print">
                <div class="header-content">
                    <h1 class="page-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        Restaurant Reports
                    </h1>
                    <div class="header-actions">
                        <button onclick="window.print()" class="btn print-btn">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Date Range Filter -->
                <div class="report-card no-print">
                    <h5>Select Report Period</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select name="range" class="form-select" id="rangeSelect">
                                <option value="today" <?php echo $dateRange == 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $dateRange == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="week" <?php echo $dateRange == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $dateRange == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="year" <?php echo $dateRange == 'year' ? 'selected' : ''; ?>>This Year</option>
                                <option value="custom" <?php echo $dateRange == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="startDateDiv" style="display: <?php echo $dateRange == 'custom' ? 'block' : 'none'; ?>;">
                            <input type="date" name="start_date" class="form-control" value="<?php echo $customStart; ?>">
                        </div>
                        <div class="col-md-3" id="endDateDiv" style="display: <?php echo $dateRange == 'custom' ? 'block' : 'none'; ?>;">
                            <input type="date" name="end_date" class="form-control" value="<?php echo $customEnd; ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Report Header -->
                <div class="report-header">
                    <h2><?php echo $reportTitle; ?></h2>
                    <p class="mb-0">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
                </div>

                <!-- Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                            <div class="metric-value"><?php echo number_format($salesSummary['total_orders']); ?></div>
                            <div class="metric-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                            <div class="metric-value"><?php echo formatPrice($salesSummary['total_revenue']); ?></div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                            <div class="metric-value"><?php echo formatPrice($salesSummary['avg_order_value']); ?></div>
                            <div class="metric-label">Avg Order Value</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <i class="fas fa-percentage fa-2x text-warning mb-2"></i>
                            <div class="metric-value"><?php echo formatPrice($salesSummary['total_discounts']); ?></div>
                            <div class="metric-label">Total Discounts</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Order Status Breakdown -->
                    <div class="col-lg-6">
                        <div class="report-card">
                            <h5><i class="fas fa-tasks me-2"></i>Order Status Breakdown</h5>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderStatus as $status): ?>
                                        <tr>
                                            <td class="text-capitalize"><?php echo $status['status']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $status['count']; ?></span></td>
                                            <td><?php echo formatPrice($status['revenue'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Order Type Breakdown -->
                    <div class="col-lg-6">
                        <div class="report-card">
                            <h5><i class="fas fa-utensils me-2"></i>Order Type Distribution</h5>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Count</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderTypes as $type): ?>
                                        <tr>
                                            <td class="text-capitalize"><?php echo str_replace('_', ' ', $type['order_type']); ?></td>
                                            <td><span class="badge bg-success"><?php echo $type['count']; ?></span></td>
                                            <td><?php echo formatPrice($type['revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Selling Items -->
                <div class="report-card">
                    <h5><i class="fas fa-fire me-2"></i>Top 10 Selling Items</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Orders</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($topItems as $item): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo $item['orders_count']; ?></td>
                                        <td><?php echo $item['total_quantity']; ?></td>
                                        <td><strong><?php echo formatPrice($item['total_revenue']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Revenue by Category -->
                <div class="report-card">
                    <h5><i class="fas fa-tags me-2"></i>Revenue by Category</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Orders</th>
                                    <th>Items Sold</th>
                                    <th>Revenue</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryRevenue as $cat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cat['category']); ?></strong></td>
                                        <td><?php echo $cat['orders']; ?></td>
                                        <td><?php echo $cat['items_sold']; ?></td>
                                        <td><?php echo formatPrice($cat['revenue']); ?></td>
                                        <td>
                                            <?php 
                                            $percentage = ($salesSummary['total_revenue'] > 0) 
                                                ? ($cat['revenue'] / $salesSummary['total_revenue']) * 100 
                                                : 0;
                                            echo number_format($percentage, 1) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Customer Statistics -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="report-card">
                            <h5><i class="fas fa-users me-2"></i>Top 10 Customers</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Customer</th>
                                            <th>Email</th>
                                            <th>Orders</th>
                                            <th>Total Spent</th>
                                            <th>Loyalty Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; foreach ($topCustomers as $customer): ?>
                                            <tr>
                                                <td><?php echo $rank++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                <td><?php echo $customer['order_count']; ?></td>
                                                <td><strong><?php echo formatPrice($customer['total_spent']); ?></strong></td>
                                                <td><span class="badge bg-warning"><?php echo number_format($customer['loyalty_points'] ?? 0); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="report-card">
                    <h5><i class="fas fa-credit-card me-2"></i>Payment Method Breakdown</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th>Transactions</th>
                                <th>Revenue</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentMethods as $method): ?>
                                <tr>
                                    <td class="text-capitalize"><?php echo htmlspecialchars($method['payment_method']); ?></td>
                                    <td><?php echo $method['count']; ?></td>
                                    <td><?php echo formatPrice($method['revenue']); ?></td>
                                    <td>
                                        <?php 
                                        $percentage = ($salesSummary['total_revenue'] > 0) 
                                            ? ($method['revenue'] / $salesSummary['total_revenue']) * 100 
                                            : 0;
                                        echo number_format($percentage, 1) . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Report Footer -->
                <div class="report-card text-center">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        This report was generated by Delicious Restaurant Management System
                    </p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide custom date inputs
        document.getElementById('rangeSelect').addEventListener('change', function() {
            const customInputs = this.value === 'custom';
            document.getElementById('startDateDiv').style.display = customInputs ? 'block' : 'none';
            document.getElementById('endDateDiv').style.display = customInputs ? 'block' : 'none';
        });
    </script>
</body>
</html>
