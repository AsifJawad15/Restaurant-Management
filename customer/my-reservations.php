<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('login.php?redirect=my-reservations.php');
}

$db = getDB();
$user = $auth->getCurrentUser();

$success = '';
$error = '';

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_reservation'])) {
    try {
        $reservation_id = (int)$_POST['reservation_id'];
        
        // Verify the reservation belongs to the current user
        $stmt = $db->prepare("SELECT * FROM reservations WHERE id = ? AND customer_id = ?");
        $stmt->execute([$reservation_id, $user['id']]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            throw new Exception('Reservation not found.');
        }
        
        // Check if cancellation is allowed (at least 2 hours before reservation time)
        $reservation_datetime = $reservation['reservation_date'] . ' ' . $reservation['reservation_time'];
        $min_cancel_time = strtotime($reservation_datetime) - (2 * 60 * 60); // 2 hours before
        
        if (time() > $min_cancel_time) {
            throw new Exception('Cancellations must be made at least 2 hours before the reservation time.');
        }
        
        // Update reservation status
        $stmt = $db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$reservation_id]);
        
        $success = 'Reservation cancelled successfully.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's reservations
$stmt = $db->prepare("
    SELECT r.*, t.table_number, t.capacity, t.location 
    FROM reservations r 
    JOIN tables t ON r.table_id = t.id 
    WHERE r.customer_id = ? 
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
$stmt->execute([$user['id']]);
$reservations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - My Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/home-style.css" rel="stylesheet">
    <style>
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
        .reservation-date {
            font-size: 1.1rem;
            font-weight: bold;
            color: #495057;
        }
        .reservation-time {
            font-size: 1.3rem;
            color: #d4af37;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="home.php"><i class="fas fa-utensils me-2"></i><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="home.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item me-3 d-flex align-items-center">
                        <i class="fas fa-user text-muted me-2"></i>
                        <span class="text-muted">Hi, <?php echo htmlspecialchars($user['first_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-alt me-2 text-primary"></i>My Reservations</h2>
                    <a href="reservations.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Make New Reservation
                    </a>
                </div>

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

                <?php if (empty($reservations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Reservations Found</h4>
                        <p class="text-muted">You haven't made any reservations yet.</p>
                        <a href="reservations.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Make Your First Reservation
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($reservations as $reservation): ?>
                        <div class="reservation-card">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="reservation-date">
                                        <?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?>
                                    </div>
                                    <div class="reservation-time">
                                        <?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-users text-muted"></i>
                                        <div><strong><?php echo $reservation['party_size']; ?></strong> <?php echo $reservation['party_size'] == 1 ? 'person' : 'people'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-table text-muted"></i>
                                        <div><strong>Table <?php echo htmlspecialchars($reservation['table_number']); ?></strong></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['location']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <?php if ($reservation['status'] == 'pending' || $reservation['status'] == 'confirmed'): ?>
                                        <?php
                                        $reservation_datetime = $reservation['reservation_date'] . ' ' . $reservation['reservation_time'];
                                        $min_cancel_time = strtotime($reservation_datetime) - (2 * 60 * 60);
                                        ?>
                                        <?php if (time() <= $min_cancel_time): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <button type="submit" name="cancel_reservation" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-times me-1"></i>Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <small class="text-muted">Cannot cancel<br>(less than 2h before)</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-outline-info btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $reservation['id']; ?>">
                                        <i class="fas fa-eye me-1"></i>Details
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
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-calendar-alt me-2"></i>Reservation Details
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Reservation ID:</strong><br>
                                                #<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?>
                                            </div>
                                            <div class="col-6">
                                                <strong>Status:</strong><br>
                                                <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Date & Time:</strong><br>
                                                <?php echo date('l, F d, Y', strtotime($reservation['reservation_date'])); ?><br>
                                                <?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?>
                                            </div>
                                            <div class="col-6">
                                                <strong>Party Size:</strong><br>
                                                <?php echo $reservation['party_size']; ?> <?php echo $reservation['party_size'] == 1 ? 'person' : 'people'; ?>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Table:</strong><br>
                                                Table <?php echo htmlspecialchars($reservation['table_number']); ?><br>
                                                <small class="text-muted">Seats <?php echo $reservation['capacity']; ?> • <?php echo htmlspecialchars($reservation['location']); ?></small>
                                            </div>
                                            <div class="col-6">
                                                <strong>Booked:</strong><br>
                                                <?php echo formatDateFlexible($reservation['created_at']); ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($reservation['special_requests'])): ?>
                                            <hr>
                                            <strong>Special Requests:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($reservation['special_requests'])); ?>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>