<?php
/**
 * UNIT TESTING
 * Database Connection Test for Laragon
 * This file tests the database connection and verifies the setup
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .test-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 800px;
            margin: 0 auto;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .test-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        .success-badge { background: #d4edda; color: #155724; }
        .error-badge { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-database me-2"></i>Database Connection Test</h1>
            <p class="text-muted">Testing Laragon database setup for Restaurant Management System</p>
        </div>

        <?php
        try {
            // Test database connection
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-plug me-2"></i>Database Connection</h3>';
            
            $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<p class="success"><i class="fas fa-check-circle me-2"></i>Database connection successful!</p>';
            echo '<span class="status-badge success-badge">✅ CONNECTED</span>';
            echo '</div>';
            
            // Test admin user
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-user-shield me-2"></i>Admin User Verification</h3>';
            
            $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, user_type FROM users WHERE user_type = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo '<p class="success"><i class="fas fa-check-circle me-2"></i>Admin user found successfully!</p>';
                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<ul class="list-group">';
                echo '<li class="list-group-item"><strong>ID:</strong> ' . htmlspecialchars($admin['id']) . '</li>';
                echo '<li class="list-group-item"><strong>Username:</strong> ' . htmlspecialchars($admin['username']) . '</li>';
                echo '<li class="list-group-item"><strong>Email:</strong> ' . htmlspecialchars($admin['email']) . '</li>';
                echo '</ul>';
                echo '</div>';
                echo '<div class="col-md-6">';
                echo '<ul class="list-group">';
                echo '<li class="list-group-item"><strong>First Name:</strong> ' . htmlspecialchars($admin['first_name']) . '</li>';
                echo '<li class="list-group-item"><strong>Last Name:</strong> ' . htmlspecialchars($admin['last_name']) . '</li>';
                echo '<li class="list-group-item"><strong>User Type:</strong> ' . htmlspecialchars($admin['user_type']) . '</li>';
                echo '</ul>';
                echo '</div>';
                echo '</div>';
                echo '<span class="status-badge success-badge">✅ VERIFIED</span>';
            } else {
                echo '<p class="error"><i class="fas fa-times-circle me-2"></i>Admin user not found!</p>';
                echo '<span class="status-badge error-badge">❌ NOT FOUND</span>';
            }
            echo '</div>';
            
            // Test password verification
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-key me-2"></i>Password Verification</h3>';
            
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = 'admin@restaurant.com'");
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify('pass1234', $user['password_hash'])) {
                echo '<p class="success"><i class="fas fa-check-circle me-2"></i>Password verification successful!</p>';
                echo '<p class="info"><strong>Login Credentials:</strong></p>';
                echo '<ul>';
                echo '<li><strong>Email:</strong> admin@restaurant.com</li>';
                echo '<li><strong>Password:</strong> pass1234</li>';
                echo '</ul>';
                echo '<span class="status-badge success-badge">✅ VERIFIED</span>';
            } else {
                echo '<p class="error"><i class="fas fa-times-circle me-2"></i>Password verification failed!</p>';
                echo '<span class="status-badge error-badge">❌ FAILED</span>';
            }
            echo '</div>';
            
            // Test database tables
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-table me-2"></i>Database Tables</h3>';
            
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo '<p class="success"><i class="fas fa-check-circle me-2"></i>Found ' . count($tables) . ' tables in database</p>';
            echo '<div class="row">';
            $half = ceil(count($tables) / 2);
            $firstHalf = array_slice($tables, 0, $half);
            $secondHalf = array_slice($tables, $half);
            
            echo '<div class="col-md-6">';
            echo '<ul class="list-group">';
            foreach ($firstHalf as $table) {
                echo '<li class="list-group-item"><i class="fas fa-table me-2"></i>' . htmlspecialchars($table) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="col-md-6">';
            echo '<ul class="list-group">';
            foreach ($secondHalf as $table) {
                echo '<li class="list-group-item"><i class="fas fa-table me-2"></i>' . htmlspecialchars($table) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '<span class="status-badge success-badge">✅ ' . count($tables) . ' TABLES</span>';
            echo '</div>';
            
            // Test sample data
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-database me-2"></i>Sample Data Verification</h3>';
            
            // Check categories
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
            $categoryCount = $stmt->fetch()['count'];
            
            // Check menu items
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items");
            $menuCount = $stmt->fetch()['count'];
            
            // Check tables
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tables");
            $tableCount = $stmt->fetch()['count'];
            
            echo '<div class="row text-center">';
            echo '<div class="col-md-4">';
            echo '<div class="card border-primary">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">Categories</h5>';
            echo '<h2 class="text-primary">' . $categoryCount . '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="card border-success">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">Menu Items</h5>';
            echo '<h2 class="text-success">' . $menuCount . '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="card border-warning">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">Tables</h5>';
            echo '<h2 class="text-warning">' . $tableCount . '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<span class="status-badge success-badge">✅ SAMPLE DATA LOADED</span>';
            echo '</div>';
            
            // Final success message
            echo '<div class="alert alert-success" role="alert">';
            echo '<h4 class="alert-heading"><i class="fas fa-thumbs-up me-2"></i>Database Setup Complete!</h4>';
            echo '<p>Your Restaurant Management System database is properly configured and ready to use.</p>';
            echo '<hr>';
            echo '<p class="mb-0">You can now proceed to test the admin login and dashboard.</p>';
            echo '</div>';
            
        } catch(PDOException $e) {
            echo '<div class="test-section">';
            echo '<h3 class="error"><i class="fas fa-exclamation-triangle me-2"></i>Database Connection Failed</h3>';
            echo '<div class="alert alert-danger" role="alert">';
            echo '<h4 class="alert-heading">Error Details:</h4>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<hr>';
            echo '<h5>🔧 Troubleshooting Steps:</h5>';
            echo '<ol>';
            echo '<li>Make sure Laragon is running (Apache and MySQL services)</li>';
            echo '<li>Check if the database "restaurant_management" exists in phpMyAdmin</li>';
            echo '<li>Verify the database schema has been imported from database/restaurant_schema.sql</li>';
            echo '<li>Ensure MySQL is running on default port 3306</li>';
            echo '<li>Check if there are any firewall restrictions</li>';
            echo '</ol>';
            echo '</div>';
            echo '<span class="status-badge error-badge">❌ CONNECTION FAILED</span>';
            echo '</div>';
        }
        ?>
        
        <div class="text-center mt-4">
            <h4>Quick Links</h4>
            <div class="btn-group" role="group">
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Landing Page
                </a>
                <a href="login.php" class="btn btn-success">
                    <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                </a>
                <a href="http://restaurant-management.test/phpmyadmin" class="btn btn-info" target="_blank">
                    <i class="fas fa-database me-2"></i>phpMyAdmin
                </a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                This test file can be safely deleted after confirming the setup works.
            </small>
        </div>
    </div>
</body>
</html>