<?php
require_once '../includes/config.php';

$db = getDBConnection();

echo "<h2>Creating Test Data for Review System</h2>";

try {
    // Check if test customer exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['testcustomer@example.com']);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        echo "<p>❌ Test customer not found. Please run create-test-customer.php first.</p>";
        exit;
    }
    
    $customer_id = $customer['id'];
    echo "<p>✅ Test customer found (ID: $customer_id)</p>";
    
    // Create some test orders
    $test_orders = [
        [
            'customer_id' => $customer_id,
            'table_id' => 1,
            'order_type' => 'dine_in',
            'status' => 'completed',
            'total_amount' => 35.97,
            'tax_amount' => 2.88,
            'final_amount' => 38.85,
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'items' => [
                ['menu_item_id' => 1, 'quantity' => 1, 'unit_price' => 8.99],
                ['menu_item_id' => 3, 'quantity' => 1, 'unit_price' => 18.99],
                ['menu_item_id' => 5, 'quantity' => 1, 'unit_price' => 6.99],
            ]
        ],
        [
            'customer_id' => $customer_id,
            'table_id' => 2,
            'order_type' => 'dine_in',
            'status' => 'completed',
            'total_amount' => 37.98,
            'tax_amount' => 3.04,
            'final_amount' => 41.02,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'items' => [
                ['menu_item_id' => 2, 'quantity' => 1, 'unit_price' => 12.99],
                ['menu_item_id' => 4, 'quantity' => 1, 'unit_price' => 24.99],
            ]
        ]
    ];
    
    foreach ($test_orders as $index => $order_data) {
        // Check if order already exists (prevent duplicates)
        $stmt = $db->prepare("SELECT id FROM orders WHERE customer_id = ? AND total_amount = ? LIMIT 1");
        $stmt->execute([$order_data['customer_id'], $order_data['total_amount']]);
        
        if ($stmt->fetch()) {
            echo "<p>⚠️ Test order " . ($index + 1) . " already exists, skipping...</p>";
            continue;
        }
        
        $db->beginTransaction();
        
        try {
            // Insert order
            $stmt = $db->prepare("
                INSERT INTO orders (customer_id, table_id, order_type, status, total_amount, tax_amount, final_amount, payment_status, payment_method, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))
            ");
            $stmt->execute([
                $order_data['customer_id'],
                $order_data['table_id'],
                $order_data['order_type'],
                $order_data['status'],
                $order_data['total_amount'],
                $order_data['tax_amount'],
                $order_data['final_amount'],
                $order_data['payment_status'],
                $order_data['payment_method'],
                rand(1, 30) // Random date within last 30 days
            ]);
            
            $order_id = $db->lastInsertId();
            
            // Insert order items
            foreach ($order_data['items'] as $item) {
                $total_price = $item['quantity'] * $item['unit_price'];
                $stmt = $db->prepare("
                    INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['menu_item_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $total_price
                ]);
            }
            
            $db->commit();
            echo "<p>✅ Created test order " . ($index + 1) . " (ID: $order_id) with " . count($order_data['items']) . " items</p>";
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "<p>❌ Failed to create test order " . ($index + 1) . ": " . $e->getMessage() . "</p>";
        }
    }
    
    // Add some sample reviews
    $sample_reviews = [
        [
            'menu_item_id' => 1,
            'rating' => 5,
            'comment' => 'Absolutely delicious Caesar salad! Fresh ingredients and perfect dressing.',
        ],
        [
            'menu_item_id' => 3,
            'rating' => 4,
            'comment' => 'Great salmon dish, cooked perfectly. Would definitely order again.',
        ],
        [
            'menu_item_id' => 2,
            'rating' => 5,
            'comment' => 'Best buffalo wings I\'ve ever had! Spicy and crispy.',
        ],
    ];
    
    // Get recent completed orders for this customer
    $stmt = $db->prepare("
        SELECT DISTINCT o.id as order_id, oi.menu_item_id 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.customer_id = ? AND o.status = 'completed'
    ");
    $stmt->execute([$customer_id]);
    $completed_orders = $stmt->fetchAll();
    
    if (empty($completed_orders)) {
        echo "<p>⚠️ No completed orders found for creating reviews</p>";
    } else {
        echo "<p>📝 Adding sample reviews...</p>";
        
        foreach ($sample_reviews as $index => $review_data) {
            // Find matching order for this menu item
            $matching_order = null;
            foreach ($completed_orders as $order) {
                if ($order['menu_item_id'] == $review_data['menu_item_id']) {
                    $matching_order = $order;
                    break;
                }
            }
            
            if (!$matching_order) {
                echo "<p>⚠️ No order found for menu item " . $review_data['menu_item_id'] . ", skipping review...</p>";
                continue;
            }
            
            // Check if review already exists
            $stmt = $db->prepare("
                SELECT id FROM reviews 
                WHERE customer_id = ? AND order_id = ? AND menu_item_id = ?
            ");
            $stmt->execute([$customer_id, $matching_order['order_id'], $review_data['menu_item_id']]);
            
            if ($stmt->fetch()) {
                echo "<p>⚠️ Review for menu item " . $review_data['menu_item_id'] . " already exists, skipping...</p>";
                continue;
            }
            
            // Insert review
            $stmt = $db->prepare("
                INSERT INTO reviews (customer_id, order_id, menu_item_id, rating, comment, is_verified, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? DAY))
            ");
            $stmt->execute([
                $customer_id,
                $matching_order['order_id'],
                $review_data['menu_item_id'],
                $review_data['rating'],
                $review_data['comment'],
                rand(1, 7) // Random date within last week
            ]);
            
            echo "<p>✅ Added review for menu item " . $review_data['menu_item_id'] . " (" . $review_data['rating'] . " stars)</p>";
        }
    }
    
    echo "<h3>✅ Test data creation completed!</h3>";
    echo "<p><strong>You can now test the review system:</strong></p>";
    echo "<ul>";
    echo "<li>🔑 Login as testcustomer@example.com / password123</li>";
    echo "<li>🏠 Visit the customer home page to see ratings on menu items</li>";
    echo "<li>⭐ Go to Reviews page to see and write reviews</li>";
    echo "<li>🔧 Check admin reviews panel for management features</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>