<?php
/**
 * Simple Authentication Helper
 */

session_start();

// Database connection
function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=restaurant_management", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Require admin login (redirect if not logged in)
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Get admin info
function getAdminInfo() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'name' => $_SESSION['admin_name'] ?? null
    ];
}

// Logout
function logoutAdmin() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>