# Admin Pages Update Script
# This document lists all admin pages that need sidebar/header updates

## Pages to Update:
1. ✅ dashboard.php - DONE
2. categories.php
3. menu-items.php  
4. orders.php
5. customers.php
6. tables.php
7. staff.php
8. inventory.php
9. reservations.php
10. reviews.php
11. loyalty-points.php
12. profile.php
13. reports.php
14. analytics.php

## Update Pattern:
Replace the entire sidebar navigation (from `<nav class="admin-sidebar">` to `</nav>`) with:
```php
<?php include 'includes/sidebar.php'; ?>
```

Replace the header section (from `<header class="admin-header">` to `</header>`) with:
```php
<?php
$page_title = 'Page Title';
$page_icon = 'fas fa-icon-name';
$header_actions = '<!-- Any buttons/actions -->';
include 'includes/header.php';
?>
```

## Files Created:
- admin/includes/sidebar.php - Common sidebar navigation
- admin/includes/header.php - Common header with dropdown
