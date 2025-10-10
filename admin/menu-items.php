<?php
/**
 * Menu Items Management
 * ASIF - Backend & Database Developer
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        $category_id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $preparation_time = (int)$_POST['preparation_time'];
        $calories = $_POST['calories'] ? (int)$_POST['calories'] : null;
        $allergens = trim($_POST['allergens']);
        
        if (empty($name) || $price <= 0) {
            $error = 'Item name and valid price are required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, description, price, preparation_time, calories, allergens, is_available, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)");
                if ($stmt->execute([$category_id, $name, $description, $price, $preparation_time, $calories, $allergens])) {
                    $success = 'Menu item added successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Error adding menu item: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['update_item'])) {
        $id = (int)$_POST['item_id'];
        $category_id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $preparation_time = (int)$_POST['preparation_time'];
        $calories = $_POST['calories'] ? (int)$_POST['calories'] : null;
        $allergens = trim($_POST['allergens']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        if (empty($name) || $price <= 0) {
            $error = 'Item name and valid price are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, preparation_time = ?, calories = ?, allergens = ?, is_available = ?, is_featured = ? WHERE id = ?");
                if ($stmt->execute([$category_id, $name, $description, $price, $preparation_time, $calories, $allergens, $is_available, $is_featured, $id])) {
                    $success = 'Menu item updated successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Error updating menu item: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['delete_item'])) {
        $id = (int)$_POST['item_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'Menu item deleted successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error deleting menu item: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['toggle_featured'])) {
        $id = (int)$_POST['item_id'];
        try {
            $stmt = $pdo->prepare("UPDATE menu_items SET is_featured = NOT is_featured WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'Featured status updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error updating featured status: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all categories for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Build query for menu items
$query = "SELECT mi.*, c.name as category_name FROM menu_items mi 
          LEFT JOIN categories c ON mi.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($category_filter > 0) {
    $query .= " AND mi.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $query .= " AND (mi.name LIKE ? OR mi.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY mi.is_featured DESC, c.sort_order ASC, mi.name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching menu items: ' . $e->getMessage();
    $menu_items = [];
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_item = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error fetching menu item: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'Menu Items';
    $page_icon = 'fas fa-utensils';
    $header_actions = '
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus me-2"></i>Add Menu Item
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
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="stats-card">
                            <h6 class="mb-3">Filter & Search</h6>
                            <form method="GET" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="0">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by item name or description">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search me-2"></i>Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Menu Items List -->
                <div class="data-table">
                    <div class="table-header p-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Menu Items (<?php echo count($menu_items); ?>)
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Prep Time</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($menu_items)): ?>
                                    <?php foreach ($menu_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <?php if ($item['description']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 80)) . (strlen($item['description']) > 80 ? '...' : ''); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['category_name'] ?? 'No Category'); ?></span>
                                            </td>
                                            <td>
                                                <strong>$<?php echo number_format($item['price'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $item['preparation_time']; ?> min</span>
                                            </td>
                                            <td>
                                                <?php if ($item['is_available']): ?>
                                                    <span class="status-badge status-confirmed">Available</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-cancelled">Unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="toggle_featured" class="btn btn-sm <?php echo $item['is_featured'] ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="delete_item" class="btn btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No menu items found. Add your first menu item above.
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="item_name" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="item_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="item_category" class="form-label">Category *</label>
                                    <select class="form-select" id="item_category" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="item_description" class="form-label">Description</label>
                            <textarea class="form-control" id="item_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="item_price" class="form-label">Price ($) *</label>
                                    <input type="number" class="form-control" id="item_price" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="item_prep_time" class="form-label">Prep Time (min)</label>
                                    <input type="number" class="form-control" id="item_prep_time" name="preparation_time" value="15" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="item_calories" class="form-label">Calories</label>
                                    <input type="number" class="form-control" id="item_calories" name="calories" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="item_allergens" class="form-label">Allergens</label>
                            <input type="text" class="form-control" id="item_allergens" name="allergens" placeholder="e.g., Nuts, Dairy, Gluten">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_item" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_item_id" name="item_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_item_name" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="edit_item_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_item_category" class="form-label">Category *</label>
                                    <select class="form-select" id="edit_item_category" name="category_id" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_item_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_item_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_item_price" class="form-label">Price ($) *</label>
                                    <input type="number" class="form-control" id="edit_item_price" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_item_prep_time" class="form-label">Prep Time (min)</label>
                                    <input type="number" class="form-control" id="edit_item_prep_time" name="preparation_time" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_item_calories" class="form-label">Calories</label>
                                    <input type="number" class="form-control" id="edit_item_calories" name="calories" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_item_allergens" class="form-label">Allergens</label>
                            <input type="text" class="form-control" id="edit_item_allergens" name="allergens" placeholder="e.g., Nuts, Dairy, Gluten">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_available" name="is_available">
                                    <label class="form-check-label" for="edit_is_available">Available for orders</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_featured" name="is_featured">
                                    <label class="form-check-label" for="edit_is_featured">Featured item</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_item" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editItem(item) {
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_item_name').value = item.name;
            document.getElementById('edit_item_category').value = item.category_id;
            document.getElementById('edit_item_description').value = item.description || '';
            document.getElementById('edit_item_price').value = item.price;
            document.getElementById('edit_item_prep_time').value = item.preparation_time;
            document.getElementById('edit_item_calories').value = item.calories || '';
            document.getElementById('edit_item_allergens').value = item.allergens || '';
            document.getElementById('edit_is_available').checked = item.is_available == 1;
            document.getElementById('edit_is_featured').checked = item.is_featured == 1;
            
            new bootstrap.Modal(document.getElementById('editItemModal')).show();
        }
    </script>
</body>
</html>