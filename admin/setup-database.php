<?php
/**
 * Database Setup Script for Restaurant Management System
 * This script will create the database and import the schema
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Restaurant Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .setup-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 900px;
            margin: 0 auto;
        }
        .step-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-cogs me-2"></i>Database Setup</h1>
            <p class="text-muted">Restaurant Management System - Database Installation</p>
        </div>

        <?php
        if (isset($_POST['setup_database'])) {
            echo '<div class="step-card">';
            echo '<h3><i class="fas fa-play-circle me-2"></i>Starting Database Setup</h3>';
            
            try {
                // Step 1: Test MySQL connection
                echo '<h5>Step 1: Testing MySQL Connection</h5>';
                $pdo = new PDO("mysql:host=localhost", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo '<p class="success"><i class="fas fa-check me-2"></i>MySQL connection successful</p>';
                
                // Step 2: Create database
                echo '<h5>Step 2: Creating Database</h5>';
                $pdo->exec("CREATE DATABASE IF NOT EXISTS restaurant_management");
                echo '<p class="success"><i class="fas fa-check me-2"></i>Database "restaurant_management" created successfully</p>';
                
                // Step 3: Use the database
                echo '<h5>Step 3: Selecting Database</h5>';
                $pdo->exec("USE restaurant_management");
                echo '<p class="success"><i class="fas fa-check me-2"></i>Database selected successfully</p>';
                
                // Step 4: Read and execute SQL file
                echo '<h5>Step 4: Importing Database Schema</h5>';
                $sqlFile = __DIR__ . '/../database/restaurant_schema.sql';
                
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    
                    // Remove the CREATE DATABASE statements since we already created it
                    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
                    $sql = preg_replace('/USE.*?;/i', '', $sql);
                    
                    // Split SQL into individual statements
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    $executed = 0;
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $pdo->exec($statement);
                            $executed++;
                        }
                    }
                    
                    echo '<p class="success"><i class="fas fa-check me-2"></i>Successfully executed ' . $executed . ' SQL statements</p>';
                } else {
                    throw new Exception('SQL file not found: ' . $sqlFile);
                }
                
                // Step 5: Verify setup
                echo '<h5>Step 5: Verifying Setup</h5>';
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo '<p class="success"><i class="fas fa-check me-2"></i>Created ' . count($tables) . ' tables</p>';
                
                // Check admin user
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'");
                $stmt->execute();
                $adminCount = $stmt->fetch()['count'];
                echo '<p class="success"><i class="fas fa-check me-2"></i>Created ' . $adminCount . ' admin user(s)</p>';
                
                echo '</div>';
                
                // Success message
                echo '<div class="alert alert-success" role="alert">';
                echo '<h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Database Setup Complete!</h4>';
                echo '<p>Your Restaurant Management System database has been successfully created and configured.</p>';
                echo '<hr>';
                echo '<p class="mb-0"><strong>Next Steps:</strong></p>';
                echo '<ol>';
                echo '<li><a href="test-db-connection.php" class="alert-link">Test the database connection</a></li>';
                echo '<li><a href="login.php" class="alert-link">Login to admin panel</a></li>';
                echo '<li><a href="../index.php" class="alert-link">Visit the homepage</a></li>';
                echo '</ol>';
                echo '</div>';
                
                // Login credentials
                echo '<div class="alert alert-info" role="alert">';
                echo '<h5><i class="fas fa-key me-2"></i>Admin Login Credentials</h5>';
                echo '<ul class="mb-0">';
                echo '<li><strong>Email:</strong> admin@restaurant.com</li>';
                echo '<li><strong>Password:</strong> pass1234</li>';
                echo '</ul>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger" role="alert">';
                echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Setup Failed</h4>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<hr>';
                echo '<p class="mb-0"><strong>Troubleshooting:</strong></p>';
                echo '<ul>';
                echo '<li>Make sure Laragon is running</li>';
                echo '<li>Check if MySQL service is started</li>';
                echo '<li>Verify the database/restaurant_schema.sql file exists</li>';
                echo '</ul>';
                echo '</div>';
            }
            
        } else {
            // Show setup form
            ?>
            <div class="step-card">
                <h3><i class="fas fa-info-circle me-2"></i>Before You Begin</h3>
                <p>This script will:</p>
                <ul>
                    <li>Create the "restaurant_management" database</li>
                    <li>Import all tables and sample data</li>
                    <li>Set up the admin user account</li>
                    <li>Configure initial restaurant settings</li>
                </ul>
                
                <div class="alert alert-warning" role="alert">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Prerequisites</h5>
                    <ul class="mb-0">
                        <li>Laragon must be running (Apache and MySQL services)</li>
                        <li>MySQL should be accessible on localhost:3306</li>
                        <li>No existing "restaurant_management" database (or it will be overwritten)</li>
                    </ul>
                </div>
            </div>
            
            <div class="text-center">
                <form method="POST">
                    <button type="submit" name="setup_database" class="btn btn-primary btn-lg">
                        <i class="fas fa-play me-2"></i>Start Database Setup
                    </button>
                </form>
            </div>
            
            <div class="text-center mt-4">
                <h5>Alternative Methods</h5>
                <div class="btn-group" role="group">
                    <a href="http://restaurant-management.test/phpmyadmin" class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i>Open phpMyAdmin
                    </a>
                    <a href="test-db-connection.php" class="btn btn-outline-secondary">
                        <i class="fas fa-vial me-2"></i>Test Connection
                    </a>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>