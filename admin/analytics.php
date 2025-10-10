<?php
/**
 * Analytics Page - Visual Data Analysis
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

try {
    $conn = getDBConnection();
    
    // Sales trend for last 30 days
    $stmt = $conn->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders,
            SUM(final_amount) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND payment_status = 'paid'
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $salesTrend = $stmt->fetchAll();
    
    // Revenue by category
    $stmt = $conn->query("
        SELECT 
            c.name as category,
            SUM(oi.total_price) as revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN categories c ON mi.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND o.payment_status = 'paid'
        GROUP BY c.id
        ORDER BY revenue DESC
    ");
    $categoryData = $stmt->fetchAll();
    
    // Order status distribution
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    $statusData = $stmt->fetchAll();
    
    // Order type distribution
    $stmt = $conn->query("
        SELECT 
            order_type,
            COUNT(*) as count,
            SUM(final_amount) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND payment_status = 'paid'
        GROUP BY order_type
    ");
    $typeData = $stmt->fetchAll();
    
    // Hourly order pattern (average for last 30 days)
    $stmt = $conn->query("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as orders,
            AVG(final_amount) as avg_revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ");
    $hourlyPattern = $stmt->fetchAll();
    
    // Top 10 items by revenue
    $stmt = $conn->query("
        SELECT 
            mi.name,
            SUM(oi.total_price) as revenue,
            SUM(oi.quantity) as quantity
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND o.payment_status = 'paid'
        GROUP BY mi.id
        ORDER BY revenue DESC
        LIMIT 10
    ");
    $topItems = $stmt->fetchAll();
    
    // Customer growth
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_customers
        FROM users 
        WHERE user_type = 'customer'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $customerGrowth = $stmt->fetchAll();
    
    // Payment method distribution
    $stmt = $conn->query("
        SELECT 
            payment_method,
            COUNT(*) as count
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND payment_status = 'paid'
        GROUP BY payment_method
    ");
    $paymentData = $stmt->fetchAll();
    
    // Loyalty tier distribution
    $stmt = $conn->query("
        SELECT 
            COALESCE(loyalty_tier, 'bronze') as tier,
            COUNT(*) as count
        FROM customer_profiles
        GROUP BY loyalty_tier
    ");
    $loyaltyData = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Prepare data for JavaScript
$salesDates = json_encode(array_column($salesTrend, 'date'));
$salesOrders = json_encode(array_column($salesTrend, 'orders'));
$salesRevenue = json_encode(array_column($salesTrend, 'revenue'));

$categoryNames = json_encode(array_column($categoryData, 'category'));
$categoryRevenue = json_encode(array_column($categoryData, 'revenue'));

$statusLabels = json_encode(array_column($statusData, 'status'));
$statusCounts = json_encode(array_column($statusData, 'count'));

$typeLabels = json_encode(array_column($typeData, 'order_type'));
$typeCounts = json_encode(array_column($typeData, 'count'));

$hours = json_encode(array_column($hourlyPattern, 'hour'));
$hourlyOrders = json_encode(array_column($hourlyPattern, 'orders'));

$itemNames = json_encode(array_column($topItems, 'name'));
$itemRevenue = json_encode(array_column($topItems, 'revenue'));

$paymentLabels = json_encode(array_column($paymentData, 'payment_method'));
$paymentCounts = json_encode(array_column($paymentData, 'count'));

$loyaltyTiers = json_encode(array_column($loyaltyData, 'tier'));
$loyaltyCounts = json_encode(array_column($loyaltyData, 'count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            height: 100%;
        }
        .analytics-card h5 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #c5a572;
        }
        .chart-container {
            position: relative;
            height: 350px;
        }
        .stat-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            margin: 0.25rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'Restaurant Analytics';
    $page_icon = 'fas fa-chart-line';
    ?>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Sales Trend -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="analytics-card">
                            <h5><i class="fas fa-chart-line me-2"></i>Sales Trend (Last 30 Days)</h5>
                            <div class="chart-container">
                                <canvas id="salesTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Revenue & Order Status -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-tags me-2"></i>Revenue by Category</h5>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-tasks me-2"></i>Order Status Distribution</h5>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Items & Order Types -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-fire me-2"></i>Top 10 Items by Revenue</h5>
                            <div class="chart-container">
                                <canvas id="topItemsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-utensils me-2"></i>Order Type Distribution</h5>
                            <div class="chart-container">
                                <canvas id="orderTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hourly Pattern & Payment Methods -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-clock me-2"></i>Hourly Order Pattern</h5>
                            <div class="chart-container">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-credit-card me-2"></i>Payment Method Distribution</h5>
                            <div class="chart-container">
                                <canvas id="paymentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loyalty Tiers -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="analytics-card">
                            <h5><i class="fas fa-gift me-2"></i>Customer Loyalty Tiers</h5>
                            <div class="chart-container">
                                <canvas id="loyaltyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart.js default configuration
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#666';

        // Color palette
        const colors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8',
            gold: '#c5a572'
        };

        // Sales Trend Chart
        new Chart(document.getElementById('salesTrendChart'), {
            type: 'line',
            data: {
                labels: <?php echo $salesDates; ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo $salesRevenue; ?>,
                    borderColor: colors.gold,
                    backgroundColor: colors.gold + '20',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: <?php echo $salesOrders; ?>,
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });

        // Category Revenue Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo $categoryNames; ?>,
                datasets: [{
                    data: <?php echo $categoryRevenue; ?>,
                    backgroundColor: [
                        colors.primary,
                        colors.gold,
                        colors.success,
                        colors.warning,
                        colors.info,
                        colors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Order Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: <?php echo $statusLabels; ?>,
                datasets: [{
                    data: <?php echo $statusCounts; ?>,
                    backgroundColor: [
                        colors.warning,
                        colors.info,
                        colors.primary,
                        colors.success,
                        colors.gold,
                        '#28a745',
                        colors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Top Items Chart
        new Chart(document.getElementById('topItemsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo $itemNames; ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo $itemRevenue; ?>,
                    backgroundColor: colors.gold,
                    borderColor: colors.gold,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Order Type Chart
        new Chart(document.getElementById('orderTypeChart'), {
            type: 'polarArea',
            data: {
                labels: <?php echo $typeLabels; ?>,
                datasets: [{
                    data: <?php echo $typeCounts; ?>,
                    backgroundColor: [
                        colors.primary + '80',
                        colors.success + '80',
                        colors.warning + '80'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Hourly Pattern Chart
        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: <?php echo $hours; ?>.map(h => h + ':00'),
                datasets: [{
                    label: 'Orders',
                    data: <?php echo $hourlyOrders; ?>,
                    backgroundColor: colors.primary,
                    borderColor: colors.primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Payment Method Chart
        new Chart(document.getElementById('paymentChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo $paymentLabels; ?>,
                datasets: [{
                    data: <?php echo $paymentCounts; ?>,
                    backgroundColor: [
                        colors.success,
                        colors.primary,
                        colors.warning,
                        colors.info
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Loyalty Tiers Chart
        new Chart(document.getElementById('loyaltyChart'), {
            type: 'bar',
            data: {
                labels: <?php echo $loyaltyTiers; ?>.map(t => t.charAt(0).toUpperCase() + t.slice(1)),
                datasets: [{
                    label: 'Customers',
                    data: <?php echo $loyaltyCounts; ?>,
                    backgroundColor: [
                        '#cd7f32',  // bronze
                        '#C0C0C0',  // silver
                        '#FFD700',  // gold
                        '#E5E4E2'   // platinum
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
