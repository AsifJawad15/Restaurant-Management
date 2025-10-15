<?php

namespace RestaurantMS\Exceptions;

/**
 * Authentication Exception
 * 
 * Thrown when authentication fails
 */
class AuthException extends AppException
{
    public function __construct(string $message = "Authentication failed", int $code = 401, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}