<?php
/**
 * Simple Reservation Test - Debug Form Submission
 */
require_once 'bootstrap.php';

echo "<h1>Reservation System Debug</h1>";

// Test database connection
try {
    $db = getDB();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if user is logged in
$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    echo "<p style='color:orange;'>⚠️ User not logged in. Redirecting to login...</p>";
    echo "<a href='login.php'>Login as Customer</a>";
    exit;
}

$user = $auth->getCurrentUser();
echo "<p>✅ User logged in: " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " (ID: " . $user['id'] . ")</p>";

// Test tables data
$tables = $db->query("SELECT * FROM tables WHERE is_available = 1 ORDER BY capacity, table_number")->fetchAll();
echo "<p>✅ Found " . count($tables) . " available tables</p>";

if (count($tables) == 0) {
    echo "<p style='color:red;'>❌ No tables available. Creating sample tables...</p>";
    
    $insertTablesSQL = "
    INSERT INTO tables (table_number, capacity, location, is_available) VALUES
    ('T01', 2, 'Main Hall', 1),
    ('T02', 4, 'Main Hall', 1),
    ('T03', 6, 'Main Hall', 1)";
    
    $db->exec($insertTablesSQL);
    echo "<p>✅ Sample tables created</p>";
    
    // Refresh tables
    $tables = $db->query("SELECT * FROM tables WHERE is_available = 1 ORDER BY capacity, table_number")->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_reservation'])) {
    echo "<hr><h2>Processing Test Reservation</h2>";
    
    try {
        $reservation_date = date('Y-m-d', strtotime('+1 day')); // Tomorrow
        $reservation_time = '19:00:00'; // 7 PM
        $party_size = 4;
        $table_id = $tables[0]['id']; // First available table
        $special_requests = 'Test reservation from debug script';
        
        echo "<p>Attempting to insert reservation:</p>";
        echo "<ul>";
        echo "<li>Customer ID: " . $user['id'] . "</li>";
        echo "<li>Table ID: " . $table_id . "</li>";
        echo "<li>Date: " . $reservation_date . "</li>";
        echo "<li>Time: " . $reservation_time . "</li>";
        echo "<li>Party Size: " . $party_size . "</li>";
        echo "</ul>";
        
        // Insert reservation
        $stmt = $db->prepare("
            INSERT INTO reservations (customer_id, table_id, reservation_date, reservation_time, party_size, special_requests, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $result = $stmt->execute([$user['id'], $table_id, $reservation_date, $reservation_time, $party_size, $special_requests]);
        
        if ($result) {
            $reservation_id = $db->lastInsertId();
            echo "<p style='color:green;'>✅ Reservation created successfully! ID: " . $reservation_id . "</p>";
            
            // Verify the reservation was inserted
            $stmt = $db->prepare("SELECT * FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();
            
            if ($reservation) {
                echo "<p style='color:green;'>✅ Reservation verified in database</p>";
                echo "<pre>" . print_r($reservation, true) . "</pre>";
            } else {
                echo "<p style='color:red;'>❌ Reservation not found in database after insertion</p>";
            }
        } else {
            echo "<p style='color:red;'>❌ Failed to create reservation</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error creating reservation: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Check existing reservations
echo "<hr><h2>Existing Reservations</h2>";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM reservations WHERE customer_id = ?");
$stmt->execute([$user['id']]);
$count = $stmt->fetch()['count'];

echo "<p>Current user has " . $count . " reservations</p>";

if ($count > 0) {
    $stmt = $db->prepare("
        SELECT r.*, t.table_number 
        FROM reservations r 
        JOIN tables t ON r.table_id = t.id 
        WHERE r.customer_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $reservations = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Table</th><th>Date</th><th>Time</th><th>Status</th><th>Created</th></tr>";
    foreach ($reservations as $res) {
        echo "<tr>";
        echo "<td>" . $res['id'] . "</td>";
        echo "<td>Table " . $res['table_number'] . "</td>";
        echo "<td>" . $res['reservation_date'] . "</td>";
        echo "<td>" . $res['reservation_time'] . "</td>";
        echo "<td>" . $res['status'] . "</td>";
        echo "<td>" . $res['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
?>

<form method="POST">
    <button type="submit" name="test_reservation" style="padding:10px 20px; background:#28a745; color:white; border:none; border-radius:5px;">
        Create Test Reservation
    </button>
</form>

<br><br>
<a href="reservations.php" style="display:inline-block; padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;">Go to Reservations Page</a>
<a href="../admin/reservations.php" style="display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;">Admin Reservations</a>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
</style>