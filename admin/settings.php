<?php
/**
 * Admin Settings Management
 * Restaurant Management System
 */

require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

try {
    $conn = getDBConnection();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_settings'])) {
            $conn->beginTransaction();
            
            try {
                // Update each setting
                $settings_to_update = [
                    'restaurant_name',
                    'restaurant_address', 
                    'restaurant_phone',
                    'restaurant_email',
                    'tax_rate',
                    'currency',
                    'opening_hours',
                    'max_reservation_days',
                    'min_reservation_time',
                    'default_table_capacity',
                    'loyalty_points_rate',
                    'email_notifications',
                    'sms_notifications'
                ];
                
                foreach ($settings_to_update as $key) {
                    if (isset($_POST[$key])) {
                        $value = trim($_POST[$key]);
                        
                        // Special validation for specific fields
                        if ($key === 'tax_rate' && (!is_numeric($value) || $value < 0 || $value > 100)) {
                            throw new Exception("Tax rate must be a number between 0 and 100");
                        }
                        
                        if ($key === 'restaurant_email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Please enter a valid email address");
                        }
                        
                        if ($key === 'loyalty_points_rate' && (!is_numeric($value) || $value < 0)) {
                            throw new Exception("Loyalty points rate must be a positive number");
                        }
                        
                        // Update or insert setting
                        $stmt = $conn->prepare("
                            INSERT INTO settings (setting_key, setting_value) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            setting_value = VALUES(setting_value), 
                            updated_at = CURRENT_TIMESTAMP
                        ");
                        $stmt->execute([$key, $value]);
                    }
                }
                
                $conn->commit();
                $success = "Settings updated successfully!";
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    }
    
    // Fetch current settings
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Set default values if settings don't exist
    $default_settings = [
        'restaurant_name' => 'Delicious Restaurant',
        'restaurant_address' => '123 Main Street, City, State 12345',
        'restaurant_phone' => '(555) 123-4567',
        'restaurant_email' => 'info@restaurant.com',
        'tax_rate' => '8.5',
        'currency' => 'USD',
        'opening_hours' => '9:00 AM - 10:00 PM',
        'max_reservation_days' => '30',
        'min_reservation_time' => '2',
        'default_table_capacity' => '4',
        'loyalty_points_rate' => '1',
        'email_notifications' => '1',
        'sms_notifications' => '0'
    ];
    
    // Merge with database settings
    $settings = array_merge($default_settings, $settings_data);
    
    // Get system statistics
    $stats_stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE user_type = 'customer') as total_customers,
            (SELECT COUNT(*) FROM orders) as total_orders,
            (SELECT COUNT(*) FROM reservations) as total_reservations,
            (SELECT COUNT(*) FROM menu_items WHERE is_available = 1) as active_menu_items,
            (SELECT COUNT(*) FROM reviews WHERE is_verified = 1) as verified_reviews
    ");
    $system_stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'System Settings';
    $page_icon = 'fas fa-cogs';
    ?>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>

            <!-- Main Content Area -->
            <main class="main-content">
                <style>
                    .settings-card {
                        border: none;
                        border-radius: 15px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        transition: transform 0.2s ease;
                    }
                    .settings-card:hover {
                        transform: translateY(-2px);
                    }
                    .section-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 1rem;
                        border-radius: 10px;
                        margin-bottom: 1.5rem;
                    }
                    .stats-card {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border-radius: 15px;
                        padding: 1.5rem;
                        text-align: center;
                        margin-bottom: 1rem;
                    }
                    .form-label {
                        font-weight: 600;
                        color: #495057;
                    }
                    .form-control:focus {
                        border-color: #667eea;
                        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
                    }
                    .btn-save {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border: none;
                        padding: 12px 30px;
                        border-radius: 25px;
                        color: white;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    }
                    .btn-save:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                        color: white;
                    }
                </style>

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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-cogs text-primary me-2"></i>System Settings</h1>
                </div>

                <!-- System Statistics -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stats-card">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4><?php echo number_format($system_stats['total_customers'] ?? 0); ?></h4>
                            <small>Customers</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <h4><?php echo number_format($system_stats['total_orders'] ?? 0); ?></h4>
                            <small>Orders</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card">
                            <i class="fas fa-calendar fa-2x mb-2"></i>
                            <h4><?php echo number_format($system_stats['total_reservations'] ?? 0); ?></h4>
                            <small>Reservations</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card">
                            <i class="fas fa-utensils fa-2x mb-2"></i>
                            <h4><?php echo number_format($system_stats['active_menu_items'] ?? 0); ?></h4>
                            <small>Menu Items</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card">
                            <i class="fas fa-star fa-2x mb-2"></i>
                            <h4><?php echo number_format($system_stats['verified_reviews'] ?? 0); ?></h4>
                            <small>Reviews</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <h4>MySQL</h4>
                            <small>Database</small>
                        </div>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="row">
                        <!-- Restaurant Information -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header section-header">
                                    <h5 class="mb-0"><i class="fas fa-store me-2"></i>Restaurant Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="restaurant_name" class="form-label">Restaurant Name</label>
                                        <input type="text" class="form-control" id="restaurant_name" name="restaurant_name" 
                                               value="<?php echo htmlspecialchars($settings['restaurant_name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="restaurant_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="restaurant_address" name="restaurant_address" rows="3" required><?php echo htmlspecialchars($settings['restaurant_address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="restaurant_phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="restaurant_phone" name="restaurant_phone" 
                                               value="<?php echo htmlspecialchars($settings['restaurant_phone'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="restaurant_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="restaurant_email" name="restaurant_email" 
                                               value="<?php echo htmlspecialchars($settings['restaurant_email'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="opening_hours" class="form-label">Opening Hours</label>
                                        <input type="text" class="form-control" id="opening_hours" name="opening_hours" 
                                               value="<?php echo htmlspecialchars($settings['opening_hours'] ?? ''); ?>" 
                                               placeholder="e.g., 9:00 AM - 10:00 PM" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Settings -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header section-header">
                                    <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Financial Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="currency" class="form-label">Currency</label>
                                        <select class="form-control" id="currency" name="currency" required>
                                            <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                            <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                            <option value="GBP" <?php echo ($settings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                                            <option value="CAD" <?php echo ($settings['currency'] ?? '') === 'CAD' ? 'selected' : ''; ?>>CAD - Canadian Dollar</option>
                                            <option value="AUD" <?php echo ($settings['currency'] ?? '') === 'AUD' ? 'selected' : ''; ?>>AUD - Australian Dollar</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                               value="<?php echo htmlspecialchars($settings['tax_rate'] ?? ''); ?>" 
                                               min="0" max="100" step="0.01" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="loyalty_points_rate" class="form-label">Loyalty Points Rate</label>
                                        <input type="number" class="form-control" id="loyalty_points_rate" name="loyalty_points_rate" 
                                               value="<?php echo htmlspecialchars($settings['loyalty_points_rate'] ?? ''); ?>" 
                                               min="0" step="0.1" required>
                                        <div class="form-text">Points earned per dollar spent</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Reservation Settings -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header section-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Reservation Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="max_reservation_days" class="form-label">Maximum Reservation Days Ahead</label>
                                        <input type="number" class="form-control" id="max_reservation_days" name="max_reservation_days" 
                                               value="<?php echo htmlspecialchars($settings['max_reservation_days'] ?? ''); ?>" 
                                               min="1" max="365" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="min_reservation_time" class="form-label">Minimum Advance Hours</label>
                                        <input type="number" class="form-control" id="min_reservation_time" name="min_reservation_time" 
                                               value="<?php echo htmlspecialchars($settings['min_reservation_time'] ?? ''); ?>" 
                                               min="0" max="72" required>
                                        <div class="form-text">Hours before reservations must be made</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="default_table_capacity" class="form-label">Default Table Capacity</label>
                                        <input type="number" class="form-control" id="default_table_capacity" name="default_table_capacity" 
                                               value="<?php echo htmlspecialchars($settings['default_table_capacity'] ?? ''); ?>" 
                                               min="1" max="20" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header section-header">
                                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" value="1"
                                                   <?php echo ($settings['email_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_notifications">
                                                <i class="fas fa-envelope me-2"></i>Email Notifications
                                            </label>
                                        </div>
                                        <div class="form-text">Send email notifications for orders and reservations</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" value="1"
                                                   <?php echo ($settings['sms_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="sms_notifications">
                                                <i class="fas fa-sms me-2"></i>SMS Notifications
                                            </label>
                                        </div>
                                        <div class="form-text">Send SMS notifications for urgent updates</div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Note:</strong> SMS functionality requires additional setup and third-party service integration.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <button type="submit" name="update_settings" class="btn btn-save btn-lg">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </div>
                </form>

                <!-- System Information -->
                <div class="card settings-card">
                    <div class="card-header section-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server Software:</strong></td>
                                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database Host:</strong></td>
                                        <td><?php echo DB_HOST; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Database Name:</strong></td>
                                        <td><?php echo DB_NAME; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>System Time:</strong></td>
                                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Time Zone:</strong></td>
                                        <td><?php echo date_default_timezone_get(); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const saveButton = document.querySelector('.btn-save');
            
            form.addEventListener('submit', function(e) {
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                saveButton.disabled = true;
            });
            
            // Add real-time validation for tax rate
            const taxRateInput = document.getElementById('tax_rate');
            taxRateInput.addEventListener('input', function() {
                const value = parseFloat(this.value);
                if (value < 0 || value > 100) {
                    this.setCustomValidity('Tax rate must be between 0 and 100');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Add real-time validation for email
            const emailInput = document.getElementById('restaurant_email');
            emailInput.addEventListener('input', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>