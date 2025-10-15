<?php
// Simple database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../src/autoload.php';

use RestaurantMS\Core\Database;
use RestaurantMS\Models\Order;

try {
    echo "Testing database connection...<br>";
    
    // Test direct database connection
    $db = Database::getInstance();
    echo "Database instance created successfully<br>";
    
    // Test simple query
    $result = $db->fetchAll("SELECT COUNT(*) as count FROM orders");
    echo "Orders count query successful: " . $result[0]['count'] . "<br>";
    
    // Test Order model
    $orderModel = new Order();
    echo "Order model created successfully<br>";
    
    // Test our custom method
    $todaysOrders = $orderModel->getTodaysOrderCount();
    echo "Today's orders: $todaysOrders<br>";
    
    echo "All tests passed!<br>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "TRACE: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>