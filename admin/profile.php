<?php
/**
 * Admin Profile Page
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
requireAdminLogin();

$adminId = $_SESSION['admin_id'];
$success = '';
$error = '';

try {
    $conn = getDBConnection();
    
    // Get admin details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'admin'");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        header('Location: logout.php');
        exit;
    }
    
    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $username = trim($_POST['username']);
        
        // Validate inputs
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $error = "First name, last name, and email are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } else {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $adminId]);
            if ($stmt->fetch()) {
                $error = "Email is already taken by another user!";
            } else {
                // Check if username is already taken by another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $adminId]);
                if ($stmt->fetch()) {
                    $error = "Username is already taken!";
                } else {
                    // Update profile
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, phone = ?, username = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$firstName, $lastName, $email, $phone, $username, $adminId])) {
                        $success = "Profile updated successfully!";
                        // Refresh admin data
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$adminId]);
                        $admin = $stmt->fetch();
                        $_SESSION['admin_name'] = $firstName . ' ' . $lastName;
                    } else {
                        $error = "Failed to update profile!";
                    }
                }
            }
        }
    }
    
    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "All password fields are required!";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match!";
        } elseif (strlen($newPassword) < 6) {
            $error = "New password must be at least 6 characters long!";
        } else {
            // Verify current password
            if (password_verify($currentPassword, $admin['password_hash'])) {
                // Update password
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                
                if ($stmt->execute([$newPasswordHash, $adminId])) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to change password!";
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Restaurant Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            border: 5px solid rgba(255,255,255,0.3);
        }
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .profile-card h5 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #c5a572;
        }
        .info-group {
            margin-bottom: 1.5rem;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .info-value {
            color: #333;
            font-size: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .btn-update {
            background: #c5a572;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
        }
        .btn-update:hover {
            background: #b08d5f;
            color: white;
        }
        .form-control:focus {
            border-color: #c5a572;
            box-shadow: 0 0 0 0.2rem rgba(197, 165, 114, 0.25);
        }
    </style>
</head>
<body>
    <?php
    // Set page-specific variables
    $page_title = 'My Profile';
    $page_icon = 'fas fa-user';
    ?>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>

            <!-- Main Content Area -->
            <main class="main-content">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)); ?>
                    </div>
                    <h2><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h2>
                    <p class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Administrator
                    </p>
                </div>

                <div class="row">
                    <!-- Left Column - Profile Info -->
                    <div class="col-lg-4">
                        <div class="profile-card">
                            <h5>
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Account Information
                            </h5>
                            
                            <div class="info-group">
                                <div class="info-label">Username</div>
                                <div class="info-value">
                                    <i class="fas fa-user me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($admin['username']); ?>
                                </div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Email</div>
                                <div class="info-value">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($admin['email']); ?>
                                </div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Phone</div>
                                <div class="info-value">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    <?php echo $admin['phone'] ? htmlspecialchars($admin['phone']) : 'Not provided'; ?>
                                </div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Account Status</div>
                                <div class="info-value">
                                    <?php if ($admin['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Inactive
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Member Since</div>
                                <div class="info-value">
                                    <i class="fas fa-calendar me-2 text-muted"></i>
                                    <?php echo date('F j, Y', strtotime($admin['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="info-group mb-0">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">
                                    <i class="fas fa-clock me-2 text-muted"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($admin['updated_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Edit Forms -->
                    <div class="col-lg-8">
                        <!-- Update Profile Form -->
                        <div class="profile-card">
                            <h5>
                                <i class="fas fa-edit text-warning me-2"></i>
                                Edit Profile
                            </h5>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" 
                                           placeholder="+1 (555) 123-4567">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-update">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>

                        <!-- Change Password Form -->
                        <div class="profile-card">
                            <h5>
                                <i class="fas fa-lock text-danger me-2"></i>
                                Change Password
                            </h5>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" class="form-control" 
                                           placeholder="Minimum 6 characters" required>
                                    <small class="text-muted">Password must be at least 6 characters long.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-danger">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
