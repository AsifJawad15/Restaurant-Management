<?php
/**
 * Customer Loyalty Points Management
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

try {
    $conn = getDBConnection();
    
    // Handle loyalty points update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_points'])) {
        $customerId = intval($_POST['customer_id']);
        $points = intval($_POST['points']);
        $action = $_POST['action']; // 'add' or 'deduct'
        $reason = trim($_POST['reason']);
        
        if ($customerId <= 0 || $points <= 0) {
            $error = "Invalid customer or points value!";
        } else {
            // Get current points
            $stmt = $conn->prepare("SELECT loyalty_points FROM customer_profiles WHERE user_id = ?");
            $stmt->execute([$customerId]);
            $profile = $stmt->fetch();
            
            if (!$profile) {
                $error = "Customer profile not found!";
            } else {
                $currentPoints = $profile['loyalty_points'];
                $newPoints = ($action === 'add') ? $currentPoints + $points : $currentPoints - $points;
                
                if ($newPoints < 0) {
                    $error = "Cannot deduct more points than available!";
                } else {
                    // Update points
                    $stmt = $conn->prepare("UPDATE customer_profiles SET loyalty_points = ? WHERE user_id = ?");
                    if ($stmt->execute([$newPoints, $customerId])) {
                        $success = "Loyalty points updated successfully!";
                    } else {
                        $error = "Failed to update loyalty points!";
                    }
                }
            }
        }
    }
    
    // Handle loyalty tier update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tier'])) {
        $customerId = intval($_POST['customer_id']);
        $tier = $_POST['tier'];
        
        $stmt = $conn->prepare("UPDATE customer_profiles SET loyalty_tier = ? WHERE user_id = ?");
        if ($stmt->execute([$tier, $customerId])) {
            $success = "Customer tier updated successfully!";
        } else {
            $error = "Failed to update tier!";
        }
    }
    
    // Search/Filter
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $tierFilter = isset($_GET['tier']) ? $_GET['tier'] : '';
    
    // Get customers with loyalty points
    $sql = "
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone, 
               cp.loyalty_points, cp.loyalty_tier, cp.total_spent,
               COUNT(DISTINCT o.id) as total_orders
        FROM users u
        JOIN customer_profiles cp ON u.id = cp.user_id
        LEFT JOIN orders o ON u.id = o.customer_id
        WHERE u.user_type = 'customer'
    ";
    
    $params = [];
    
    if ($searchQuery) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    $sql .= " GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, 
              cp.loyalty_points, cp.loyalty_tier, cp.total_spent";
    
    if ($tierFilter) {
        $sql .= " HAVING cp.loyalty_tier = ?";
        $params[] = $tierFilter;
    }
    
    $sql .= " ORDER BY cp.loyalty_points DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
    
    // Get loyalty statistics
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total_customers,
            SUM(loyalty_points) as total_points,
            AVG(loyalty_points) as avg_points,
            MAX(loyalty_points) as max_points
        FROM customer_profiles
    ");
    $stats = $stmt->fetch();
    
    // Get tier distribution
    $stmt = $conn->query("
        SELECT 
            COALESCE(loyalty_tier, 'bronze') as tier,
            COUNT(*) as count
        FROM customer_profiles
        GROUP BY loyalty_tier
    ");
    $tierDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Loyalty tier benefits
$tierBenefits = [
    'bronze' => ['discount' => 5, 'points_multiplier' => 1, 'min_points' => 0],
    'silver' => ['discount' => 10, 'points_multiplier' => 1.5, 'min_points' => 500],
    'gold' => ['discount' => 15, 'points_multiplier' => 2, 'min_points' => 1000],
    'platinum' => ['discount' => 20, 'points_multiplier' => 2.5, 'min_points' => 2000]
];

function getTierBadgeClass($tier) {
    $classes = [
        'bronze' => 'bg-warning',
        'silver' => 'bg-secondary',
        'gold' => 'bg-warning',
        'platinum' => 'bg-dark'
    ];
    return $classes[$tier] ?? 'bg-secondary';
}

function calculateDiscount($points) {
    if ($points >= 2000) return 20;
    if ($points >= 1000) return 15;
    if ($points >= 500) return 10;
    return 5;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Loyalty Points - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .loyalty-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #c5a572;
        }
        .stat-label {
            color: #666;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .tier-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tier-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .tier-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .tier-bronze { background: #cd7f32; color: white; }
        .tier-silver { background: #C0C0C0; color: white; }
        .tier-gold { background: #FFD700; color: #333; }
        .tier-platinum { background: #E5E4E2; color: #333; }
        
        .points-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
        .discount-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'Customer Loyalty Points';
    $page_icon = 'fas fa-gift';
    ?>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>

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
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="loyalty-stats">
                    <div class="stat-card">
                        <div class="stat-icon text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_points']); ?></div>
                        <div class="stat-label">Total Points Issued</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon text-success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['avg_points']); ?></div>
                        <div class="stat-label">Average Points</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon text-danger">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['max_points']); ?></div>
                        <div class="stat-label">Highest Points</div>
                    </div>
                </div>

                <!-- Loyalty Tiers Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">
                            <i class="fas fa-medal me-2"></i>
                            Loyalty Tiers & Benefits
                        </h5>
                    </div>
                    
                    <?php foreach ($tierBenefits as $tier => $benefits): ?>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="tier-card">
                                <div class="tier-header">
                                    <div class="tier-icon tier-<?php echo $tier; ?>">
                                        <i class="fas fa-medal"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-capitalize"><?php echo $tier; ?></h6>
                                        <small class="text-muted">
                                            <?php echo isset($tierDistribution[$tier]) ? $tierDistribution[$tier] : 0; ?> customers
                                        </small>
                                    </div>
                                </div>
                                <div class="small">
                                    <div class="mb-2">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        Min Points: <strong><?php echo $benefits['min_points']; ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-percentage text-success me-1"></i>
                                        Discount: <strong><?php echo $benefits['discount']; ?>%</strong>
                                    </div>
                                    <div>
                                        <i class="fas fa-times text-primary me-1"></i>
                                        Points: <strong><?php echo $benefits['points_multiplier']; ?>x</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search Customer</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name or email..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filter by Tier</label>
                                <select name="tier" class="form-select">
                                    <option value="">All Tiers</option>
                                    <option value="bronze" <?php echo $tierFilter === 'bronze' ? 'selected' : ''; ?>>Bronze</option>
                                    <option value="silver" <?php echo $tierFilter === 'silver' ? 'selected' : ''; ?>>Silver</option>
                                    <option value="gold" <?php echo $tierFilter === 'gold' ? 'selected' : ''; ?>>Gold</option>
                                    <option value="platinum" <?php echo $tierFilter === 'platinum' ? 'selected' : ''; ?>>Platinum</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="data-table">
                    <div class="table-header p-3">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Customer Loyalty Points (<?php echo count($customers); ?>)
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Points</th>
                                    <th>Tier</th>
                                    <th>Discount</th>
                                    <th>Orders</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($customers)): ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="points-badge">
                                                    <i class="fas fa-star me-1"></i>
                                                    <?php echo number_format($customer['loyalty_points']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getTierBadgeClass($customer['loyalty_tier'] ?? 'bronze'); ?> text-capitalize">
                                                    <?php echo $customer['loyalty_tier'] ?? 'Bronze'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="discount-badge">
                                                    <?php echo calculateDiscount($customer['loyalty_points']); ?>% OFF
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $customer['total_orders']; ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#managePointsModal"
                                                        data-customer-id="<?php echo $customer['id']; ?>"
                                                        data-customer-name="<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>"
                                                        data-current-points="<?php echo $customer['loyalty_points']; ?>">
                                                    <i class="fas fa-edit"></i> Manage
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No customers found
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

    <!-- Manage Points Modal -->
    <div class="modal fade" id="managePointsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-star me-2"></i>
                        Manage Loyalty Points
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="customer_id" id="modal_customer_id">
                        
                        <div class="alert alert-info">
                            <strong id="modal_customer_name"></strong><br>
                            Current Points: <strong id="modal_current_points"></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select" required>
                                <option value="add">Add Points</option>
                                <option value="deduct">Deduct Points</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Points Amount</label>
                            <input type="number" name="points" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="e.g., Bonus points, Order refund, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_points" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Points
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate modal with customer data
        const managePointsModal = document.getElementById('managePointsModal');
        managePointsModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const customerId = button.getAttribute('data-customer-id');
            const customerName = button.getAttribute('data-customer-name');
            const currentPoints = button.getAttribute('data-current-points');
            
            document.getElementById('modal_customer_id').value = customerId;
            document.getElementById('modal_customer_name').textContent = customerName;
            document.getElementById('modal_current_points').textContent = currentPoints;
        });
    </script>
</body>
</html>
