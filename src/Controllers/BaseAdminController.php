<?php

namespace RestaurantMS\Controllers;

use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Base Admin Controller
 * 
 * Provides common functionality for all admin controllers
 * Implements authentication, error handling, and common operations
 */
abstract class BaseAdminController
{
    protected Database $db;
    protected array $session;
    protected array $request;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = &$_SESSION;
        $this->request = $_REQUEST;
        
        $this->requireAdminLogin();
    }
    
    /**
     * Require admin login
     * 
     * @throws ValidationException
     */
    protected function requireAdminLogin(): void
    {
        if (!isset($this->session['admin_id']) || !isset($this->session['admin_email'])) {
            $this->redirectToLogin();
        }
    }
    
    /**
     * Get current admin user
     * 
     * @return array
     */
    protected function getCurrentAdmin(): array
    {
        return [
            'id' => $this->session['admin_id'],
            'email' => $this->session['admin_email'],
            'name' => $this->session['admin_name'] ?? 'Admin'
        ];
    }
    
    /**
     * Redirect to login page
     */
    protected function redirectToLogin(): void
    {
        header('Location: login.php');
        exit;
    }
    
    /**
     * Redirect to specified URL
     * 
     * @param string $url
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
    
    /**
     * Set success message
     * 
     * @param string $message
     */
    protected function setSuccessMessage(string $message): void
    {
        $this->session['success'] = $message;
    }
    
    /**
     * Set error message
     * 
     * @param string $message
     */
    protected function setErrorMessage(string $message): void
    {
        $this->session['error'] = $message;
    }
    
    /**
     * Get and clear session messages
     * 
     * @return array
     */
    protected function getSessionMessages(): array
    {
        $messages = [
            'success' => $this->session['success'] ?? '',
            'error' => $this->session['error'] ?? ''
        ];
        
        unset($this->session['success'], $this->session['error']);
        return $messages;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    protected function validateCSRFToken(string $token): bool
    {
        return isset($this->session['csrf_token']) && 
               hash_equals($this->session['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    protected function generateCSRFToken(): string
    {
        if (!isset($this->session['csrf_token'])) {
            $this->session['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $this->session['csrf_token'];
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $data
     * @return mixed
     */
    protected function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    /**
     * Handle database exceptions
     * 
     * @param \Exception $e
     * @param string $defaultMessage
     */
    protected function handleDatabaseException(\Exception $e, string $defaultMessage = 'Database error occurred'): void
    {
        if ($e instanceof DatabaseException) {
            $this->setErrorMessage($e->getMessage());
        } else {
            $this->setErrorMessage($defaultMessage);
        }
        
        error_log("Database error: " . $e->getMessage());
    }
    
    /**
     * Handle validation exceptions
     * 
     * @param ValidationException $e
     */
    protected function handleValidationException(ValidationException $e): void
    {
        $errors = $e->getErrors();
        $message = "Validation failed: " . implode(', ', array_map(function($fieldErrors) {
            return implode(', ', $fieldErrors);
        }, $errors));
        
        $this->setErrorMessage($message);
    }
    
    /**
     * Send JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    protected function sendJsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is POST
     * 
     * @return bool
     */
    protected function isPostRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request is GET
     * 
     * @return bool
     */
    protected function isGetRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Get request parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getRequestParam(string $key, $default = null)
    {
        return $this->request[$key] ?? $default;
    }
    
    /**
     * Get POST parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getPostParam(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getGetParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Format price for display
     * 
     * @param float $price
     * @return string
     */
    protected function formatPrice(float $price): string
    {
        if ($price === null || $price === '' || !is_numeric($price)) {
            $price = 0;
        }
        return '$' . number_format((float)$price, 2);
    }
    
    /**
     * Format date for display
     * 
     * @param string $date
     * @return string
     */
    protected function formatDate(string $date): string
    {
        return date('M d, Y', strtotime($date));
    }
    
    /**
     * Format datetime for display
     * 
     * @param string $datetime
     * @return string
     */
    protected function formatDateTime(string $datetime): string
    {
        return date('M d, Y H:i', strtotime($datetime));
    }
}
