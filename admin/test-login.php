<?php
/**
 * Simple test for login functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load our OOP classes
require_once '../src/autoload.php';

use RestaurantMS\Core\Response;
use RestaurantMS\Core\Validator;
use RestaurantMS\Services\AuthService;

echo "<h1>Login Test</h1>";

try {
    echo "<p>✓ Autoloader loaded successfully</p>";
    echo "<p>✓ Classes imported successfully</p>";

    // Test session start
    session_start();
    echo "<p>✓ Session started successfully</p>";

    // Initialize services
    $authService = AuthService::getInstance();
    echo "<p>✓ AuthService initialized successfully</p>";

    $response = new Response();
    echo "<p>✓ Response class created successfully</p>";

    // Test if we can check login status
    $isLoggedIn = $authService->isAdminLoggedIn();
    echo "<p>✓ Login status check: " . ($isLoggedIn ? 'LOGGED IN' : 'NOT LOGGED IN') . "</p>";

    // Test login attempt
    if (isset($_POST['test_login'])) {
        echo "<h2>Testing Login...</h2>";
        $loginResult = $authService->loginAdmin('admin@restaurant.com', 'pass1234');
        echo "<p>Login result: " . ($loginResult ? 'SUCCESS' : 'FAILED') . "</p>";
        
        if ($loginResult) {
            echo "<p>✓ Login successful! Redirecting...</p>";
            echo "<script>setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);</script>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
</head>
<body>
    <form method="POST">
        <button type="submit" name="test_login" style="padding: 10px 20px; background: green; color: white; border: none;">
            Test Login
        </button>
    </form>
    
    <br><br>
    <a href="login.php">Back to Login Page</a>
</body>
</html>