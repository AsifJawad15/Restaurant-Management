<?php
/**
 * Admin Header
 * ASIF - Backend & Database Developer
 * Common header component for all admin pages
 */

// Page title can be set before including this file
if (!isset($page_title)) {
    $page_title = 'Admin Panel';
}

// Page icon can be set before including this file
if (!isset($page_icon)) {
    $page_icon = 'fas fa-tachometer-alt';
}

// Header actions can be set before including this file (HTML content)
if (!isset($header_actions)) {
    $header_actions = '';
}
?>

<!-- Top Header -->
<header class="admin-header">
    <div class="header-content">
        <h1 class="page-title">
            <i class="<?php echo $page_icon; ?> me-2"></i>
            <?php echo $page_title; ?>
        </h1>
        <div class="header-actions">
            <?php echo $header_actions; ?>
            <div class="dropdown admin-dropdown ms-3">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo $_SESSION['admin_name']; ?></span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
