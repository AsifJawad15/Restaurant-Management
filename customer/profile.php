<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('../index.php');
}

$db = getDB();
$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Get customer profile
$stmt = $db->prepare("SELECT * FROM customer_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    // Create empty profile if doesn't exist
    $profile = [
        'address' => '',
        'city' => '',
        'state' => '',
        'zip_code' => '',
        'date_of_birth' => '',
        'loyalty_points' => 0,
        'preferred_payment_method' => ''
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $error = 'Invalid request token.';
    } else {
        $data = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'city' => sanitizeInput($_POST['city'] ?? ''),
            'state' => sanitizeInput($_POST['state'] ?? ''),
            'zip_code' => sanitizeInput($_POST['zip_code'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'preferred_payment_method' => sanitizeInput($_POST['preferred_payment_method'] ?? '')
        ];
        
        if ($data['first_name'] === '' || $data['last_name'] === '' || $data['email'] === '') {
            $error = 'First name, last name, and email are required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
            try {
                $db->beginTransaction();
                
                // Update user table
                $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $user['id']]);
                
                // Update or insert customer profile
                $stmt = $db->prepare("
                    INSERT INTO customer_profiles (user_id, address, city, state, zip_code, date_of_birth, preferred_payment_method) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    address = VALUES(address), city = VALUES(city), state = VALUES(state), 
                    zip_code = VALUES(zip_code), date_of_birth = VALUES(date_of_birth), 
                    preferred_payment_method = VALUES(preferred_payment_method)
                ");
                $stmt->execute([
                    $user['id'], $data['address'], $data['city'], $data['state'], 
                    $data['zip_code'], $data['date_of_birth'], $data['preferred_payment_method']
                ]);
                
                $db->commit();
                $success = 'Profile updated successfully.';
                
                // Refresh user data
                $user = $auth->getCurrentUser();
                $stmt = $db->prepare("SELECT * FROM customer_profiles WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $profile = $stmt->fetch() ?: $profile;
                
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/profile-style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="home.php"><i class="fas fa-utensils me-2"></i>Delicious</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i>Home</a>
                <a class="nav-link" href="orders.php"><i class="fas fa-receipt me-1"></i>Orders</a>
                <a class="nav-link active" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a>
                <a class="nav-link" href="../admin/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user me-2"></i>My Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($profile['state'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Zip Code</label>
                                    <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($profile['zip_code'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Payment Method</label>
                                    <select class="form-select" name="preferred_payment_method">
                                        <option value="">Select payment method</option>
                                        <option value="cash" <?php echo ($profile['preferred_payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="credit_card" <?php echo ($profile['preferred_payment_method'] ?? '') === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                        <option value="debit_card" <?php echo ($profile['preferred_payment_method'] ?? '') === 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                                        <option value="digital_wallet" <?php echo ($profile['preferred_payment_method'] ?? '') === 'digital_wallet' ? 'selected' : ''; ?>>Digital Wallet</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Loyalty Points Card -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-gift me-2"></i>Loyalty Points</h5>
                        <div class="display-4 text-primary"><?php echo (int)($profile['loyalty_points'] ?? 0); ?></div>
                        <p class="text-muted">Earn points with every order!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
