<?php

namespace RestaurantMS\Exceptions;

/**
 * Database Exception
 * 
 * Thrown when database operations fail
 */
class DatabaseException extends AppException
{
    public function __construct(string $message = "Database operation failed", int $code = 500, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}