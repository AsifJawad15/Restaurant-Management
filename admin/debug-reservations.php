<?php
/**
 * Debug Reservations - Check Database Structure and Data
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Reservations</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1><i class="fas fa-bug"></i> Reservations Debug</h1>
        
        <?php
        try {
            // Database connection
            $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<div class="alert alert-success">✅ Database connection successful</div>';
            
            // Check if reservations table exists
            echo '<h3>1. Checking Reservations Table Structure</h3>';
            $stmt = $pdo->query("SHOW TABLES LIKE 'reservations'");
            if ($stmt->rowCount() > 0) {
                echo '<p class="success">✅ Reservations table exists</p>';
                
                // Show table structure
                echo '<h5>Table Structure:</h5>';
                $stmt = $pdo->query("DESCRIBE reservations");
                $columns = $stmt->fetchAll();
                
                echo '<table class="table table-sm table-striped">';
                echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
                echo '<tbody>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($column['Field'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($column['Type'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($column['Null'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($column['Key'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($column['Default'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($column['Extra'] ?? '') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p class="error">❌ Reservations table does not exist</p>';
                
                // Create the table
                echo '<p>Creating reservations table...</p>';
                $createTableSQL = "
                CREATE TABLE reservations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    customer_id INT NOT NULL,
                    table_id INT NOT NULL,
                    reservation_date DATE NOT NULL,
                    reservation_time TIME NOT NULL,
                    party_size INT NOT NULL,
                    status ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled') DEFAULT 'pending',
                    special_requests TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
                )";
                
                $pdo->exec($createTableSQL);
                echo '<p class="success">✅ Reservations table created successfully</p>';
            }
            
            // Check if tables table exists
            echo '<h3>2. Checking Tables Table</h3>';
            $stmt = $pdo->query("SHOW TABLES LIKE 'tables'");
            if ($stmt->rowCount() > 0) {
                echo '<p class="success">✅ Tables table exists</p>';
                
                // Show available tables
                $stmt = $pdo->query("SELECT * FROM tables");
                $tables = $stmt->fetchAll();
                
                echo '<h5>Available Tables (' . count($tables) . '):</h5>';
                if (count($tables) > 0) {
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>ID</th><th>Table Number</th><th>Capacity</th><th>Location</th><th>Available</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($tables as $table) {
                        echo '<tr>';
                        echo '<td>' . $table['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($table['table_number']) . '</td>';
                        echo '<td>' . $table['capacity'] . '</td>';
                        echo '<td>' . htmlspecialchars($table['location']) . '</td>';
                        echo '<td>' . ($table['is_available'] ? 'Yes' : 'No') . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="warning">⚠️ No tables found. Creating sample tables...</p>';
                    
                    // Insert sample tables
                    $insertTablesSQL = "
                    INSERT INTO tables (table_number, capacity, location, is_available) VALUES
                    ('T01', 2, 'Main Hall', 1),
                    ('T02', 4, 'Main Hall', 1),
                    ('T03', 6, 'Main Hall', 1),
                    ('T04', 2, 'Patio', 1),
                    ('T05', 4, 'Patio', 1),
                    ('T06', 8, 'Private Room', 1)";
                    
                    $pdo->exec($insertTablesSQL);
                    echo '<p class="success">✅ Sample tables created</p>';
                }
            } else {
                echo '<p class="error">❌ Tables table does not exist</p>';
            }
            
            // Check current reservations
            echo '<h3>3. Current Reservations Data</h3>';
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations");
            $reservationCount = $stmt->fetch()['count'];
            
            echo '<p>Total reservations in database: <strong>' . $reservationCount . '</strong></p>';
            
            if ($reservationCount > 0) {
                $stmt = $pdo->query("
                    SELECT r.*, u.first_name, u.last_name, u.email, t.table_number 
                    FROM reservations r 
                    JOIN users u ON r.customer_id = u.id 
                    JOIN tables t ON r.table_id = t.id 
                    ORDER BY r.created_at DESC 
                    LIMIT 10
                ");
                $reservations = $stmt->fetchAll();
                
                echo '<h5>Recent Reservations:</h5>';
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>ID</th><th>Customer</th><th>Table</th><th>Date</th><th>Time</th><th>Party Size</th><th>Status</th><th>Created</th></tr></thead>';
                echo '<tbody>';
                foreach ($reservations as $reservation) {
                    echo '<tr>';
                    echo '<td>#' . str_pad($reservation['id'], 6, '0', STR_PAD_LEFT) . '</td>';
                    echo '<td>' . htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) . '</td>';
                    echo '<td>Table ' . htmlspecialchars($reservation['table_number']) . '</td>';
                    echo '<td>' . date('M d, Y', strtotime($reservation['reservation_date'])) . '</td>';
                    echo '<td>' . date('g:i A', strtotime($reservation['reservation_time'])) . '</td>';
                    echo '<td>' . $reservation['party_size'] . '</td>';
                    echo '<td><span class="badge bg-primary">' . ucfirst($reservation['status']) . '</span></td>';
                    echo '<td>' . date('M d, H:i', strtotime($reservation['created_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p class="warning">⚠️ No reservations found in database</p>';
            }
            
            // Check customer users
            echo '<h3>4. Customer Users</h3>';
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'");
            $customerCount = $stmt->fetch()['count'];
            
            echo '<p>Total customer accounts: <strong>' . $customerCount . '</strong></p>';
            
            if ($customerCount > 0) {
                $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, created_at FROM users WHERE user_type = 'customer' ORDER BY created_at DESC LIMIT 5");
                $customers = $stmt->fetchAll();
                
                echo '<h5>Recent Customers:</h5>';
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Registered</th></tr></thead>';
                echo '<tbody>';
                foreach ($customers as $customer) {
                    echo '<tr>';
                    echo '<td>' . $customer['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($customer['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($customer['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) . '</td>';
                    echo '<td>' . date('M d, Y', strtotime($customer['created_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p class="warning">⚠️ No customer accounts found</p>';
                echo '<p>You may need to register a customer account to test reservations.</p>';
            }
            
            // Test reservation insertion
            echo '<h3>5. Test Reservation Insertion</h3>';
            echo '<p>Testing if we can insert a sample reservation...</p>';
            
            // Check if we have a customer and table to test with
            $stmt = $pdo->query("SELECT id FROM users WHERE user_type = 'customer' LIMIT 1");
            $testCustomer = $stmt->fetch();
            
            $stmt = $pdo->query("SELECT id FROM tables WHERE is_available = 1 LIMIT 1");
            $testTable = $stmt->fetch();
            
            if ($testCustomer && $testTable) {
                try {
                    $testDate = date('Y-m-d', strtotime('+1 day'));
                    $testTime = '19:00:00';
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO reservations (customer_id, table_id, reservation_date, reservation_time, party_size, special_requests, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$testCustomer['id'], $testTable['id'], $testDate, $testTime, 4, 'Test reservation from debug script']);
                    
                    echo '<p class="success">✅ Test reservation inserted successfully</p>';
                    echo '<p>Reservation ID: ' . $pdo->lastInsertId() . '</p>';
                    
                } catch (Exception $e) {
                    echo '<p class="error">❌ Failed to insert test reservation: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p class="warning">⚠️ Cannot test insertion - missing customer or table data</p>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">❌ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div class="mt-4">
            <h3>Quick Links</h3>
            <a href="reservations.php" class="btn btn-primary me-2">Admin Reservations</a>
            <a href="../customer/reservations.php" class="btn btn-success me-2">Customer Reservations</a>
            <a href="../customer/register.php" class="btn btn-info me-2">Register Customer</a>
            <a href="test-db-connection.php" class="btn btn-secondary">Database Test</a>
        </div>
    </div>
</body>
</html>