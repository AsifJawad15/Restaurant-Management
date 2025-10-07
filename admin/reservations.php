<?php
/**
 * Admin Reservations Management
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

// Handle reservation status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = getDBConnection();
        
        if (isset($_POST['update_status'])) {
            $reservation_id = (int)$_POST['reservation_id'];
            $new_status = $_POST['status'];
            
            $valid_statuses = ['pending', 'confirmed', 'seated', 'completed', 'cancelled'];
            if (!in_array($new_status, $valid_statuses)) {
                throw new Exception('Invalid status selected.');
            }
            
            $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $reservation_id]);
            
            $success = 'Reservation status updated successfully.';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? ''; // Show all dates by default

// Build query with filters
$conn = getDBConnection();
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "r.reservation_date = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $conn->prepare("
    SELECT r.*, u.first_name, u.last_name, u.email, u.phone, 
           t.table_number, t.capacity, t.location
    FROM reservations r
    JOIN users u ON r.customer_id = u.id
    JOIN tables t ON r.table_id = t.id
    $where_clause
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Get reservation statistics
$stats_stmt = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'seated' THEN 1 ELSE 0 END) as seated,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM reservations 
    WHERE reservation_date = CURDATE()
");
$today_stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-seated { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .reservation-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .reservation-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-utensils me-2"></i>Restaurant Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php"><i class="fas fa-tags me-1"></i>Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu-items.php"><i class="fas fa-utensils me-1"></i>Menu Items</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reservations.php"><i class="fas fa-calendar-alt me-1"></i>Reservations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="order-details.php"><i class="fas fa-receipt me-1"></i>Orders</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-calendar-alt me-2 text-primary"></i>Reservations Management</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Today's Statistics -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <h5><i class="fas fa-chart-bar me-2"></i>Today's Reservations</h5>
                            <div class="row text-center">
                                <div class="col-md-2">
                                    <div class="bg-primary text-white rounded p-3">
                                        <h3><?php echo $today_stats['total']; ?></h3>
                                        <small>Total</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="bg-warning text-dark rounded p-3">
                                        <h3><?php echo $today_stats['pending']; ?></h3>
                                        <small>Pending</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="bg-info text-white rounded p-3">
                                        <h3><?php echo $today_stats['confirmed']; ?></h3>
                                        <small>Confirmed</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="bg-success text-white rounded p-3">
                                        <h3><?php echo $today_stats['seated']; ?></h3>
                                        <small>Seated</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="bg-secondary text-white rounded p-3">
                                        <h3><?php echo $today_stats['completed']; ?></h3>
                                        <small>Completed</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="bg-danger text-white rounded p-3">
                                        <h3><?php echo $today_stats['cancelled']; ?></h3>
                                        <small>Cancelled</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="seated" <?php echo $status_filter === 'seated' ? 'selected' : ''; ?>>Seated</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="reservations.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reservations List -->
                <?php if (empty($reservations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Reservations Found</h4>
                        <p class="text-muted">No reservations match your current filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reservations as $reservation): ?>
                        <div class="reservation-card">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <strong>#<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></strong><br>
                                        <small class="text-muted"><?php echo formatDate($reservation['created_at']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small><br>
                                        <?php if ($reservation['phone']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['phone']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <strong><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></strong><br>
                                        <strong class="text-primary"><?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="col-md-1">
                                    <div class="text-center">
                                        <i class="fas fa-users text-muted"></i><br>
                                        <strong><?php echo $reservation['party_size']; ?></strong>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-table text-muted"></i><br>
                                        <strong>Table <?php echo htmlspecialchars($reservation['table_number']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['location']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $reservation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $reservation['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="seated" <?php echo $reservation['status'] === 'seated' ? 'selected' : ''; ?>>Seated</option>
                                            <option value="completed" <?php echo $reservation['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $reservation['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </div>
                                
                                <div class="col-md-1">
                                    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $reservation['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (!empty($reservation['special_requests'])): ?>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="fas fa-comment me-1"></i>
                                            <strong>Special Requests:</strong> <?php echo htmlspecialchars($reservation['special_requests']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Detail Modal -->
                        <div class="modal fade" id="detailModal<?php echo $reservation['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-calendar-alt me-2"></i>Reservation #<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                                                <ul class="list-unstyled">
                                                    <li><strong>Name:</strong> <?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></li>
                                                    <li><strong>Email:</strong> <?php echo htmlspecialchars($reservation['email']); ?></li>
                                                    <?php if ($reservation['phone']): ?>
                                                        <li><strong>Phone:</strong> <?php echo htmlspecialchars($reservation['phone']); ?></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-calendar me-2"></i>Reservation Details</h6>
                                                <ul class="list-unstyled">
                                                    <li><strong>Date:</strong> <?php echo date('l, F d, Y', strtotime($reservation['reservation_date'])); ?></li>
                                                    <li><strong>Time:</strong> <?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?></li>
                                                    <li><strong>Party Size:</strong> <?php echo $reservation['party_size']; ?> <?php echo $reservation['party_size'] == 1 ? 'person' : 'people'; ?></li>
                                                    <li><strong>Table:</strong> Table <?php echo htmlspecialchars($reservation['table_number']); ?> (<?php echo htmlspecialchars($reservation['location']); ?>)</li>
                                                    <li><strong>Status:</strong> <span class="status-badge status-<?php echo $reservation['status']; ?>"><?php echo ucfirst($reservation['status']); ?></span></li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($reservation['special_requests'])): ?>
                                            <hr>
                                            <h6><i class="fas fa-comment me-2"></i>Special Requests</h6>
                                            <p><?php echo nl2br(htmlspecialchars($reservation['special_requests'])); ?></p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted"><strong>Booked:</strong> <?php echo formatDateTime($reservation['created_at']); ?></small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted"><strong>Last Updated:</strong> <?php echo formatDateTime($reservation['updated_at']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>