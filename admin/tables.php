<?php
/**
 * Table Management System
 * ASIF - Backend & Database Developer
 * Manage restaurant tables with full CRUD operations
 */

require_once '../includes/config.php';
requireAdminLogin();

$conn = getDBConnection();
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $table_number = trim($_POST['table_number']);
                    $capacity = intval($_POST['capacity']);
                    $location = trim($_POST['location']);
                    $is_available = isset($_POST['is_available']) ? 1 : 0;
                    
                    // Check if table number already exists
                    $stmt = $conn->prepare("SELECT id FROM tables WHERE table_number = ?");
                    $stmt->execute([$table_number]);
                    if ($stmt->fetch()) {
                        throw new Exception("Table number already exists!");
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO tables (table_number, capacity, location, is_available) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$table_number, $capacity, $location, $is_available]);
                    
                    $success = "Table added successfully!";
                    break;
                    
                case 'edit':
                    $id = intval($_POST['table_id']);
                    $table_number = trim($_POST['table_number']);
                    $capacity = intval($_POST['capacity']);
                    $location = trim($_POST['location']);
                    $is_available = isset($_POST['is_available']) ? 1 : 0;
                    
                    // Check if table number exists for other tables
                    $stmt = $conn->prepare("SELECT id FROM tables WHERE table_number = ? AND id != ?");
                    $stmt->execute([$table_number, $id]);
                    if ($stmt->fetch()) {
                        throw new Exception("Table number already exists!");
                    }
                    
                    $stmt = $conn->prepare("UPDATE tables SET table_number = ?, capacity = ?, location = ?, is_available = ? WHERE id = ?");
                    $stmt->execute([$table_number, $capacity, $location, $is_available, $id]);
                    
                    $success = "Table updated successfully!";
                    break;
                    
                case 'delete':
                    $id = intval($_POST['table_id']);
                    
                    // Check for active reservations
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE table_id = ? AND status IN ('pending', 'confirmed', 'seated')");
                    $stmt->execute([$id]);
                    $activeReservations = $stmt->fetch()['count'];
                    
                    if ($activeReservations > 0) {
                        throw new Exception("Cannot delete table with active reservations!");
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM tables WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    $success = "Table deleted successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get statistics
try {
    // Total tables
    $stmt = $conn->query("SELECT COUNT(*) as total FROM tables");
    $total_tables = $stmt->fetch()['total'];
    
    // Available tables
    $stmt = $conn->query("SELECT COUNT(*) as total FROM tables WHERE is_available = 1");
    $available_tables = $stmt->fetch()['total'];
    
    // Occupied tables (with active reservations)
    $stmt = $conn->query("SELECT COUNT(DISTINCT table_id) as total FROM reservations WHERE status IN ('confirmed', 'seated') AND reservation_date = CURDATE()");
    $occupied_tables = $stmt->fetch()['total'];
    
    // Total capacity
    $stmt = $conn->query("SELECT SUM(capacity) as total FROM tables WHERE is_available = 1");
    $total_capacity = $stmt->fetch()['total'] ?? 0;
    
    // Location distribution
    $stmt = $conn->query("SELECT location, COUNT(*) as count, SUM(capacity) as capacity FROM tables GROUP BY location ORDER BY count DESC");
    $location_distribution = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Filtering and searching
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'table_number';

// Build query
$query = "SELECT t.*, 
          (SELECT COUNT(*) FROM reservations r WHERE r.table_id = t.id AND r.status IN ('confirmed', 'seated') AND r.reservation_date = CURDATE()) as current_reservations
          FROM tables t WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (t.table_number LIKE ? OR t.location LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($location_filter)) {
    $query .= " AND t.location = ?";
    $params[] = $location_filter;
}

if ($availability_filter !== '') {
    $query .= " AND t.is_available = ?";
    $params[] = intval($availability_filter);
}

// Sorting
switch ($sort_by) {
    case 'table_number':
        $query .= " ORDER BY CAST(SUBSTRING(t.table_number, 1) AS UNSIGNED), t.table_number";
        break;
    case 'capacity_high':
        $query .= " ORDER BY t.capacity DESC";
        break;
    case 'capacity_low':
        $query .= " ORDER BY t.capacity ASC";
        break;
    case 'location':
        $query .= " ORDER BY t.location, t.table_number";
        break;
    default:
        $query .= " ORDER BY t.id DESC";
}

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tables = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching tables: " . $e->getMessage();
    $tables = [];
}

// Get all unique locations for filter
try {
    $stmt = $conn->query("SELECT DISTINCT location FROM tables WHERE location IS NOT NULL AND location != '' ORDER BY location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations = [];
}

// Avatar colors for visual identification
$avatar_colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .table-avatar {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            color: white;
        }
        .status-available {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-unavailable {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-occupied {
            background-color: #fff3cd;
            color: #856404;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .location-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .capacity-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'Table Management';
    $page_icon = 'fas fa-chair';
    $header_actions = '
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i>Add New Table
        </button>';
    ?>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-body">
                <div class="dropdown admin-dropdown ms-3">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <div class="admin-avatar">
                                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                                </div>
                                <span><?php echo $_SESSION['admin_name']; ?></span>
                                <i class="fas fa-chevron-down ms-2"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon primary">
                                <i class="fas fa-chair"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $total_tables; ?></h3>
                            <p class="stats-label">Total Tables</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $available_tables; ?></h3>
                            <p class="stats-label">Available Tables</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon warning">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $occupied_tables; ?></h3>
                            <p class="stats-label">Occupied Today</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon info">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="stats-number"><?php echo $total_capacity; ?></h3>
                            <p class="stats-label">Total Capacity</p>
                        </div>
                    </div>
                </div>

                <!-- Location Distribution -->
                <?php if (!empty($location_distribution)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <h5 class="mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Tables by Location
                            </h5>
                            <div class="row">
                                <?php foreach ($location_distribution as $dist): ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                        <div class="p-3 border rounded">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-pin text-primary me-2"></i>
                                                <strong><?php echo htmlspecialchars($dist['location']); ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Tables:</span>
                                                <span class="badge bg-primary"><?php echo $dist['count']; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Capacity:</span>
                                                <span class="badge bg-info"><?php echo $dist['capacity']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="data-table mb-4">
                    <div class="table-header p-3">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>
                            Filter Tables
                        </h5>
                    </div>
                    <div class="p-3">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Table number or location..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Location</label>
                                <select name="location" class="form-select">
                                    <option value="">All Locations</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>" 
                                                <?php echo $location_filter === $loc ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($loc); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Availability</label>
                                <select name="availability" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $availability_filter === '1' ? 'selected' : ''; ?>>Available</option>
                                    <option value="0" <?php echo $availability_filter === '0' ? 'selected' : ''; ?>>Unavailable</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Sort By</label>
                                <select name="sort_by" class="form-select">
                                    <option value="table_number" <?php echo $sort_by === 'table_number' ? 'selected' : ''; ?>>Table Number</option>
                                    <option value="capacity_high" <?php echo $sort_by === 'capacity_high' ? 'selected' : ''; ?>>Capacity (High)</option>
                                    <option value="capacity_low" <?php echo $sort_by === 'capacity_low' ? 'selected' : ''; ?>>Capacity (Low)</option>
                                    <option value="location" <?php echo $sort_by === 'location' ? 'selected' : ''; ?>>Location</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="tables.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tables List -->
                <div class="data-table">
                    <div class="table-header p-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            All Tables (<?php echo count($tables); ?>)
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Table</th>
                                    <th>Location</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Today's Reservations</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tables)): ?>
                                    <?php foreach ($tables as $index => $table): ?>
                                        <?php 
                                            $avatar_color = $avatar_colors[$table['id'] % count($avatar_colors)];
                                            $is_occupied = $table['current_reservations'] > 0;
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $table['id']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="table-avatar me-3" style="background-color: <?php echo $avatar_color; ?>;">
                                                        <?php echo strtoupper(substr($table['table_number'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <strong>Table <?php echo htmlspecialchars($table['table_number']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="location-badge">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($table['location'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="capacity-badge">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $table['capacity']; ?> seats
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$table['is_available']): ?>
                                                    <span class="status-unavailable">
                                                        <i class="fas fa-times-circle me-1"></i>Unavailable
                                                    </span>
                                                <?php elseif ($is_occupied): ?>
                                                    <span class="status-occupied">
                                                        <i class="fas fa-utensils me-1"></i>Occupied
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-available">
                                                        <i class="fas fa-check-circle me-1"></i>Available
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($table['current_reservations'] > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <?php echo $table['current_reservations']; ?> reservation(s)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No reservations</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline-primary btn-sm me-1" 
                                                        onclick="editTable(<?php echo htmlspecialchars(json_encode($table)); ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#editModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteTable(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_number']); ?>', <?php echo $table['current_reservations']; ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No tables found
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

    <!-- Add Table Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Table
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Table Number <span class="text-danger">*</span></label>
                            <input type="text" name="table_number" class="form-control" required 
                                   placeholder="e.g., 1, A1, T-05">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Capacity (seats) <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" required min="1" max="20" 
                                   placeholder="Number of seats">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="location" class="form-select" required>
                                <option value="">Select Location</option>
                                <option value="Main Hall">Main Hall</option>
                                <option value="Patio">Patio</option>
                                <option value="Private Room">Private Room</option>
                                <option value="Bar Area">Bar Area</option>
                                <option value="Window Side">Window Side</option>
                                <option value="VIP Section">VIP Section</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" class="form-check-input" id="addAvailable" checked>
                                <label class="form-check-label" for="addAvailable">
                                    Available for reservations
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Table
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Table Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Table
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="table_id" id="edit_table_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Table Number <span class="text-danger">*</span></label>
                            <input type="text" name="table_number" id="edit_table_number" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Capacity (seats) <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control" required min="1" max="20">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="location" id="edit_location" class="form-select" required>
                                <option value="">Select Location</option>
                                <option value="Main Hall">Main Hall</option>
                                <option value="Patio">Patio</option>
                                <option value="Private Room">Private Room</option>
                                <option value="Bar Area">Bar Area</option>
                                <option value="Window Side">Window Side</option>
                                <option value="VIP Section">VIP Section</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" class="form-check-input" id="edit_is_available">
                                <label class="form-check-label" for="edit_is_available">
                                    Available for reservations
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Table
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Table Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="table_id" id="delete_table_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Warning:</strong> You are about to delete <strong id="delete_table_name"></strong>.
                        </div>
                        
                        <div id="active_reservations_warning" class="alert alert-danger" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Cannot Delete:</strong> This table has <strong id="reservations_count"></strong> active reservation(s).
                        </div>
                        
                        <p>This action cannot be undone. All reservation history for this table will be preserved.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="confirm_delete_btn">
                            <i class="fas fa-trash me-2"></i>Delete Table
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTable(table) {
            document.getElementById('edit_table_id').value = table.id;
            document.getElementById('edit_table_number').value = table.table_number;
            document.getElementById('edit_capacity').value = table.capacity;
            document.getElementById('edit_location').value = table.location || '';
            document.getElementById('edit_is_available').checked = table.is_available == 1;
        }
        
        function deleteTable(id, tableName, activeReservations) {
            document.getElementById('delete_table_id').value = id;
            document.getElementById('delete_table_name').textContent = 'Table ' + tableName;
            
            const warningDiv = document.getElementById('active_reservations_warning');
            const deleteBtn = document.getElementById('confirm_delete_btn');
            
            if (activeReservations > 0) {
                warningDiv.style.display = 'block';
                document.getElementById('reservations_count').textContent = activeReservations;
                deleteBtn.disabled = true;
            } else {
                warningDiv.style.display = 'none';
                deleteBtn.disabled = false;
            }
        }
        
        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-warning):not(.alert-danger)');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
