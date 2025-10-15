<?php
session_start();
require_once '../src/autoload.php';

use RestaurantMS\Services\AuthService;

$authService = AuthService::getInstance();
$message = '';

if ($_POST) {
    $message = "POST received: " . print_r($_POST, true);
    
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $result = $authService->loginAdmin($_POST['email'], $_POST['password']);
        $message .= "\nLogin result: " . ($result ? 'SUCCESS' : 'FAILED');
        
        if ($result) {
            $message .= "\nRedirecting to dashboard...";
            echo $message;
            echo '<script>setTimeout(() => { window.location.href = "dashboard.php"; }, 2000);</script>';
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        .form-group { margin: 15px 0; }
        input { padding: 10px; width: 200px; }
        button { padding: 10px 20px; background: blue; color: white; border: none; }
        .message { background: yellow; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Simple Login Test</h1>
    
    <?php if ($message): ?>
        <div class="message"><?php echo nl2br(htmlspecialchars($message)); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Email:</label><br>
            <input type="email" name="email" value="admin@restaurant.com" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label><br>
            <input type="password" name="password" value="pass1234" required>
        </div>
        
        <div class="form-group">
            <button type="submit">Login</button>
        </div>
    </form>
    
    <p><a href="login.php">Back to Styled Login</a></p>
</body>
</html>