<?php
/**
 * Quick Fix for Admin Login Issue
 */

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Fixing Admin Login Issue</h1>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@restaurant.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>✅ Admin user exists</p>";
        
        // Check if password verification works
        if (password_verify('pass1234', $admin['password_hash'])) {
            echo "<p>✅ Password verification works</p>";
        } else {
            echo "<p>❌ Password verification failed - fixing...</p>";
            
            // Create new password hash
            $newHash = password_hash('pass1234', PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@restaurant.com'");
            $updateStmt->execute([$newHash]);
            
            echo "<p>✅ Password hash updated</p>";
        }
        
        // Make sure user is active
        $activateStmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = 'admin@restaurant.com'");
        $activateStmt->execute();
        echo "<p>✅ User activated</p>";
        
    } else {
        echo "<p>❌ Admin user not found - creating...</p>";
        
        // Create admin user
        $passwordHash = password_hash('pass1234', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute(['admin', 'admin@restaurant.com', $passwordHash, 'Admin', 'User', 'admin', 1]);
        
        echo "<p>✅ Admin user created</p>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@restaurant.com</li>";
    echo "<li>Password: pass1234</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Try Login Now</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>