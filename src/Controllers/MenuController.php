<?php

namespace RestaurantMS\Controllers;

use RestaurantMS\Services\MenuService;
use RestaurantMS\Services\CategoryService;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Menu Controller - HTTP Request/Response Handler
 * 
 * Handles HTTP requests related to menu management
 */
class MenuController extends BaseAdminController
{
    private MenuService $menuService;
    private CategoryService $categoryService;
    
    public function __construct()
    {
        parent::__construct();
        $this->menuService = new MenuService();
        $this->categoryService = new CategoryService();
    }
    
    /**
     * Handle menu items listing page
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            // Get filter parameters
            $filters = $this->getFiltersFromRequest();
            
            // Get menu items data
            $menuItems = $this->menuService->getMenuItems($filters);
            
            // Get categories for filter dropdown
            $categories = $this->categoryService->getAllCategories();
            
            // Get statistics
            $stats = $this->menuService->getMenuStatistics();
            
            // Get session messages
            $messages = $this->getSessionMessages();
            
            // Render the view
            $this->renderMenuItemsView([
                'menu_items' => $menuItems,
                'categories' => $categories,
                'stats' => $stats,
                'filters' => $filters,
                'success' => $messages['success'],
                'error' => $messages['error']
            ]);
            
        } catch (DatabaseException $e) {
            $this->handleDatabaseException($e);
            $this->redirect('menu-items.php');
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            $this->redirect('menu-items.php');
        } catch (\Exception $e) {
            $this->setErrorMessage("An unexpected error occurred: " . $e->getMessage());
            $this->redirect('menu-items.php');
        }
    }
    
    /**
     * Handle menu item creation
     * 
     * @return void
     */
    public function create(): void
    {
        if (!$this->isPostRequest()) {
            $this->redirect('menu-items.php');
            return;
        }
        
        try {
            $data = $this->getMenuItemDataFromRequest();
            
            $menuItem = $this->menuService->createMenuItem($data);
            
            $this->setSuccessMessage("Menu item '{$menuItem->name}' created successfully!");
            
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (DatabaseException $e) {
            $this->handleDatabaseException($e);
        } catch (\Exception $e) {
            $this->setErrorMessage("Error creating menu item: " . $e->getMessage());
        }
        
        $this->redirect('menu-items.php');
    }
    
    /**
     * Handle menu item update
     * 
     * @return void
     */
    public function update(): void
    {
        if (!$this->isPostRequest()) {
            $this->redirect('menu-items.php');
            return;
        }
        
        try {
            $itemId = (int) $this->getPostParam('item_id');
            $data = $this->getMenuItemDataFromRequest();
            
            $menuItem = $this->menuService->updateMenuItem($itemId, $data);
            
            $this->setSuccessMessage("Menu item '{$menuItem->name}' updated successfully!");
            
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (DatabaseException $e) {
            $this->handleDatabaseException($e);
        } catch (\Exception $e) {
            $this->setErrorMessage("Error updating menu item: " . $e->getMessage());
        }
        
        $this->redirect('menu-items.php');
    }
    
    /**
     * Handle menu item deletion
     * 
     * @return void
     */
    public function delete(): void
    {
        if (!$this->isPostRequest()) {
            $this->redirect('menu-items.php');
            return;
        }
        
        try {
            $itemId = (int) $this->getPostParam('item_id');
            
            $success = $this->menuService->deleteMenuItem($itemId);
            
            if ($success) {
                $this->setSuccessMessage("Menu item deleted successfully!");
            } else {
                $this->setErrorMessage("Failed to delete menu item");
            }
            
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (DatabaseException $e) {
            $this->handleDatabaseException($e);
        } catch (\Exception $e) {
            $this->setErrorMessage("Error deleting menu item: " . $e->getMessage());
        }
        
        $this->redirect('menu-items.php');
    }
    
    /**
     * Handle toggle availability
     * 
     * @return void
     */
    public function toggleAvailability(): void
    {
        if (!$this->isPostRequest()) {
            $this->redirect('menu-items.php');
            return;
        }
        
        try {
            $itemId = (int) $this->getPostParam('item_id');
            
            $success = $this->menuService->toggleAvailability($itemId);
            
            if ($success) {
                $this->setSuccessMessage("Menu item availability updated!");
            } else {
                $this->setErrorMessage("Failed to update availability");
            }
            
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (\Exception $e) {
            $this->setErrorMessage("Error updating availability: " . $e->getMessage());
        }
        
        $this->redirect('menu-items.php');
    }
    
    /**
     * Handle toggle featured status
     * 
     * @return void
     */
    public function toggleFeatured(): void
    {
        if (!$this->isPostRequest()) {
            $this->redirect('menu-items.php');
            return;
        }
        
        try {
            $itemId = (int) $this->getPostParam('item_id');
            
            $success = $this->menuService->toggleFeatured($itemId);
            
            if ($success) {
                $this->setSuccessMessage("Menu item featured status updated!");
            } else {
                $this->setErrorMessage("Failed to update featured status");
            }
            
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (\Exception $e) {
            $this->setErrorMessage("Error updating featured status: " . $e->getMessage());
        }
        
        $this->redirect('menu-items.php');
    }
    
    /**
     * Handle AJAX requests
     * 
     * @return void
     */
    public function ajax(): void
    {
        try {
            $action = $this->getGetParam('action');
            
            switch ($action) {
                case 'search':
                    $this->handleAjaxSearch();
                    break;
                case 'stats':
                    $this->handleAjaxStats();
                    break;
                case 'popular':
                    $this->handleAjaxPopular();
                    break;
                default:
                    $this->sendJsonResponse(['error' => 'Invalid action'], 400);
            }
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle AJAX search request
     * 
     * @return void
     */
    private function handleAjaxSearch(): void
    {
        $searchTerm = $this->getGetParam('q', '');
        
        if (empty($searchTerm)) {
            $this->sendJsonResponse([]);
            return;
        }
        
        $menuItems = $this->menuService->searchMenuItems($searchTerm);
        $this->sendJsonResponse($menuItems);
    }
    
    /**
     * Handle AJAX stats request
     * 
     * @return void
     */
    private function handleAjaxStats(): void
    {
        $stats = $this->menuService->getMenuStatistics();
        $this->sendJsonResponse($stats);
    }
    
    /**
     * Handle AJAX popular items request
     * 
     * @return void
     */
    private function handleAjaxPopular(): void
    {
        $limit = (int) $this->getGetParam('limit', 10);
        $popularItems = $this->menuService->getPopularMenuItems($limit);
        $this->sendJsonResponse($popularItems);
    }
    
    /**
     * Get filters from request parameters
     * 
     * @return array
     */
    private function getFiltersFromRequest(): array
    {
        return [
            'category_id' => $this->getGetParam('category_id'),
            'search' => $this->getGetParam('search'),
            'is_available' => $this->getGetParam('is_available'),
            'is_featured' => $this->getGetParam('is_featured'),
            'sort_by' => $this->getGetParam('sort_by', 'name')
        ];
    }
    
    /**
     * Get menu item data from request
     * 
     * @return array
     */
    private function getMenuItemDataFromRequest(): array
    {
        return [
            'category_id' => (int) $this->getPostParam('category_id'),
            'name' => $this->sanitizeInput($this->getPostParam('name')),
            'description' => $this->sanitizeInput($this->getPostParam('description')),
            'price' => (float) $this->getPostParam('price'),
            'image_url' => $this->sanitizeInput($this->getPostParam('image_url')),
            'is_available' => $this->getPostParam('is_available') ? 1 : 0,
            'is_featured' => $this->getPostParam('is_featured') ? 1 : 0,
            'preparation_time' => (int) $this->getPostParam('preparation_time', 15),
            'calories' => $this->getPostParam('calories') ? (int) $this->getPostParam('calories') : null,
            'allergens' => $this->sanitizeInput($this->getPostParam('allergens'))
        ];
    }
    
    /**
     * Render menu items view
     * 
     * @param array $data
     * @return void
     */
    private function renderMenuItemsView(array $data): void
    {
        extract($data);
        
        // Set page-specific variables
        $page_title = 'Menu Items Management';
        $page_icon = 'fas fa-utensils';
        
        $this->renderHeader($page_title, $page_icon);
        $this->renderAlerts($success, $error);
        $this->renderStatisticsCards($stats);
        $this->renderFilters($filters, $categories);
        $this->renderMenuItemsTable($menu_items);
        $this->renderModals();
        $this->renderFooter();
    }
    
    /**
     * Render page header
     * 
     * @param string $page_title
     * @param string $page_icon
     * @return void
     */
    private function renderHeader(string $page_title, string $page_icon): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $page_title; ?> - Admin Panel</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
            <link href="../assets/css/admin.css" rel="stylesheet">
        </head>
        <body>
            <div class="admin-wrapper">
                <?php include 'includes/sidebar.php'; ?>
                <div class="admin-content">
                    <?php include 'includes/header.php'; ?>
                    <main class="main-content">
        <?php
    }
    
    /**
     * Render alerts
     * 
     * @param string $success
     * @param string $error
     * @return void
     */
    private function renderAlerts(string $success, string $error): void
    {
        if ($success): ?>
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
        <?php endif;
    }
    
    /**
     * Render statistics cards
     * 
     * @param array $stats
     * @return void
     */
    private function renderStatisticsCards(array $stats): void
    {
        ?>
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card" style="border-left-color: #0d6efd;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['total_items'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">Total Items</p>
                        </div>
                        <i class="fas fa-utensils fa-2x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card" style="border-left-color: #198754;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['available_items'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">Available</p>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card" style="border-left-color: #ffc107;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['featured_items'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">Featured</p>
                        </div>
                        <i class="fas fa-star fa-2x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card" style="border-left-color: #dc3545;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $this->formatPrice($stats['avg_price'] ?? 0); ?></h3>
                            <p class="stats-label mb-0">Avg Price</p>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-danger opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render filters form
     * 
     * @param array $filters
     * @param array $categories
     * @return void
     */
    private function renderFilters(array $filters, array $categories): void
    {
        ?>
        <div class="data-table mb-4">
            <div class="p-3">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search menu items..." 
                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="is_available" class="form-select">
                            <option value="">All</option>
                            <option value="1" <?php echo ($filters['is_available'] ?? '') === '1' ? 'selected' : ''; ?>>Available</option>
                            <option value="0" <?php echo ($filters['is_available'] ?? '') === '0' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select name="sort_by" class="form-select">
                            <option value="name" <?php echo ($filters['sort_by'] ?? '') === 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="price_asc" <?php echo ($filters['sort_by'] ?? '') === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?php echo ($filters['sort_by'] ?? '') === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="rating" <?php echo ($filters['sort_by'] ?? '') === 'rating' ? 'selected' : ''; ?>>Rating</option>
                            <option value="popularity" <?php echo ($filters['sort_by'] ?? '') === 'popularity' ? 'selected' : ''; ?>>Popularity</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render menu items table
     * 
     * @param array $menuItems
     * @return void
     */
    private function renderMenuItemsTable(array $menuItems): void
    {
        ?>
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($menuItems)): ?>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td><?php echo $item['id']; ?></td>
                                    <td>
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="img-thumbnail" style="width: 50px; height: 50px;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <?php if ($item['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo $this->formatPrice($item['price']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-warning text-dark me-1">
                                                <?php echo number_format($item['avg_rating'], 1); ?>
                                            </span>
                                            <small class="text-muted">(<?php echo $item['review_count']; ?>)</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['is_available'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['is_featured']): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            <i class="fas fa-star text-muted"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                                    title="Edit Item">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-<?php echo $item['is_available'] ? 'warning' : 'success'; ?>" 
                                                    onclick="toggleAvailability(<?php echo $item['id']; ?>)"
                                                    title="<?php echo $item['is_available'] ? 'Make Unavailable' : 'Make Available'; ?>">
                                                <i class="fas fa-<?php echo $item['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                            <button class="btn btn-outline-<?php echo $item['is_featured'] ? 'secondary' : 'warning'; ?>" 
                                                    onclick="toggleFeatured(<?php echo $item['id']; ?>)"
                                                    title="<?php echo $item['is_featured'] ? 'Remove from Featured' : 'Add to Featured'; ?>">
                                                <i class="fas fa-star"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')"
                                                    title="Delete Item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No menu items found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render modals
     * 
     * @return void
     */
    private function renderModals(): void
    {
        // Add Item Modal
        ?>
        <div class="modal fade" id="addItemModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="?action=create">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Menu Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category *</label>
                                        <select name="category_id" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($this->categoryService->getAllCategories() as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Price *</label>
                                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Preparation Time (min)</label>
                                        <input type="number" name="preparation_time" class="form-control" min="0" value="15">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Calories</label>
                                        <input type="number" name="calories" class="form-control" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image URL</label>
                                <input type="url" name="image_url" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allergens</label>
                                <input type="text" name="allergens" class="form-control" placeholder="e.g., nuts, dairy, gluten">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_available" class="form-check-input" checked>
                                        <label class="form-check-label">Available</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_featured" class="form-check-input">
                                        <label class="form-check-label">Featured</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Menu Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Item Modal -->
        <div class="modal fade" id="editItemModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="?action=update">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Menu Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Same form fields as add modal -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Menu Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="?action=delete">
                        <input type="hidden" name="item_id" id="delete_item_id">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete menu item <strong id="delete_item_name"></strong>?</p>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This action cannot be undone!
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render page footer
     * 
     * @return void
     */
    private function renderFooter(): void
    {
        ?>
                    </main>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function editItem(item) {
                    document.getElementById('edit_item_id').value = item.id;
                    // Populate form fields with item data
                    // Implementation details...
                    new bootstrap.Modal(document.getElementById('editItemModal')).show();
                }
                
                function toggleAvailability(itemId) {
                    if (confirm('Are you sure you want to change availability?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '?action=toggle-availability';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'item_id';
                        input.value = itemId;
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
                
                function toggleFeatured(itemId) {
                    if (confirm('Are you sure you want to change featured status?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '?action=toggle-featured';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'item_id';
                        input.value = itemId;
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
                
                function deleteItem(itemId, itemName) {
                    document.getElementById('delete_item_id').value = itemId;
                    document.getElementById('delete_item_name').textContent = itemName;
                    new bootstrap.Modal(document.getElementById('deleteModal')).show();
                }
            </script>
        </body>
        </html>
        <?php
    }
}
