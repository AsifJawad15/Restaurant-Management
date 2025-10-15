<?php
/**
 * Admin Login Page - OOP Implementation
 * ASIF - Backend & Database Developer
 */

// Load our OOP classes
require_once '../src/autoload.php';

use RestaurantMS\Core\Response;
use RestaurantMS\Core\Validator;
use RestaurantMS\Services\AuthService;

session_start();

// Initialize services
$authService = AuthService::getInstance();
$response = new Response();

// Clear any existing sessions if requested
if (isset($_GET['clear'])) {
    $authService->logout();
    header('Location: login.php');
    exit();
}

// Redirect if already logged in
if ($authService->isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    $validator = new Validator([
        'email' => $email,
        'password' => $password
    ]);
    
    $validator->required(['email', 'password'])
             ->email('email');
    
    if (!$validator->isValid()) {
        $error = $validator->getFirstError();
    } else {
        try {
            $loginResult = $authService->loginAdmin($email, $password);
            
            if ($loginResult) {
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Delicious Restaurant</title>
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
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(212, 175, 55, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .restaurant-icon {
            font-size: 3.5rem;
            color: #d4af37;
            margin-bottom: 1rem;
        }
        .restaurant-name {
            color: #2c1810;
            font-weight: bold;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .login-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        .form-control {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        .login-btn {
            background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
        }
        .back-link {
            color: #d4af37;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            color: #b8941f;
        }
        .demo-note {
            background: rgba(255, 193, 7, 0.1);
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center">
            <i class="fas fa-utensils restaurant-icon"></i>
            <h2 class="restaurant-name">Delicious Restaurant</h2>
            <p class="login-subtitle">Admin Portal</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Enter your email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'admin@restaurant.com'; ?>">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required value="pass1234">
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">
                    Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 login-btn">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
            </button>
        </form>
        
        <div class="demo-note text-center">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Demo credentials are pre-filled
            </small>
        </div>
        
        <div class="text-center mt-3">
            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Back to Restaurant
            </a>
        </div>
        
        <div class="text-center mt-2">
            <a href="?clear=1" class="text-muted small">
                <i class="fas fa-refresh me-1"></i>Clear Session
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>