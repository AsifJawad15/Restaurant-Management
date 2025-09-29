<?php
// Clear all sessions for testing
session_start();
session_destroy();
session_unset();

// Clear cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

echo "✅ All sessions cleared!<br>";
echo '<a href="login.php">Go to Login Page</a>';
?>