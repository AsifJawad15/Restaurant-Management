<?php

namespace RestaurantMS\Exceptions;

/**
 * Validation Exception
 * 
 * Thrown when validation fails
 */
class ValidationException extends AppException
{
    private array $errors = [];
    
    public function __construct(string $message = "Validation failed", array $errors = [], int $code = 422, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous, ['errors' => $errors]);
        $this->errors = $errors;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Add validation error
     * 
     * @param string $field
     * @param string $message
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}