<?php
/**
 * Staff Management
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$conn = getDBConnection();
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'add') {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $phone = $_POST['phone'];
            $employee_id = $_POST['employee_id'];
            $position = $_POST['position'];
            $salary = $_POST['salary'];
            $hire_date = $_POST['hire_date'];
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->fetch()) {
                $error = "Username or email already exists!";
            } else {
                // Check if employee_id already exists
                $stmt = $conn->prepare("SELECT id FROM staff WHERE employee_id = :employee_id");
                $stmt->execute([':employee_id' => $employee_id]);
                if ($stmt->fetch()) {
                    $error = "Employee ID already exists!";
                } else {
                    // Insert into users table (using 'admin' for user_type since enum is 'admin' or 'customer')
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password_hash, first_name, last_name, phone, user_type, created_at) 
                        VALUES (:username, :email, :password, :first_name, :last_name, :phone, 'admin', NOW())
                    ");
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password' => $password,
                        ':first_name' => $first_name,
                        ':last_name' => $last_name,
                        ':phone' => $phone
                    ]);
                    
                    $user_id = $conn->lastInsertId();
                    
                    // Insert into staff table
                    $stmt = $conn->prepare("
                        INSERT INTO staff (user_id, employee_id, position, hire_date, salary, is_active) 
                        VALUES (:user_id, :employee_id, :position, :hire_date, :salary, 1)
                    ");
                    $stmt->execute([
                        ':user_id' => $user_id,
                        ':employee_id' => $employee_id,
                        ':position' => $position,
                        ':hire_date' => $hire_date,
                        ':salary' => $salary
                    ]);
                    
                    $success = "Staff member added successfully!";
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $staff_id = $_POST['staff_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $employee_id = $_POST['employee_id'];
            $position = $_POST['position'];
            $salary = $_POST['salary'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Update users table
            $stmt = $conn->prepare("
                UPDATE users 
                SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone 
                WHERE id = :id
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':id' => $staff_id
            ]);
            
            // Update staff table
            $stmt = $conn->prepare("
                UPDATE staff 
                SET employee_id = :employee_id, position = :position, salary = :salary, is_active = :is_active 
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':employee_id' => $employee_id,
                ':position' => $position,
                ':salary' => $salary,
                ':is_active' => $is_active,
                ':user_id' => $staff_id
            ]);
            
            $success = "Staff member updated successfully!";
        } elseif ($_POST['action'] === 'delete') {
            $staff_id = $_POST['staff_id'];
            
            // Delete from users table (cascade will handle staff)
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $staff_id]);
            
            $success = "Staff member deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$position_filter = $_GET['position'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT u.*, s.employee_id, s.position, s.salary, s.hire_date, s.is_active
    FROM users u
    JOIN staff s ON u.id = s.user_id
    WHERE 1=1
";

$params = [];

if ($search) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search OR s.employee_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($position_filter) {
    $query .= " AND s.position = :position";
    $params[':position'] = $position_filter;
}

if ($status_filter !== '') {
    $query .= " AND s.is_active = :status";
    $params[':status'] = $status_filter;
}

$query .= " ORDER BY s.hire_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$staff_members = $stmt->fetchAll();

// Get statistics
$stats_stmt = $conn->query("
    SELECT 
        COUNT(*) as total_staff,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_staff,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_staff,
        AVG(salary) as avg_salary,
        SUM(CASE WHEN is_active = 1 THEN salary ELSE 0 END) as total_payroll
    FROM staff
");
$stats = $stats_stmt->fetch();

// Get position distribution
$positions_stmt = $conn->query("
    SELECT position, COUNT(*) as count 
    FROM staff 
    GROUP BY position
");
$position_distribution = $positions_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .staff-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: white;
            margin-right: 10px;
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.7rem;
            border-radius: 15px;
            font-weight: 600;
        }
        .status-active { background: #28a745; color: white; }
        .status-on_leave { background: #ffc107; color: #000; }
        .status-inactive { background: #dc3545; color: white; }
    </style>
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
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <div class="nav-section">Menu Management</div>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="menu-items.php" class="nav-link">
                        <i class="fas fa-utensils"></i>
                        <span>Menu Items</span>
                    </a>
                </li>
                
                <div class="nav-section">Orders & Sales</div>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
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
                    <a href="loyalty-points.php" class="nav-link">
                        <i class="fas fa-gift"></i>
                        <span>Loyalty Points</span>
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
                    <a href="staff.php" class="nav-link active">
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
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
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
                    <h1 class="page-title">
                        <i class="fas fa-user-tie me-2"></i>
                        Staff Management
                    </h1>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus me-2"></i>Add Staff Member
                        </button>
                    </div>
                </div>
            </header>

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
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $stats['total_staff']; ?></h3>
                            <p class="stats-label">Total Staff</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon success">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $stats['active_staff']; ?></h3>
                            <p class="stats-label">Active Staff</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon danger">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $stats['inactive_staff']; ?></h3>
                            <p class="stats-label">Inactive Staff</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon info">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h3 class="stats-number"><?php echo formatPrice($stats['total_payroll']); ?></h3>
                            <p class="stats-label">Total Payroll</p>
                            <p class="stats-change">
                                Avg: <?php echo formatPrice($stats['avg_salary']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Position Distribution -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3">
                                <h5 class="mb-3">
                                    <i class="fas fa-briefcase me-2"></i>
                                    Staff by Position
                                </h5>
                                <div class="row text-center">
                                    <?php if (!empty($position_distribution)): ?>
                                        <?php foreach ($position_distribution as $position => $count): ?>
                                            <div class="col-md-2 col-sm-4 col-6">
                                                <div class="p-2">
                                                    <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                                    <h4 class="mb-0"><?php echo $count; ?></h4>
                                                    <small><?php echo htmlspecialchars($position); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p class="text-muted">No staff members yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Old Position Distribution (keeping for reference) -->
                <div class="row mb-4" style="display:none;">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3">
                                <h5 class="mb-3">
                                    <i class="fas fa-briefcase me-2"></i>
                                    Staff by Position (old)
                                </h5>
                                <div class="row text-center">
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                            <h4 class="mb-0"><?php echo $position_distribution['Manager'] ?? 0; ?></h4>
                                            <small>Managers</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <i class="fas fa-hat-chef fa-2x text-success mb-2"></i>
                                            <h4 class="mb-0"><?php echo $position_distribution['Chef'] ?? 0; ?></h4>
                                            <small>Chefs</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <i class="fas fa-concierge-bell fa-2x text-info mb-2"></i>
                                            <h4 class="mb-0"><?php echo $position_distribution['Waiter'] ?? 0; ?></h4>
                                            <small>Waiters</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <i class="fas fa-cash-register fa-2x text-warning mb-2"></i>
                                            <h4 class="mb-0"><?php echo $position_distribution['Cashier'] ?? 0; ?></h4>
                                            <small>Cashiers</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <i class="fas fa-broom fa-2x text-secondary mb-2"></i>
                                            <h4 class="mb-0"><?php echo $role_distribution['cleaner'] ?? 0; ?></h4>
                                            <small>Cleaners</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="p-2">
                                            <i class="fas fa-user fa-2x text-dark mb-2"></i>
                                            <h4 class="mb-0"><?php echo $role_distribution['other'] ?? 0; ?></h4>
                                            <small>Other</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="data-table mb-4">
                    <div class="p-3">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search Staff</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name, email, phone or employee ID..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Position</label>
                                <select name="position" class="form-select">
                                    <option value="">All Positions</option>
                                    <?php foreach (array_keys($position_distribution) as $pos): ?>
                                        <option value="<?php echo htmlspecialchars($pos); ?>" <?php echo $position_filter === $pos ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Staff Table -->
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Staff Member</th>
                                    <th>Employee ID</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Position</th>
                                    <th>Salary</th>
                                    <th>Hire Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($staff_members)): ?>
                                    <?php foreach ($staff_members as $staff): ?>
                                        <?php
                                        $avatar_colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
                                        $color = $avatar_colors[$staff['id'] % count($avatar_colors)];
                                        $initials = strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1));
                                        ?>
                                        <tr>
                                            <td><?php echo $staff['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="staff-avatar" style="background-color: <?php echo $color; ?>">
                                                        <?php echo $initials; ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($staff['username']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($staff['employee_id']); ?></span></td>
                                            <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-primary role-badge">
                                                    <?php echo htmlspecialchars($staff['position']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatPrice($staff['salary']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($staff['hire_date'])); ?></td>
                                            <td>
                                                <?php if ($staff['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick='openEditModal(<?php echo json_encode($staff); ?>)'
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>')"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No staff members found
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

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Add New Staff Member
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID *</label>
                                <input type="text" name="employee_id" class="form-control" required placeholder="e.g., EMP001">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position *</label>
                                <input type="text" name="position" class="form-control" required placeholder="e.g., Manager, Chef, Waiter">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary *</label>
                                <input type="number" step="0.01" name="salary" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hire Date *</label>
                                <input type="date" name="hire_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="staff_id" id="edit_staff_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Edit Staff Member
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" id="edit_phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID *</label>
                                <input type="text" name="employee_id" id="edit_employee_id" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position *</label>
                                <input type="text" name="position" id="edit_position" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary *</label>
                                <input type="number" step="0.01" name="salary" id="edit_salary" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="staff_id" id="delete_staff_id">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete staff member <strong id="delete_staff_name"></strong>?</p>
                        <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openAddModal() {
            new bootstrap.Modal(document.getElementById('addModal')).show();
        }

        function openEditModal(staff) {
            document.getElementById('edit_staff_id').value = staff.id;
            document.getElementById('edit_first_name').value = staff.first_name;
            document.getElementById('edit_last_name').value = staff.last_name;
            document.getElementById('edit_email').value = staff.email;
            document.getElementById('edit_phone').value = staff.phone || '';
            document.getElementById('edit_employee_id').value = staff.employee_id;
            document.getElementById('edit_position').value = staff.position;
            document.getElementById('edit_salary').value = staff.salary;
            document.getElementById('edit_is_active').checked = staff.is_active == 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function confirmDelete(staffId, staffName) {
            document.getElementById('delete_staff_id').value = staffId;
            document.getElementById('delete_staff_name').textContent = staffName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
