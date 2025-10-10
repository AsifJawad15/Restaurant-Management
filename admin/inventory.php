<?php
/**
 * Inventory Management System
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$conn = getDBConnection();
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $conn->prepare("
                        INSERT INTO inventory (item_name, category, current_stock, minimum_stock, unit, cost_per_unit, supplier)
                        VALUES (:item_name, :category, :current_stock, :minimum_stock, :unit, :cost_per_unit, :supplier)
                    ");
                    $stmt->execute([
                        ':item_name' => $_POST['item_name'],
                        ':category' => $_POST['category'],
                        ':current_stock' => $_POST['current_stock'],
                        ':minimum_stock' => $_POST['minimum_stock'],
                        ':unit' => $_POST['unit'],
                        ':cost_per_unit' => $_POST['cost_per_unit'],
                        ':supplier' => $_POST['supplier']
                    ]);
                    $success = "Inventory item added successfully!";
                    break;
                    
                case 'edit':
                    $stmt = $conn->prepare("
                        UPDATE inventory 
                        SET item_name = :item_name, category = :category, current_stock = :current_stock,
                            minimum_stock = :minimum_stock, unit = :unit, cost_per_unit = :cost_per_unit,
                            supplier = :supplier
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':item_name' => $_POST['item_name'],
                        ':category' => $_POST['category'],
                        ':current_stock' => $_POST['current_stock'],
                        ':minimum_stock' => $_POST['minimum_stock'],
                        ':unit' => $_POST['unit'],
                        ':cost_per_unit' => $_POST['cost_per_unit'],
                        ':supplier' => $_POST['supplier'],
                        ':id' => $_POST['id']
                    ]);
                    $success = "Inventory item updated successfully!";
                    break;
                    
                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = :id");
                    $stmt->execute([':id' => $_POST['id']]);
                    $success = "Inventory item deleted successfully!";
                    break;
                    
                case 'adjust_stock':
                    $stmt = $conn->prepare("
                        UPDATE inventory 
                        SET current_stock = current_stock + :adjustment
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':adjustment' => $_POST['adjustment'],
                        ':id' => $_POST['id']
                    ]);
                    $success = "Stock adjusted successfully!";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

// Build query
$query = "SELECT * FROM inventory WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (item_name LIKE :search OR supplier LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category_filter) {
    $query .= " AND category = :category";
    $params[':category'] = $category_filter;
}

if ($stock_status === 'low') {
    $query .= " AND current_stock <= minimum_stock";
} elseif ($stock_status === 'out') {
    $query .= " AND current_stock = 0";
}

$query .= " ORDER BY item_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$inventory_items = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $conn->query("SELECT DISTINCT category FROM inventory WHERE category IS NOT NULL ORDER BY category");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$total_items_stmt = $conn->query("SELECT COUNT(*) as total FROM inventory");
$total_items = $total_items_stmt->fetch()['total'];

$low_stock_stmt = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE current_stock <= minimum_stock");
$low_stock_count = $low_stock_stmt->fetch()['total'];

$out_of_stock_stmt = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE current_stock = 0");
$out_of_stock_count = $out_of_stock_stmt->fetch()['total'];

$total_value_stmt = $conn->query("SELECT SUM(current_stock * cost_per_unit) as total FROM inventory");
$total_value = $total_value_stmt->fetch()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .stock-critical {
            color: #dc3545;
            font-weight: bold;
        }
        .stock-low {
            color: #fd7e14;
            font-weight: bold;
        }
        .stock-good {
            color: #28a745;
        }
        .inventory-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'Inventory Management';
    $page_icon = 'fas fa-boxes';
    $header_actions = '
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus me-2"></i>Add New Item
        </button>';
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
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card inventory-card" style="border-left-color: #0d6efd;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="stats-number mb-1"><?php echo $total_items; ?></h3>
                                    <p class="stats-label mb-0">Total Items</p>
                                </div>
                                <i class="fas fa-boxes stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card inventory-card" style="border-left-color: #198754;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="stats-number mb-1"><?php echo formatPrice($total_value); ?></h3>
                                    <p class="stats-label mb-0">Total Value</p>
                                </div>
                                <i class="fas fa-dollar-sign stat-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card inventory-card" style="border-left-color: #fd7e14;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="stats-number mb-1"><?php echo $low_stock_count; ?></h3>
                                    <p class="stats-label mb-0">Low Stock Items</p>
                                </div>
                                <i class="fas fa-exclamation-triangle stat-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card inventory-card" style="border-left-color: #dc3545;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="stats-number mb-1"><?php echo $out_of_stock_count; ?></h3>
                                    <p class="stats-label mb-0">Out of Stock</p>
                                </div>
                                <i class="fas fa-times-circle stat-icon text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="data-table mb-4">
                    <div class="p-3">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by item name or supplier..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                                <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock Status</label>
                                <select name="stock_status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="low" <?php echo $stock_status === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="out" <?php echo $stock_status === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
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

                <!-- Inventory Table -->
                <div class="data-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Min. Stock</th>
                                    <th>Unit</th>
                                    <th>Cost/Unit</th>
                                    <th>Total Value</th>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_items)): ?>
                                    <?php foreach ($inventory_items as $item): ?>
                                        <?php
                                        $stock_class = '';
                                        $status_badge = '';
                                        if ($item['current_stock'] == 0) {
                                            $stock_class = 'stock-critical';
                                            $status_badge = '<span class="badge bg-danger">Out of Stock</span>';
                                        } elseif ($item['current_stock'] <= $item['minimum_stock']) {
                                            $stock_class = 'stock-low';
                                            $status_badge = '<span class="badge bg-warning">Low Stock</span>';
                                        } else {
                                            $stock_class = 'stock-good';
                                            $status_badge = '<span class="badge bg-success">In Stock</span>';
                                        }
                                        $total_value = $item['current_stock'] * $item['cost_per_unit'];
                                        ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                            <td class="<?php echo $stock_class; ?>">
                                                <?php echo $item['current_stock']; ?>
                                            </td>
                                            <td><?php echo $item['minimum_stock']; ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td><?php echo formatPrice($item['cost_per_unit']); ?></td>
                                            <td><?php echo formatPrice($total_value); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                            <td><?php echo $status_badge; ?></td>
                                            <td><?php echo formatDateTime($item['last_updated']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" 
                                                            onclick="openAdjustModal(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                                            title="Adjust Stock">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteItem(<?php echo $item['id']; ?>)"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No inventory items found
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

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>Add New Inventory Item
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" 
                                   placeholder="e.g., Vegetables, Meats, Dairy">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current Stock <span class="text-danger">*</span></label>
                                <input type="number" name="current_stock" class="form-control" 
                                       min="0" step="1" required value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimum Stock <span class="text-danger">*</span></label>
                                <input type="number" name="minimum_stock" class="form-control" 
                                       min="0" step="1" required value="10">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select" required>
                                    <option value="">Select Unit</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="g">Gram (g)</option>
                                    <option value="L">Liter (L)</option>
                                    <option value="ml">Milliliter (ml)</option>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="box">Box</option>
                                    <option value="bag">Bag</option>
                                    <option value="dozen">Dozen</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost per Unit ($)</label>
                                <input type="number" name="cost_per_unit" class="form-control" 
                                       min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier" class="form-control" 
                                   placeholder="Supplier name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Edit Inventory Item
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" id="edit_category" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current Stock <span class="text-danger">*</span></label>
                                <input type="number" name="current_stock" id="edit_current_stock" 
                                       class="form-control" min="0" step="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimum Stock <span class="text-danger">*</span></label>
                                <input type="number" name="minimum_stock" id="edit_minimum_stock" 
                                       class="form-control" min="0" step="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit" id="edit_unit" class="form-select" required>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="g">Gram (g)</option>
                                    <option value="L">Liter (L)</option>
                                    <option value="ml">Milliliter (ml)</option>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="box">Box</option>
                                    <option value="bag">Bag</option>
                                    <option value="dozen">Dozen</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost per Unit ($)</label>
                                <input type="number" name="cost_per_unit" id="edit_cost_per_unit" 
                                       class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier" id="edit_supplier" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="adjust_stock">
                    <input type="hidden" name="id" id="adjust_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exchange-alt me-2"></i>Adjust Stock
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong id="adjust_item_name"></strong><br>
                            Current Stock: <strong id="adjust_current_stock"></strong> <span id="adjust_unit"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adjustment <span class="text-danger">*</span></label>
                            <input type="number" name="adjustment" class="form-control" 
                                   step="1" required placeholder="Enter positive or negative value">
                            <small class="text-muted">
                                Use positive numbers to add stock (e.g., +50)<br>
                                Use negative numbers to reduce stock (e.g., -20)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Adjust Stock
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
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this inventory item?</p>
                        <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_item_name').value = item.item_name;
            document.getElementById('edit_category').value = item.category || '';
            document.getElementById('edit_current_stock').value = item.current_stock;
            document.getElementById('edit_minimum_stock').value = item.minimum_stock;
            document.getElementById('edit_unit').value = item.unit;
            document.getElementById('edit_cost_per_unit').value = item.cost_per_unit;
            document.getElementById('edit_supplier').value = item.supplier || '';
            
            new bootstrap.Modal(document.getElementById('editItemModal')).show();
        }

        function openAdjustModal(item) {
            document.getElementById('adjust_id').value = item.id;
            document.getElementById('adjust_item_name').textContent = item.item_name;
            document.getElementById('adjust_current_stock').textContent = item.current_stock;
            document.getElementById('adjust_unit').textContent = item.unit;
            
            new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
        }

        function deleteItem(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
