<?php
/**
 * Test Admin Login Credentials
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';

// Test the admin login
$email = 'admin@restaurant.com';
$password = 'pass1234';

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, first_name, last_name FROM users WHERE email = ? AND user_type = 'admin' AND is_active = 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "Admin user found:\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Name: " . $admin['first_name'] . ' ' . $admin['last_name'] . "\n";
        
        // Test password
        if (password_verify($password, $admin['password_hash'])) {
            echo "✅ Password verification: SUCCESS\n";
        } else {
            echo "❌ Password verification: FAILED\n";
            
            // Generate new hash for pass1234
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "New password hash for 'pass1234': " . $new_hash . "\n";
            
            // Update the database with new hash
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            if ($update_stmt->execute([$new_hash, $email])) {
                echo "✅ Password hash updated successfully\n";
            } else {
                echo "❌ Failed to update password hash\n";
            }
        }
    } else {
        echo "❌ Admin user not found\n";
        
        // Create admin user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, user_type) VALUES (?, ?, ?, ?, ?, ?)");
        if ($insert_stmt->execute(['admin', $email, $password_hash, 'Admin', 'User', 'admin'])) {
            echo "✅ Admin user created successfully\n";
        } else {
            echo "❌ Failed to create admin user\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\nPlease make sure:\n";
    echo "1. XAMPP MySQL is running\n";
    echo "2. Database 'restaurant_management' exists\n";
    echo "3. Database tables are created (run restaurant_schema.sql)\n";
}
?>