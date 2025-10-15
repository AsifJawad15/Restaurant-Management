<?php

namespace RestaurantMS\Core;

use RestaurantMS\Exceptions\ValidationException;

/**
 * Validator - Simple validation helper
 * 
 * Provides common validation rules for forms and data
 */
class Validator
{
    private array $data = [];
    private array $rules = [];
    private array $errors = [];
    private array $customMessages = [];
    
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    
    /**
     * Set validation rules
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * Set custom error messages
     */
    public function messages(array $messages): self
    {
        $this->customMessages = $messages;
        return $this;
    }
    
    /**
     * Validate data against rules
     */
    public function validate(): bool
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $fieldRules) {
            $rules = explode('|', $fieldRules);
            $value = $this->data[$field] ?? null;
            
            foreach ($rules as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->validate();
    }
    
    /**
     * Check if validation passed
     */
    public function isValid(): bool
    {
        return $this->validate();
    }
    
    /**
     * Get first error message
     */
    public function getFirstError(): string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return '';
    }
    
    /**
     * Add required validation for fields
     */
    public function required(array $fields): self
    {
        foreach ($fields as $field) {
            $this->rules[$field] = 'required';
        }
        return $this;
    }
    
    /**
     * Add email validation for field
     */
    public function email(string $field): self
    {
        if (isset($this->rules[$field])) {
            $this->rules[$field] .= '|email';
        } else {
            $this->rules[$field] = 'email';
        }
        return $this;
    }
    
    /**
     * Validate or throw exception
     */
    public function validateOrFail(): void
    {
        if ($this->fails()) {
            throw new ValidationException('Validation failed', $this->errors);
        }
    }
    
    /**
     * Validate a single rule
     */
    private function validateRule(string $field, $value, string $rule): void
    {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;
        
        $passed = true;
        $message = '';
        
        switch ($ruleName) {
            case 'required':
                $passed = $this->validateRequired($value);
                $message = $this->getMessage($field, $ruleName, "{field} is required");
                break;
                
            case 'email':
                $passed = $this->validateEmail($value);
                $message = $this->getMessage($field, $ruleName, "{field} must be a valid email");
                break;
                
            case 'min':
                $passed = $this->validateMin($value, (int)$parameter);
                $message = $this->getMessage($field, $ruleName, "{field} must be at least {$parameter} characters");
                break;
                
            case 'max':
                $passed = $this->validateMax($value, (int)$parameter);
                $message = $this->getMessage($field, $ruleName, "{field} cannot exceed {$parameter} characters");
                break;
                
            case 'numeric':
                $passed = $this->validateNumeric($value);
                $message = $this->getMessage($field, $ruleName, "{field} must be numeric");
                break;
                
            case 'integer':
                $passed = $this->validateInteger($value);
                $message = $this->getMessage($field, $ruleName, "{field} must be an integer");
                break;
                
            case 'phone':
                $passed = $this->validatePhone($value);
                $message = $this->getMessage($field, $ruleName, "{field} must be a valid phone number");
                break;
                
            case 'date':
                $passed = $this->validateDate($value);
                $message = $this->getMessage($field, $ruleName, "{field} must be a valid date");
                break;
                
            case 'in':
                $allowed = explode(',', $parameter);
                $passed = $this->validateIn($value, $allowed);
                $message = $this->getMessage($field, $ruleName, "{field} must be one of: " . implode(', ', $allowed));
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;
                $passed = $value === $confirmValue;
                $message = $this->getMessage($field, $ruleName, "{field} confirmation does not match");
                break;
        }
        
        if (!$passed) {
            $this->addError($field, $message);
        }
    }
    
    /**
     * Validate required field
     */
    private function validateRequired($value): bool
    {
        if (is_null($value)) {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        if (is_array($value) && empty($value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email
     */
    private function validateEmail($value): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate minimum length
     */
    private function validateMin($value, int $min): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return mb_strlen((string)$value) >= $min;
    }
    
    /**
     * Validate maximum length
     */
    private function validateMax($value, int $max): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return mb_strlen((string)$value) <= $max;
    }
    
    /**
     * Validate numeric value
     */
    private function validateNumeric($value): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return is_numeric($value);
    }
    
    /**
     * Validate integer value
     */
    private function validateInteger($value): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validate phone number
     */
    private function validatePhone($value): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $value);
    }
    
    /**
     * Validate date
     */
    private function validateDate($value): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return strtotime($value) !== false;
    }
    
    /**
     * Validate value is in allowed list
     */
    private function validateIn($value, array $allowed): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Optional field
        }
        
        return in_array($value, $allowed);
    }
    
    /**
     * Get error message
     */
    private function getMessage(string $field, string $rule, string $default): string
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } else {
            $message = $default;
        }
        
        // Replace placeholders
        $message = str_replace('{field}', ucfirst(str_replace('_', ' ', $field)), $message);
        
        return $message;
    }
    
    /**
     * Add validation error
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Static validation method
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return (new self($data))->rules($rules)->messages($messages);
    }
    
    /**
     * Quick validation
     */
    public static function quick(array $data, array $rules, array $messages = []): bool
    {
        return self::make($data, $rules, $messages)->validate();
    }
}