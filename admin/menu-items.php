<?php
/**
 * Menu Items Management - Class-Based Implementation
 * ASIF - Backend & Database Developer
 */

require_once '../includes/config.php';
require_once '../src/autoload.php';
requireAdminLogin();

use RestaurantMS\Controllers\MenuController;

// Initialize controller
$controller = new MenuController();

// Route requests based on action
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'update':
        $controller->update();
        break;
    case 'delete':
        $controller->delete();
        break;
    case 'toggle-availability':
        $controller->toggleAvailability();
        break;
    case 'toggle-featured':
        $controller->toggleFeatured();
        break;
    case 'ajax':
        $controller->ajax();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}