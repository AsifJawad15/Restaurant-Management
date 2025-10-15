<?php

namespace RestaurantMS\Exceptions;

use Exception;

/**
 * Base Application Exception
 * 
 * Custom exception class for restaurant management system
 */
class AppException extends Exception
{
    protected array $context = [];
    
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    /**
     * Get additional context information
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Set additional context information
     * 
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}