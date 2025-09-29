<?php
/**
 * Admin Logout
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';

// Destroy all session data
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php?logout=1');
exit();
?>