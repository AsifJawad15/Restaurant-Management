<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('login.php?redirect=reservations.php');
}

$db = getDB();
$user = $auth->getCurrentUser();

$success = '';
$error = '';

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_reservation'])) {
    try {
        $reservation_date = $_POST['reservation_date'];
        $reservation_time = $_POST['reservation_time'];
        $party_size = (int)$_POST['party_size'];
        $table_id = (int)$_POST['table_id'];
        $special_requests = trim($_POST['special_requests']);
        
        // Validate inputs
        if (empty($reservation_date) || empty($reservation_time) || $party_size < 1 || $table_id < 1) {
            throw new Exception('Please fill in all required fields.');
        }
        
        // Check if date is not in the past
        $reservation_datetime = $reservation_date . ' ' . $reservation_time;
        if (strtotime($reservation_datetime) <= time()) {
            throw new Exception('Reservation date and time must be in the future.');
        }
        
        // Check if table is available
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM reservations 
            WHERE table_id = ? AND reservation_date = ? AND reservation_time = ? 
            AND status NOT IN ('cancelled', 'completed')
        ");
        $stmt->execute([$table_id, $reservation_date, $reservation_time]);
        $existing = $stmt->fetch();
        
        if ($existing['count'] > 0) {
            throw new Exception('This table is already booked for the selected date and time. Please choose a different time or table.');
        }
        
        // Insert reservation
        $stmt = $db->prepare("
            INSERT INTO reservations (customer_id, table_id, reservation_date, reservation_time, party_size, special_requests, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user['id'], $table_id, $reservation_date, $reservation_time, $party_size, $special_requests]);
        
        $success = 'Reservation request submitted successfully! We will confirm your booking shortly.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get available tables
$tables = $db->query("SELECT * FROM tables WHERE is_available = 1 ORDER BY capacity, table_number")->fetchAll();

// Get today's date for minimum date selection
$today = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+60 days')); // Allow bookings up to 60 days in advance
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Make Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/home-style.css" rel="stylesheet">
    <style>
        .reservation-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding-top: 2rem;
        }
        .reservation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .table-selection {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .table-selection:hover {
            border-color: #d4af37;
            background-color: #faf6f0;
        }
        .table-selection.selected {
            border-color: #d4af37;
            background-color: #faf6f0;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
        }
        .table-icon {
            font-size: 2rem;
            color: #d4af37;
            margin-bottom: 0.5rem;
        }
        .time-slot {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .time-slot:hover {
            border-color: #d4af37;
            background-color: #faf6f0;
        }
        .time-slot.selected {
            border-color: #d4af37;
            background-color: #d4af37;
            color: white;
        }
        .reservation-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid #d4af37;
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

    <div class="reservation-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="reservation-card">
                        <h2><i class="fas fa-calendar-alt me-2 text-primary"></i>Make a Reservation</h2>
                        <p class="text-muted mb-4">Book your table for an unforgettable dining experience</p>
                        
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

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reservation_date" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Reservation Date
                                    </label>
                                    <input type="date" class="form-control" id="reservation_date" name="reservation_date" 
                                           min="<?php echo $today; ?>" max="<?php echo $max_date; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="party_size" class="form-label">
                                        <i class="fas fa-users me-2"></i>Party Size
                                    </label>
                                    <select class="form-control" id="party_size" name="party_size" required>
                                        <option value="">Select party size</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'person' : 'people'; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-clock me-2"></i>Preferred Time
                                </label>
                                <div class="row">
                                    <?php
                                    $time_slots = [
                                        '11:00', '11:30', '12:00', '12:30', '13:00', '13:30',
                                        '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00'
                                    ];
                                    foreach ($time_slots as $time): ?>
                                        <div class="col-md-2 col-sm-3 col-4">
                                            <div class="time-slot" data-time="<?php echo $time; ?>">
                                                <?php echo date('g:i A', strtotime($time)); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="reservation_time" name="reservation_time" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-chair me-2"></i>Select Table
                                </label>
                                <div class="row">
                                    <?php foreach ($tables as $table): ?>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="table-selection" data-table-id="<?php echo $table['id']; ?>">
                                                <div class="text-center">
                                                    <i class="fas fa-table table-icon"></i>
                                                    <h5>Table <?php echo htmlspecialchars($table['table_number']); ?></h5>
                                                    <p class="mb-1">
                                                        <i class="fas fa-users me-1"></i>
                                                        Seats <?php echo $table['capacity']; ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($table['location']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="table_id" name="table_id" required>
                            </div>

                            <div class="mb-4">
                                <label for="special_requests" class="form-label">
                                    <i class="fas fa-comment me-2"></i>Special Requests (Optional)
                                </label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3" 
                                          placeholder="Any special requests, dietary requirements, or celebrations?"></textarea>
                            </div>

                            <div class="text-center">
                                <button type="submit" name="make_reservation" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-calendar-check me-2"></i>Make Reservation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="reservation-card">
                        <h4><i class="fas fa-info-circle me-2 text-info"></i>Reservation Info</h4>
                        <div id="reservation-summary" class="reservation-summary" style="display: none;">
                            <h6><i class="fas fa-calendar-check me-2"></i>Reservation Summary</h6>
                            <div id="summary-content"></div>
                        </div>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-clock me-2"></i>Restaurant Hours</h6>
                            <ul class="list-unstyled">
                                <li><strong>Lunch:</strong> 11:00 AM - 3:00 PM</li>
                                <li><strong>Dinner:</strong> 6:00 PM - 10:00 PM</li>
                                <li><strong>Closed:</strong> Mondays</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Reservation Policy</h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Reservations can be made up to 60 days in advance</li>
                                <li>• Please arrive within 15 minutes of your reservation time</li>
                                <li>• Cancellations must be made at least 2 hours in advance</li>
                                <li>• Large parties (8+) may require a deposit</li>
                            </ul>
                        </div>

                        <div class="mt-4">
                            <h6><i class="fas fa-history me-2"></i>My Reservations</h6>
                            <a href="my-reservations.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i>View All Reservations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Time slot selection
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('reservation_time').value = this.getAttribute('data-time');
                updateSummary();
            });
        });

        // Table selection
        document.querySelectorAll('.table-selection').forEach(table => {
            table.addEventListener('click', function() {
                document.querySelectorAll('.table-selection').forEach(t => t.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('table_id').value = this.getAttribute('data-table-id');
                updateSummary();
            });
        });

        // Update summary when form changes
        function updateSummary() {
            const date = document.getElementById('reservation_date').value;
            const time = document.getElementById('reservation_time').value;
            const partySize = document.getElementById('party_size').value;
            const tableId = document.getElementById('table_id').value;
            
            if (date && time && partySize && tableId) {
                const selectedTable = document.querySelector(`.table-selection[data-table-id="${tableId}"]`);
                const tableNumber = selectedTable ? selectedTable.querySelector('h5').textContent : '';
                const timeFormatted = time ? new Date('1970-01-01T' + time + ':00').toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}) : '';
                
                document.getElementById('summary-content').innerHTML = `
                    <p><strong>Date:</strong> ${new Date(date).toLocaleDateString('en-US', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</p>
                    <p><strong>Time:</strong> ${timeFormatted}</p>
                    <p><strong>Party Size:</strong> ${partySize} ${partySize == 1 ? 'person' : 'people'}</p>
                    <p><strong>Table:</strong> ${tableNumber}</p>
                `;
                document.getElementById('reservation-summary').style.display = 'block';
            } else {
                document.getElementById('reservation-summary').style.display = 'none';
            }
        }

        // Listen for changes in date and party size
        document.getElementById('reservation_date').addEventListener('change', updateSummary);
        document.getElementById('party_size').addEventListener('change', updateSummary);
    </script>
</body>
</html>