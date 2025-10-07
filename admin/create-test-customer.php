<?php
/**
 * Quick Customer Creation for Testing
 */

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Quick Customer Creation</h1>";
    
    // Check if test customer exists
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = 'testcustomer@example.com'");
    $stmt->execute();
    $existingCustomer = $stmt->fetch();
    
    if ($existingCustomer) {
        echo "<p>✅ Test customer already exists:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $existingCustomer['id'] . "</li>";
        echo "<li><strong>Username:</strong> " . $existingCustomer['username'] . "</li>";
        echo "<li><strong>Email:</strong> " . $existingCustomer['email'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p>Creating test customer...</p>";
        
        // Create test customer
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, is_active) 
            VALUES (?, ?, ?, ?, ?, 'customer', 1)
        ");
        $stmt->execute(['testcustomer', 'testcustomer@example.com', $password_hash, 'Test', 'Customer']);
        
        $customer_id = $pdo->lastInsertId();
        
        // Create customer profile
        $stmt = $pdo->prepare("
            INSERT INTO customer_profiles (user_id, address, city, state, zip_code) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customer_id, '123 Test Street', 'Test City', 'Test State', '12345']);
        
        echo "<p>✅ Test customer created successfully!</p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> testcustomer</li>";
        echo "<li><strong>Email:</strong> testcustomer@example.com</li>";
        echo "<li><strong>Password:</strong> password123</li>";
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<h2>Test Login</h2>";
    echo "<p>You can now test the reservation system with these credentials:</p>";
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px;'>";
    echo "<strong>Customer Login:</strong><br>";
    echo "Email: testcustomer@example.com<br>";
    echo "Password: password123";
    echo "</div>";
    
    echo "<br>";
    echo "<a href='../customer/login.php' class='btn btn-primary' style='display:inline-block; padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Go to Customer Login</a>";
    echo " ";
    echo "<a href='debug-reservations.php' class='btn btn-secondary' style='display:inline-block; padding:10px 20px; background:#6c757d; color:white; text-decoration:none; border-radius:5px;'>Check Database</a>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
.btn { display: inline-block; padding: 10px 20px; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
.btn-primary { background: #007bff; }
.btn-secondary { background: #6c757d; }
</style>