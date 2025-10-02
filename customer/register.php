<?php
require_once 'bootstrap.php';

$auth = new Auth();

// Redirect if already logged in as customer
if ($auth->isSessionValid() && $auth->isCustomer()) {
    redirect('home.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $data = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'city' => sanitizeInput($_POST['city'] ?? ''),
        'state' => sanitizeInput($_POST['state'] ?? ''),
        'zip_code' => sanitizeInput($_POST['zip_code'] ?? '')
    ];

    $result = $auth->register($data);
    if ($result['success']) {
        // Auto-login then redirect to home
        $identifier = $data['username'] !== '' ? $data['username'] : $data['email'];
        $auth->login($identifier, $data['password']);
        redirect('home.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Customer Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/register-style.css" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header text-center mb-4">
                <i class="fas fa-user-plus text-primary" style="font-size: 3rem;"></i>
                <h2 class="mt-2">Create Account</h2>
                <p class="text-muted">Join us for a better dining experience</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="register-form">
                <input type="hidden" name="action" value="register">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Phone (optional)</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" class="form-control" name="state">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Zip Code</label>
                        <input type="text" class="form-control" name="zip_code">
                    </div>
                </div>
                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-link">Already have an account? Login</a>
                <a href="../index.php" class="btn btn-link">Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        const pw = document.getElementById('password');
        const cpw = document.getElementById('confirm_password');
        function validateMatch() {
            if (pw.value !== cpw.value) cpw.setCustomValidity('Passwords do not match');
            else cpw.setCustomValidity('');
        }
        pw.addEventListener('input', validateMatch);
        cpw.addEventListener('input', validateMatch);
    </script>
</body>
</html>

