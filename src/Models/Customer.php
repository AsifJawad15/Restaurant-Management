<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * Customer Model - Extends User for customer-specific functionality
 * 
 * Handles customer profiles, loyalty points, and preferences
 */
class Customer extends User
{
    protected string $table = 'customer_profiles';
    protected string $primaryKey = 'customer_id';
    
    protected array $fillable = [
        'user_id',
        'date_of_birth',
        'preferences',
        'loyalty_points',
        'tier_level',
        'total_spent',
        'visit_count',
        'last_visit',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'customer_id' => 'int',
        'user_id' => 'int',
        'date_of_birth' => 'datetime',
        'preferences' => 'json',
        'loyalty_points' => 'int',
        'total_spent' => 'float',
        'visit_count' => 'int',
        'last_visit' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Tier levels
    public const TIER_BRONZE = 'bronze';
    public const TIER_SILVER = 'silver';
    public const TIER_GOLD = 'gold';
    public const TIER_PLATINUM = 'platinum';
    
    private ?User $user = null;
    
    /**
     * Get associated user
     * 
     * @return User|null
     */
    public function getUser(): ?User
    {
        if ($this->user === null && $this->user_id) {
            $this->user = User::find($this->user_id);
        }
        return $this->user;
    }
    
    /**
     * Set associated user
     * 
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->user_id = $user->user_id;
    }
    
    /**
     * Get customer's full name from associated user
     * 
     * @return string
     */
    public function getFullName(): string
    {
        $user = $this->getUser();
        return $user ? $user->getFullName() : '';
    }
    
    /**
     * Get customer's email from associated user
     * 
     * @return string
     */
    public function getEmail(): string
    {
        $user = $this->getUser();
        return $user ? $user->email : '';
    }
    
    /**
     * Calculate age from date of birth
     * 
     * @return int|null
     */
    public function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        $today = new DateTime();
        $birthDate = $this->date_of_birth instanceof DateTime 
            ? $this->date_of_birth 
            : new DateTime($this->date_of_birth);
            
        return $today->diff($birthDate)->y;
    }
    
    /**
     * Add loyalty points
     * 
     * @param int $points
     */
    public function addLoyaltyPoints(int $points): void
    {
        $this->loyalty_points = ($this->loyalty_points ?: 0) + $points;
        $this->updateTierLevel();
    }
    
    /**
     * Redeem loyalty points
     * 
     * @param int $points
     * @return bool
     */
    public function redeemLoyaltyPoints(int $points): bool
    {
        if ($this->loyalty_points >= $points) {
            $this->loyalty_points -= $points;
            $this->updateTierLevel();
            return true;
        }
        return false;
    }
    
    /**
     * Update tier level based on points or spending
     */
    protected function updateTierLevel(): void
    {
        $points = $this->loyalty_points ?: 0;
        $spent = $this->total_spent ?: 0;
        
        if ($points >= 5000 || $spent >= 1000) {
            $this->tier_level = self::TIER_PLATINUM;
        } elseif ($points >= 2000 || $spent >= 500) {
            $this->tier_level = self::TIER_GOLD;
        } elseif ($points >= 500 || $spent >= 200) {
            $this->tier_level = self::TIER_SILVER;
        } else {
            $this->tier_level = self::TIER_BRONZE;
        }
    }
    
    /**
     * Add purchase amount to total spent
     * 
     * @param float $amount
     */
    public function addPurchase(float $amount): void
    {
        $this->total_spent = ($this->total_spent ?: 0) + $amount;
        $this->visit_count = ($this->visit_count ?: 0) + 1;
        $this->last_visit = new DateTime();
        
        // Award loyalty points (1 point per $10 spent)
        $points = floor($amount / 10);
        $this->addLoyaltyPoints((int)$points);
    }
    
    /**
     * Get customer preferences
     * 
     * @return array
     */
    public function getPreferences(): array
    {
        return $this->preferences ?: [];
    }
    
