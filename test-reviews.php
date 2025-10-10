<?php
/**
 * Test Reviews Query
 * Check if reviews are being fetched from database
 */

require_once 'includes/config.php';

echo "<h2>Testing Reviews Query</h2>";

try {
    $db = getDBConnection();
    
    // Check if reviews table exists
    echo "<h3>Checking reviews table...</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'reviews'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p style='color: green;'>✓ Reviews table exists</p>";
        
        // Count total reviews
        $stmt = $db->query("SELECT COUNT(*) as total FROM reviews");
        $count = $stmt->fetch()['total'];
        echo "<p>Total reviews in database: <strong>{$count}</strong></p>";
        
        // Count verified reviews with comments
        $stmt = $db->query("SELECT COUNT(*) as total FROM reviews WHERE is_verified = 1 AND comment IS NOT NULL AND comment != ''");
        $verified = $stmt->fetch()['total'];
        echo "<p>Verified reviews with comments: <strong>{$verified}</strong></p>";
        
        // Try the actual query from index.php
        echo "<h3>Testing the actual query from index.php:</h3>";
        $stmt = $db->prepare("
            SELECT 
                r.*,
                CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                mi.name as item_name,
                c.name as category_name
            FROM reviews r
            JOIN users u ON r.customer_id = u.id
            JOIN menu_items mi ON r.menu_item_id = mi.id
            JOIN categories c ON mi.category_id = c.id
            WHERE r.is_verified = 1 AND r.comment IS NOT NULL AND r.comment != ''
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $reviews = $stmt->fetchAll();
        
        echo "<p>Query returned <strong>" . count($reviews) . "</strong> reviews</p>";
        
        if (!empty($reviews)) {
            echo "<h3>Sample Reviews:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                  </tr>";
            
            foreach ($reviews as $review) {
                echo "<tr>";
                echo "<td>{$review['id']}</td>";
                echo "<td>{$review['customer_name']}</td>";
                echo "<td>{$review['item_name']}</td>";
                echo "<td>{$review['rating']}/5</td>";
                echo "<td>" . substr($review['comment'], 0, 50) . "...</td>";
                echo "<td>{$review['created_at']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Output JSON for testing
            echo "<h3>JSON Data (for JavaScript):</h3>";
            echo "<pre>" . json_encode($reviews, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠ No reviews match the criteria. Let's check what's missing:</p>";
            
            // Check each condition
            $stmt = $db->query("SELECT COUNT(*) as total FROM reviews WHERE is_verified = 0");
            $unverified = $stmt->fetch()['total'];
            echo "<p>Unverified reviews: <strong>{$unverified}</strong></p>";
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM reviews WHERE comment IS NULL OR comment = ''");
            $noComment = $stmt->fetch()['total'];
            echo "<p>Reviews without comments: <strong>{$noComment}</strong></p>";
            
            // Show all reviews regardless of conditions
            echo "<h3>All Reviews (regardless of verification/comments):</h3>";
            $stmt = $db->query("
                SELECT 
                    r.*,
                    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                    mi.name as item_name
                FROM reviews r
                JOIN users u ON r.customer_id = u.id
                JOIN menu_items mi ON r.menu_item_id = mi.id
                ORDER BY r.created_at DESC
                LIMIT 10
            ");
            $allReviews = $stmt->fetchAll();
            
            if (!empty($allReviews)) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Item</th>
                        <th>Rating</th>
                        <th>Verified</th>
                        <th>Has Comment</th>
                        <th>Comment</th>
                      </tr>";
                
                foreach ($allReviews as $review) {
                    echo "<tr>";
                    echo "<td>{$review['id']}</td>";
                    echo "<td>{$review['customer_name']}</td>";
                    echo "<td>{$review['item_name']}</td>";
                    echo "<td>{$review['rating']}/5</td>";
                    echo "<td>" . ($review['is_verified'] ? '✓ Yes' : '✗ No') . "</td>";
                    echo "<td>" . (!empty($review['comment']) ? '✓ Yes' : '✗ No') . "</td>";
                    echo "<td>" . htmlspecialchars(substr($review['comment'] ?? 'No comment', 0, 50)) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p style='color: red;'>✗ No reviews found in database at all!</p>";
                echo "<p>You need to import the sample data: <code>database/clean_sample_data.sql</code></p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ Reviews table does not exist!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
?>
