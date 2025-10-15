<?php
/**
 * Customers Management - Class-Based Implementation
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
require_once '../src/autoload.php';
requireAdminLogin();

use RestaurantMS\Controllers\CustomerController;

// Initialize controller
$controller = new CustomerController();

// Route requests based on action
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'delete':
        $controller->delete();
        break;
    case 'search':
        $controller->search();
        break;
    case 'show':
        $customerId = (int) ($_GET['id'] ?? 0);
        if ($customerId > 0) {
            $controller->show($customerId);
        } else {
            $controller->index();
        }
        break;
    case 'update-loyalty':
        $controller->updateLoyaltyPoints();
        break;
    case 'update-tier':
        $controller->updateTier();
        break;
    case 'export':
        $controller->export();
        break;
    case 'ajax':
        $controller->ajax();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