    /**
     * Set customer preferences
     * 
     * @param array $preferences
     */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
    }
    
    /**
     * Add preference
     * 
     * @param string $key
     * @param mixed $value
     */
    public function addPreference(string $key, $value): void
    {
        $preferences = $this->getPreferences();
        $preferences[$key] = $value;
        $this->setPreferences($preferences);
    }
    
    /**
     * Get preference by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference(string $key, $default = null)
    {
        $preferences = $this->getPreferences();
        return $preferences[$key] ?? $default;
    }
    
    /**
     * Find customer by user ID
     * 
     * @param int $userId
     * @return static|null
     */
    public static function findByUserId(int $userId): ?self
    {
        $instance = new static();
        $row = $instance->db->fetchRow(
            "SELECT * FROM {$instance->table} WHERE user_id = ?",
            [$userId]
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
     * Get customers by tier level
     * 
     * @param string $tier
     * @return array
     */
    public static function getByTier(string $tier): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE tier_level = ? ORDER BY loyalty_points DESC",
            [$tier]
        );
        
        $customers = [];
        foreach ($rows as $row) {
            $customer = new static($row);
            $customer->exists = true;
            $customer->original = $customer->attributes;
            $customers[] = $customer;
        }
        
        return $customers;
    }
    
    /**
     * Get top customers by spending
     * 
     * @param int $limit
     * @return array
     */
    public static function getTopSpenders(int $limit = 10): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} ORDER BY total_spent DESC LIMIT ?",
            [$limit]
        );
        
        $customers = [];
        foreach ($rows as $row) {
            $customer = new static($row);
            $customer->exists = true;
            $customer->original = $customer->attributes;
            $customers[] = $customer;
        }
        
        return $customers;
    }
    
    /**
     * Validate customer data
     * 
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate user_id
        if (empty($this->user_id)) {
            $errors['user_id'][] = 'User ID is required';
        } else {
            // Check if user exists
            $user = User::find($this->user_id);
            if (!$user) {
                $errors['user_id'][] = 'Invalid user ID';
            } elseif (!$user->isCustomer()) {
                $errors['user_id'][] = 'User must be a customer';
            }
        }
        
        // Validate date of birth
        if (!empty($this->date_of_birth)) {
            try {
                $birthDate = $this->date_of_birth instanceof DateTime 
                    ? $this->date_of_birth 
                    : new DateTime($this->date_of_birth);
                
                $today = new DateTime();
                if ($birthDate > $today) {
                    $errors['date_of_birth'][] = 'Date of birth cannot be in the future';
                }
                
                $age = $today->diff($birthDate)->y;
                if ($age < 13) {
                    $errors['date_of_birth'][] = 'Customer must be at least 13 years old';
                }
            } catch (\Exception $e) {
                $errors['date_of_birth'][] = 'Invalid date format';
            }
        }
        
        // Validate tier level
        $validTiers = [self::TIER_BRONZE, self::TIER_SILVER, self::TIER_GOLD, self::TIER_PLATINUM];
        if (!empty($this->tier_level) && !in_array($this->tier_level, $validTiers)) {
            $errors['tier_level'][] = 'Invalid tier level';
        }
        
        // Validate numeric fields
        if (!empty($this->loyalty_points) && $this->loyalty_points < 0) {
            $errors['loyalty_points'][] = 'Loyalty points cannot be negative';
        }
        
        if (!empty($this->total_spent) && $this->total_spent < 0) {
            $errors['total_spent'][] = 'Total spent cannot be negative';
        }
        
        if (!empty($this->visit_count) && $this->visit_count < 0) {
            $errors['visit_count'][] = 'Visit count cannot be negative';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Customer validation failed', $errors);
        }
    }
    
    /**
     * Perform insert operation with timestamps and tier calculation
     * 
     * @return bool
     */
    protected function performInsert(): bool
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
        
        // Set default values
        if ($this->loyalty_points === null) {
            $this->loyalty_points = 0;
        }
        if ($this->total_spent === null) {
            $this->total_spent = 0.00;
        }
        if ($this->visit_count === null) {
            $this->visit_count = 0;
        }
        if (empty($this->tier_level)) {
            $this->tier_level = self::TIER_BRONZE;
        }
        
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
    
    /**
     * Convert to array with user information
     * 
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add user information if available
        $user = $this->getUser();
        if ($user) {
            $array['user'] = $user->toArray();
        }
        
        return $array;
    }
}