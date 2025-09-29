<?php
/**
 * Admin Logout - Simple & Working
 * ASIF - Backend & Database Developer
 */

session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Start a new clean session
session_start();
session_regenerate_id(true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Delicious Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c1810 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .logout-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(212, 175, 55, 0.2);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .logout-title {
            color: #2c1810;
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        .logout-message {
            color: #666;
            margin-bottom: 2rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <i class="fas fa-check-circle success-icon"></i>
        <h2 class="logout-title">Successfully Logged Out</h2>
        <p class="logout-message">You have been logged out of the admin panel. All sessions have been cleared.</p>
        
        <div class="d-grid gap-2">
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Login Again
            </a>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Restaurant
            </a>
        </div>
    </div>
    
    <script>
        // Auto redirect to login page after 3 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
</body>
</html>