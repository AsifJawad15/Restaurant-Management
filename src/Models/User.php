<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * User Model - Represents system users (customers and staff)
 * 
 * Implements Active Record pattern for user management
 * Supports role-based access control
 */
class User extends BaseModel
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'username',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'phone',
        'user_type',
        'is_active',
        'created_at',
        'updated_at'
    ];
    
    protected array $hidden = [
        'password_hash'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // User types constants
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_ADMIN = 'admin';
    public const TYPE_STAFF = 'staff';
    public const TYPE_MANAGER = 'manager';
    
    /**
     * Hash password before saving
     * 
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->setAttribute('password_hash', password_hash($password, PASSWORD_DEFAULT));
    }
    
    /**
     * Verify password
     * 
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->getAttribute('password_hash'));
    }
    
    /**
     * Get full name
     * 
     * @return string
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    
    /**
     * Check if user is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->user_type === self::TYPE_ADMIN;
    }
    
    /**
     * Check if user is staff
     * 
     * @return bool
     */
    public function isStaff(): bool
    {
        return in_array($this->user_type, [self::TYPE_STAFF, self::TYPE_MANAGER, self::TYPE_ADMIN]);
    }
    
    /**
     * Check if user is customer
     * 
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->user_type === self::TYPE_CUSTOMER;
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        // Note: last_login column not available in current database schema
        // Could be added later if needed
        // $this->last_login = new DateTime();
        // $this->save();
    }
    
    /**
     * Find user by email
     * 
     * @param string $email
     * @return static|null
     */
    public static function findByEmail(string $email): ?self
    {
        $instance = new static();
        $row = $instance->db->fetchRow(
            "SELECT * FROM {$instance->table} WHERE email = ?",
            [$email]
        );
        
        if ($row) {
            $instance->fill($row);
            $instance->exists = true;
            $instance->original = $instance->attributes;
            return $instance;
        }
        
        return null;
    }
    
    /**
     * Find user by username
     * 
     * @param string $username
     * @return static|null
     */
    public static function findByUsername(string $username): ?self
    {
        $instance = new static();
        $row = $instance->db->fetchRow(
            "SELECT * FROM {$instance->table} WHERE username = ?",
            [$username]
        );
        
        if ($row) {
            $instance->fill($row);
            $instance->exists = true;
            $instance->original = $instance->attributes;
            return $instance;
        }
        
        return null;
    }
    
    /**
     * Get users by type
     * 
     * @param string $type
     * @return array
     */
    public static function getByType(string $type): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE user_type = ? ORDER BY created_at DESC",
            [$type]
        );
        
        $users = [];
        foreach ($rows as $row) {
            $user = new static($row);
            $user->exists = true;
            $user->original = $user->attributes;
            $users[] = $user;
        }
        
        return $users;
    }
    
    /**
     * Validate user data
     * 
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate email
        if (empty($this->email)) {
            $errors['email'][] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email format';
        } else {
            // Check for duplicate email
            $existing = self::findByEmail($this->email);
            if ($existing && $existing->id !== $this->id) {
                $errors['email'][] = 'Email already exists';
            }
        }
        
        // Validate username
        if (empty($this->username)) {
            $errors['username'][] = 'Username is required';
        } elseif (strlen($this->username) < 3) {
            $errors['username'][] = 'Username must be at least 3 characters';
        } else {
            // Check for duplicate username
            $existing = self::findByUsername($this->username);
            if ($existing && $existing->id !== $this->id) {
                $errors['username'][] = 'Username already exists';
            }
        }
        
        // Note: Password validation is handled through setPassword() method
        // Raw passwords are not stored as attributes, only hashed passwords
        
        // For new users, ensure password_hash exists (set via setPassword method)
        if (!$this->exists && empty($this->password_hash)) {
            $errors['password'][] = 'Password is required';
        }
        
        // Validate names
        if (empty($this->first_name)) {
            $errors['first_name'][] = 'First name is required';
        }
        
        if (empty($this->last_name)) {
            $errors['last_name'][] = 'Last name is required';
        }
        
        // Validate user type
        $validTypes = [self::TYPE_CUSTOMER, self::TYPE_ADMIN, self::TYPE_STAFF, self::TYPE_MANAGER];
        if (empty($this->user_type) || !in_array($this->user_type, $validTypes)) {
            $errors['user_type'][] = 'Valid user type is required';
        }
        
        // Validate phone if provided
        if (!empty($this->phone) && !preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $this->phone)) {
            $errors['phone'][] = 'Invalid phone number format';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('User validation failed', $errors);
        }
    }
    
    /**
     * Perform insert operation with timestamps
     * 
     * @return bool
     */
    protected function performInsert(): bool
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
        return parent::performInsert();
    }
    
    /**
     * Perform update operation with timestamps
     * 
     * @return bool
     */
    protected function performUpdate(): bool
    {
        $this->updated_at = new DateTime();
        return parent::performUpdate();
    }
}