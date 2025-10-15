<?php

namespace RestaurantMS\Core;

/**
 * Response Helper - Simple response formatting
 * 
 * Provides standardized JSON responses and redirects
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success(string $message, array $data = []): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send error response
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, $errors, 422);
    }
    
    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, [], 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized access'): void
    {
        self::error($message, [], 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Access forbidden'): void
    {
        self::error($message, [], 403);
    }
    
    /**
     * Redirect with message
     */
    public static function redirect(string $url, string $message = '', string $type = 'info'): void
    {
        if ($message) {
            session_start();
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        
        header("Location: $url");
        exit;
    }
    
    /**
     * Redirect back with message
     */
    public static function redirectBack(string $message = '', string $type = 'info', string $default = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        self::redirect($referer, $message, $type);
    }
    
    /**
     * Get flash message and clear it
     */
    public static function getFlashMessage(): ?array
    {
        session_start();
        
        if (!isset($_SESSION['flash_message'])) {
            return null;
        }
        
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return $message;
    }
    
    /**
     * Set flash message
     */
    public static function setFlashMessage(string $message, string $type = 'info'): void
    {
        session_start();
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
}