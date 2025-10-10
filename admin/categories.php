<?php
/**
 * Menu Categories Management
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
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $sort_order = (int)$_POST['sort_order'];
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, sort_order, is_active) VALUES (?, ?, ?, 1)");
                if ($stmt->execute([$name, $description, $sort_order])) {
                    $success = 'Category added successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Error adding category: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['update_category'])) {
        $id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $sort_order = (int)$_POST['sort_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $sort_order, $is_active, $id])) {
                    $success = 'Category updated successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Error updating category: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'Category deleted successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error deleting category: ' . $e->getMessage();
        }
    }
}

// Get all categories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching categories: ' . $e->getMessage();
    $categories = [];
}

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_category = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error fetching category: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Categories - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'Menu Categories';
    $page_icon = 'fas fa-tags';
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

                <!-- Add/Edit Category Form -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="stats-card">
                            <h5 class="mb-3">
                                <i class="fas fa-plus-circle me-2"></i>
                                <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <?php if ($edit_category): ?>
                                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" 
                                           placeholder="e.g., Appetizers, Main Courses" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Brief description of this category"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                           value="<?php echo $edit_category ? $edit_category['sort_order'] : 0; ?>" 
                                           placeholder="0">
                                    <small class="text-muted">Lower numbers appear first</small>
                                </div>
                                
                                <?php if ($edit_category): ?>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                               <?php echo $edit_category['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active (visible to customers)
                                        </label>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <?php if ($edit_category): ?>
                                        <a href="categories.php" class="btn btn-outline-secondary">Cancel</a>
                                        <button type="submit" name="update_category" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Category
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="add_category" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Category
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="data-table">
                    <div class="table-header p-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            All Categories (<?php echo count($categories); ?>)
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Sort Order</th>
                                    <th>Status</th>
                                    <th>Items Count</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        // Get items count for this category
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?");
                                        $stmt->execute([$category['id']]);
                                        $items_count = $stmt->fetch()['count'];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo $category['description'] ? htmlspecialchars($category['description']) : '<em class="text-muted">No description</em>'; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $category['sort_order']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($category['is_active']): ?>
                                                    <span class="status-badge status-confirmed">Active</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-cancelled">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $items_count; ?> items</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                        <button type="submit" name="delete_category" class="btn btn-outline-danger">
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
                                            No categories found. Add your first category above.
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>