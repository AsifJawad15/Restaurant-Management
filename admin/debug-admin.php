<?php
/**
 * Debug Admin User - Check what's in the database
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Admin User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .debug-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1><i class="fas fa-bug"></i> Admin User Debug</h1>
        
        <?php
        try {
            // Database connection
            $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<div class="alert alert-success">✅ Database connection successful</div>';
            
            // Check if users table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="alert alert-success">✅ Users table exists</div>';
            } else {
                echo '<div class="alert alert-danger">❌ Users table does not exist</div>';
                exit;
            }
            
            // Get all users
            echo '<h3>All Users in Database:</h3>';
            $stmt = $pdo->query("SELECT id, username, email, user_type, is_active, created_at FROM users");
            $users = $stmt->fetchAll();
            
            if (count($users) > 0) {
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Type</th><th>Active</th><th>Created</th></tr></thead>';
                echo '<tbody>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['user_type']) . '</td>';
                    echo '<td>' . ($user['is_active'] ? 'Yes' : 'No') . '</td>';
                    echo '<td>' . $user['created_at'] . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-warning">⚠️ No users found in database</div>';
            }
            
            // Check specific admin user
            echo '<h3>Admin User Check:</h3>';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'admin'");
            $stmt->execute(['admin@restaurant.com']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo '<div class="alert alert-success">✅ Admin user found with email: admin@restaurant.com</div>';
                echo '<h4>Admin User Details:</h4>';
                echo '<ul>';
                echo '<li><strong>ID:</strong> ' . $admin['id'] . '</li>';
                echo '<li><strong>Username:</strong> ' . htmlspecialchars($admin['username']) . '</li>';
                echo '<li><strong>Email:</strong> ' . htmlspecialchars($admin['email']) . '</li>';
                echo '<li><strong>First Name:</strong> ' . htmlspecialchars($admin['first_name']) . '</li>';
                echo '<li><strong>Last Name:</strong> ' . htmlspecialchars($admin['last_name']) . '</li>';
                echo '<li><strong>User Type:</strong> ' . htmlspecialchars($admin['user_type']) . '</li>';
                echo '<li><strong>Is Active:</strong> ' . ($admin['is_active'] ? 'Yes' : 'No') . '</li>';
                echo '<li><strong>Password Hash:</strong> ' . substr($admin['password_hash'], 0, 50) . '...</li>';
                echo '</ul>';
                
                // Test password verification
                echo '<h4>Password Verification Test:</h4>';
                $testPassword = 'pass1234';
                if (password_verify($testPassword, $admin['password_hash'])) {
                    echo '<div class="alert alert-success">✅ Password "pass1234" verification: SUCCESS</div>';
                } else {
                    echo '<div class="alert alert-danger">❌ Password "pass1234" verification: FAILED</div>';
                    
                    // Try to create a new hash and see if it works
                    echo '<h5>Creating New Password Hash:</h5>';
                    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                    echo '<p>New hash: ' . $newHash . '</p>';
                    
                    if (password_verify($testPassword, $newHash)) {
                        echo '<div class="alert alert-info">✅ New hash verification works - Database hash might be corrupted</div>';
                        
                        // Update the password hash
                        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ? AND user_type = 'admin'");
                        if ($updateStmt->execute([$newHash, 'admin@restaurant.com'])) {
                            echo '<div class="alert alert-success">✅ Password hash updated successfully!</div>';
                            echo '<div class="alert alert-info">Try logging in again with: admin@restaurant.com / pass1234</div>';
                        }
                    }
                }
                
            } else {
                echo '<div class="alert alert-danger">❌ No admin user found with email: admin@restaurant.com</div>';
                
                // Let's try to create the admin user
                echo '<h4>Creating Admin User:</h4>';
                $passwordHash = password_hash('pass1234', PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($insertStmt->execute(['admin', 'admin@restaurant.com', $passwordHash, 'Admin', 'User', 'admin', 1])) {
                    echo '<div class="alert alert-success">✅ Admin user created successfully!</div>';
                    echo '<div class="alert alert-info">Login credentials:<br>Email: admin@restaurant.com<br>Password: pass1234</div>';
                } else {
                    echo '<div class="alert alert-danger">❌ Failed to create admin user</div>';
                }
            }
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">❌ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary">Try Login Again</a>
            <a href="test-db-connection.php" class="btn btn-secondary">Database Test</a>
        </div>
    </div>
</body>
</html>