<?php

namespace RestaurantMS\Controllers;

use RestaurantMS\Models\Customer;

/**
 * Customer View - Presentation Layer
 * 
 * Handles rendering of customer-related views and templates
 * Separates presentation logic from business logic
 */
class CustomerView
{
    /**
     * Render customer index page
     * 
     * @param array $data
     * @return void
     */
    public function renderIndex(array $data): void
    {
        extract($data);
        
        // Set page-specific variables
        $page_title = 'Customers Management';
        $page_icon = 'fas fa-users';
        
        $this->renderHeader($page_title, $page_icon);
        $this->renderAlerts($success, $error);
        $this->renderStatisticsCards($stats);
        $this->renderLoyaltyTierDistribution($loyalty_tiers);
        $this->renderFilters($filters, $sort_by);
        $this->renderCustomersTable($customers);
        $this->renderDeleteModal();
        $this->renderFooter();
    }
    
    /**
     * Render customer details page
     * 
     * @param array $data
     * @return void
     */
    public function renderDetails(array $data): void
    {
        extract($data);
        
        $page_title = 'Customer Details';
        $page_icon = 'fas fa-user';
        
        $this->renderHeader($page_title, $page_icon);
        $this->renderCustomerDetails($customer, $activity_summary);
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
            <style>
                .customer-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 16px;
                    color: white;
                    margin-right: 10px;
                }
                .loyalty-badge {
                    font-size: 0.75rem;
                    padding: 0.25rem 0.5rem;
                    border-radius: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                .loyalty-bronze { background: linear-gradient(135deg, #cd7f32, #b87333); color: white; }
                .loyalty-silver { background: linear-gradient(135deg, #c0c0c0, #a8a8a8); color: white; }
                .loyalty-gold { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #333; }
                .loyalty-platinum { background: linear-gradient(135deg, #e5e4e2, #8b8b8b); color: white; }
                
                .stat-card {
                    border-left: 4px solid;
                    transition: transform 0.2s;
                }
                .stat-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
            </style>
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
                <div class="stats-card stat-card" style="border-left-color: #0d6efd;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['total_customers'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">Total Customers</p>
                        </div>
                        <i class="fas fa-users fa-2x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stat-card" style="border-left-color: #198754;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['new_today'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">New Today</p>
                        </div>
                        <i class="fas fa-user-plus fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stat-card" style="border-left-color: #ffc107;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['new_this_week'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">New This Week</p>
                        </div>
                        <i class="fas fa-calendar-week fa-2x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card stat-card" style="border-left-color: #dc3545;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stats-number mb-1"><?php echo $stats['new_this_month'] ?? 0; ?></h3>
                            <p class="stats-label mb-0">New This Month</p>
                        </div>
                        <i class="fas fa-calendar-alt fa-2x text-danger opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render loyalty tier distribution
     * 
     * @param array $tiers
     * @return void
     */
    private function renderLoyaltyTierDistribution(array $tiers): void
    {
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="data-table">
                    <div class="p-3">
                        <h5 class="mb-3">
                            <i class="fas fa-trophy me-2"></i>
                            Loyalty Tier Distribution
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <span class="loyalty-badge loyalty-bronze">Bronze</span>
                                    <h4 class="mt-2 mb-0"><?php echo $tiers['bronze'] ?? 0; ?></h4>
                                    <small class="text-muted">Customers</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <span class="loyalty-badge loyalty-silver">Silver</span>
                                    <h4 class="mt-2 mb-0"><?php echo $tiers['silver'] ?? 0; ?></h4>
                                    <small class="text-muted">Customers</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <span class="loyalty-badge loyalty-gold">Gold</span>
                                    <h4 class="mt-2 mb-0"><?php echo $tiers['gold'] ?? 0; ?></h4>
                                    <small class="text-muted">Customers</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <span class="loyalty-badge loyalty-platinum">Platinum</span>
                                    <h4 class="mt-2 mb-0"><?php echo $tiers['platinum'] ?? 0; ?></h4>
                                    <small class="text-muted">Customers</small>
                                </div>
                            </div>
                        </div>
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
     * @param string $sortBy
     * @return void
     */
    private function renderFilters(array $filters, string $sortBy): void
    {
        ?>
        <div class="data-table mb-4">
            <div class="p-3">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search Customer</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name, email or phone..." 
                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loyalty Tier</label>
                        <select name="loyalty_tier" class="form-select">
                            <option value="">All Tiers</option>
                            <option value="bronze" <?php echo ($filters['loyalty_tier'] ?? '') === 'bronze' ? 'selected' : ''; ?>>Bronze</option>
                            <option value="silver" <?php echo ($filters['loyalty_tier'] ?? '') === 'silver' ? 'selected' : ''; ?>>Silver</option>
                            <option value="gold" <?php echo ($filters['loyalty_tier'] ?? '') === 'gold' ? 'selected' : ''; ?>>Gold</option>
                            <option value="platinum" <?php echo ($filters['loyalty_tier'] ?? '') === 'platinum' ? 'selected' : ''; ?>>Platinum</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort By</label>
                        <select name="sort_by" class="form-select">
                            <option value="recent" <?php echo $sortBy === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="orders" <?php echo $sortBy === 'orders' ? 'selected' : ''; ?>>Most Orders</option>
                            <option value="spending" <?php echo $sortBy === 'spending' ? 'selected' : ''; ?>>Highest Spending</option>
                            <option value="points" <?php echo $sortBy === 'points' ? 'selected' : ''; ?>>Most Points</option>
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
        <?php
    }
    
    /**
     * Render customers table
     * 
     * @param array $customers
     * @return void
     */
    private function renderCustomersTable(array $customers): void
    {
        ?>
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Loyalty Tier</th>
                            <th>Points</th>
                            <th>Total Spent</th>
                            <th>Orders</th>
                            <th>Reviews</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customers)): ?>
                            <?php foreach ($customers as $customer): ?>
                                <?php
                                $avatar_colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
                                $color = $avatar_colors[$customer['id'] % count($avatar_colors)];
                                $initials = strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1));
                                $tier = $customer['loyalty_tier'] ?? 'bronze';
                                ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar" style="background-color: <?php echo $color; ?>">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">@<?php echo htmlspecialchars($customer['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($customer['city']): ?>
                                            <?php echo htmlspecialchars($customer['city'] . ', ' . $customer['state']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="loyalty-badge loyalty-<?php echo $tier; ?>">
                                            <?php echo ucfirst($tier); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo number_format($customer['loyalty_points'] ?? 0); ?> pts
                                        </span>
                                    </td>
                                    <td><?php echo $this->formatPrice($customer['total_spent'] ?? 0); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $customer['total_orders']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $customer['total_reviews']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="customer-details.php?id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>')"
                                                    title="Delete Customer">
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
                                    No customers found
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
     * Render delete confirmation modal
     * 
     * @return void
     */
    private function renderDeleteModal(): void
    {
        ?>
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="customer_id" id="delete_customer_id">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete customer <strong id="delete_customer_name"></strong>?</p>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This will also delete:
                                <ul class="mb-0 mt-2">
                                    <li>Customer profile</li>
                                    <li>All orders</li>
                                    <li>All reviews</li>
                                    <li>All reservations</li>
                                </ul>
                            </div>
                            <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render customer details
     * 
     * @param Customer $customer
     * @param array $activitySummary
     * @return void
     */
    private function renderCustomerDetails(Customer $customer, array $activitySummary): void
    {
        // Implementation for customer details view
        // This would render detailed customer information
        ?>
        <div class="row">
            <div class="col-12">
                <div class="data-table">
                    <div class="p-3">
                        <h3>Customer Details</h3>
                        <p>Customer ID: <?php echo $customer->id; ?></p>
                        <p>Name: <?php echo $customer->getFullName(); ?></p>
                        <p>Email: <?php echo $customer->getEmail(); ?></p>
                        <!-- Add more customer details here -->
                    </div>
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
                function confirmDelete(customerId, customerName) {
                    document.getElementById('delete_customer_id').value = customerId;
                    document.getElementById('delete_customer_name').textContent = customerName;
                    new bootstrap.Modal(document.getElementById('deleteModal')).show();
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Format price for display
     * 
     * @param float $amount
     * @return string
     */
    private function formatPrice(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
