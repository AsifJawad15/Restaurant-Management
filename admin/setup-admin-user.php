<?php
/**
 * Setup Admin User - OOP Implementation
 * Creates or updates the admin user using our new OOP classes
 */

// Load our OOP classes
require_once '../src/autoload.php';

use RestaurantMS\Models\User;
use RestaurantMS\Core\Database;
use RestaurantMS\Services\AuthService;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin User - Restaurant Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #2c1810 0%, #1a1a1a 100%);
            min-height: 100vh;
            padding: 20px;
            color: white;
        }
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }
        .restaurant-icon {
            font-size: 3rem;
            color: #d4af37;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="text-center mb-4">
            <i class="fas fa-utensils restaurant-icon"></i>
            <h1><i class="fas fa-user-shield me-2"></i>Admin User Setup</h1>
            <p class="text-muted">Setting up admin user with OOP classes</p>
        </div>
        
        <?php
        try {
            echo '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Starting admin user setup...</div>';
            
            // Test database connection
            $db = Database::getInstance();
            echo '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Database connection successful</div>';
            
            // Check if admin user already exists
            $existingAdmin = User::findByEmail('admin@restaurant.com');
            
            if ($existingAdmin) {
                echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Admin user already exists</div>';
                echo '<h4>Existing Admin Details:</h4>';
                echo '<ul class="list-group mb-3">';
                echo '<li class="list-group-item"><strong>ID:</strong> ' . $existingAdmin->id . '</li>';
                echo '<li class="list-group-item"><strong>Username:</strong> ' . htmlspecialchars($existingAdmin->username) . '</li>';
                echo '<li class="list-group-item"><strong>Email:</strong> ' . htmlspecialchars($existingAdmin->email) . '</li>';
                echo '<li class="list-group-item"><strong>Name:</strong> ' . htmlspecialchars($existingAdmin->first_name . ' ' . $existingAdmin->last_name) . '</li>';
                echo '<li class="list-group-item"><strong>User Type:</strong> ' . htmlspecialchars($existingAdmin->user_type) . '</li>';
                echo '<li class="list-group-item"><strong>Is Active:</strong> ' . ($existingAdmin->is_active ? 'Yes' : 'No') . '</li>';
                echo '</ul>';
                
                // Test password verification
                echo '<h4>Password Verification Test:</h4>';
                if ($existingAdmin->verifyPassword('pass1234')) {
                    echo '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Password "pass1234" works correctly!</div>';
                } else {
                    echo '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Password "pass1234" does not work. Updating password...</div>';
                    
                    // Update password
                    $existingAdmin->setPassword('pass1234');
                    $existingAdmin->save();
                    
                    echo '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Password updated successfully!</div>';
                }
                
                // Ensure user type is admin and is active
                if ($existingAdmin->user_type !== User::TYPE_ADMIN) {
                    $existingAdmin->user_type = User::TYPE_ADMIN;
                    $existingAdmin->save();
                    echo '<div class="alert alert-info"><i class="fas fa-info me-2"></i>User type updated to admin</div>';
                }
                
                if (!$existingAdmin->is_active) {
                    $existingAdmin->is_active = true;
                    $existingAdmin->save();
                    echo '<div class="alert alert-info"><i class="fas fa-info me-2"></i>User activated</div>';
                }
                
            } else {
                echo '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Creating new admin user...</div>';
                
                // Create new admin user
                $adminData = [
                    'username' => 'admin',
                    'email' => 'admin@restaurant.com',
                    'password' => 'pass1234',
                    'first_name' => 'Admin',
                    'last_name' => 'User',
                    'user_type' => User::TYPE_ADMIN,
                    'is_active' => true
                ];
                
                $admin = User::create($adminData);
                
                if ($admin) {
                    echo '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Admin user created successfully!</div>';
                    echo '<h4>New Admin Details:</h4>';
                    echo '<ul class="list-group mb-3">';
                    echo '<li class="list-group-item"><strong>ID:</strong> ' . $admin->id . '</li>';
                    echo '<li class="list-group-item"><strong>Username:</strong> ' . htmlspecialchars($admin->username) . '</li>';
                    echo '<li class="list-group-item"><strong>Email:</strong> ' . htmlspecialchars($admin->email) . '</li>';
                    echo '<li class="list-group-item"><strong>Name:</strong> ' . htmlspecialchars($admin->first_name . ' ' . $admin->last_name) . '</li>';
                    echo '<li class="list-group-item"><strong>User Type:</strong> ' . htmlspecialchars($admin->user_type) . '</li>';
                    echo '</ul>';
                } else {
                    echo '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Failed to create admin user</div>';
                }
            }
            
            // Final test - try to authenticate with our AuthService
            echo '<h4>Authentication Test:</h4>';
            
            $authService = AuthService::getInstance();
            
            // Start a temporary session for testing
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            echo '<div class="alert alert-info">Testing login with: admin@restaurant.com / pass1234</div>';
            
            try {
                // Test step by step
                echo '<p><strong>Step 1:</strong> Finding user by email...</p>';
                $testUser = User::findByEmail('admin@restaurant.com');
                if ($testUser) {
                    echo '<div class="alert alert-success">✅ User found: ID=' . $testUser->id . ', Type=' . $testUser->user_type . '</div>';
                    
                    echo '<p><strong>Step 2:</strong> Checking user type...</p>';
                    if ($testUser->user_type === User::TYPE_ADMIN) {
                        echo '<div class="alert alert-success">✅ User type is admin</div>';
                    } else {
                        echo '<div class="alert alert-danger">❌ User type is: ' . $testUser->user_type . ' (expected: ' . User::TYPE_ADMIN . ')</div>';
                    }
                    
                    echo '<p><strong>Step 3:</strong> Testing password verification...</p>';
                    if ($testUser->verifyPassword('pass1234')) {
                        echo '<div class="alert alert-success">✅ Password verification successful</div>';
                    } else {
                        echo '<div class="alert alert-danger">❌ Password verification failed</div>';
                    }
                    
                    echo '<p><strong>Step 4:</strong> Checking if user is active...</p>';
                    if ($testUser->isActive()) {
                        echo '<div class="alert alert-success">✅ User is active</div>';
                    } else {
                        echo '<div class="alert alert-danger">❌ User is not active</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">❌ User not found with email: admin@restaurant.com</div>';
                }
                
                echo '<p><strong>Step 5:</strong> Full authentication test...</p>';
                $loginResult = $authService->loginAdmin('admin@restaurant.com', 'pass1234');
                
                if ($loginResult) {
                    echo '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Authentication test SUCCESSFUL!</div>';
                    echo '<div class="alert alert-info"><strong>Ready to use:</strong><br>';
                    echo 'Email: admin@restaurant.com<br>';
                    echo 'Password: pass1234</div>';
                    
                    // Clear the test session
                    $authService->logout();
                } else {
                    echo '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Authentication test FAILED!</div>';
                    echo '<div class="alert alert-warning">Check the step-by-step results above to identify the issue.</div>';
                }
                
            } catch (Exception $authEx) {
                echo '<div class="alert alert-danger">Authentication Error: ' . htmlspecialchars($authEx->getMessage()) . '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div class="mt-4 text-center">
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Go to Login
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
        
        <div class="mt-3">
            <h5>Other Debug Tools:</h5>
            <a href="debug-admin.php" class="btn btn-sm btn-outline-info me-2">Debug Admin (Old)</a>
            <a href="test-db-connection.php" class="btn btn-sm btn-outline-secondary">Test DB Connection</a>
        </div>
    </div>
</body>
</html>