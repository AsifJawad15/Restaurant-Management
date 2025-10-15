<?php
/**
 * Restaurant Management System - Autoloader
 * 
 * PSR-4 compliant autoloader for the Restaurant Management System
 * Maps namespaces to directory structures for automatic class loading
 */

spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $prefix = 'RestaurantMS\\';
    $baseDir = __DIR__ . '/';
    
    // Check if the class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($className, $len);
    
    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Global exception handler for the application
set_exception_handler(function ($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    
    // In development, show detailed error
    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo "<h1>Error</h1>";
        echo "<p>" . $exception->getMessage() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        // In production, show generic error
        http_response_code(500);
        include __DIR__ . '/../includes/error.php';
    }
});

// Set error reporting based on environment
if (defined('APP_ENV') && APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}