<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * Review Model - Represents customer reviews
 * 
 * Simple class for review management
 */
class Review extends BaseModel
{
    protected string $table = 'reviews';
    protected string $primaryKey = 'review_id';
    
    protected array $fillable = [
        'customer_id',
        'order_id',
        'item_id',
        'rating',
        'review_text',
        'is_verified',
        'is_featured',
        'staff_response',
        'responded_at',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'review_id' => 'int',
        'customer_id' => 'int',
        'order_id' => 'int',
        'item_id' => 'int',
        'rating' => 'int',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    private ?Customer $customer = null;
    private ?Order $order = null;
    private ?MenuItem $menuItem = null;
    
    /**
     * Get customer
     */
    public function getCustomer(): ?Customer
    {
        if ($this->customer === null && $this->customer_id) {
            $this->customer = Customer::find($this->customer_id);
        }
        return $this->customer;
    }
    
    /**
     * Get order
     */
    public function getOrder(): ?Order
    {
        if ($this->order === null && $this->order_id) {
            $this->order = Order::find($this->order_id);
        }
        return $this->order;
    }
    
    /**
     * Get menu item
     */
    public function getMenuItem(): ?MenuItem
    {
        if ($this->menuItem === null && $this->item_id) {
            $this->menuItem = MenuItem::find($this->item_id);
        }
        return $this->menuItem;
    }
    
    /**
     * Get customer name
     */
    public function getCustomerName(): string
    {
        $customer = $this->getCustomer();
        return $customer ? $customer->getFullName() : 'Anonymous';
    }
    
    /**
     * Get menu item name
     */
    public function getMenuItemName(): string
    {
        $item = $this->getMenuItem();
        return $item ? $item->name : 'Unknown Item';
    }
    
    /**
     * Get rating stars as HTML
     */
    public function getRatingStars(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted"></i>';
            }
        }
        return $stars;
    }
    
    /**
     * Check if review is positive (4-5 stars)
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }
    
    /**
     * Check if review is negative (1-2 stars)
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }
    
    /**
     * Mark as verified
     */
    public function markAsVerified(): void
    {
        $this->is_verified = true;
        $this->save();
    }
    
    /**
     * Mark as featured
     */
    public function markAsFeatured(): void
    {
        $this->is_featured = true;
        $this->save();
    }
    
    /**
     * Add staff response
     */
    public function addStaffResponse(string $response): void
    {
        $this->staff_response = $response;
        $this->responded_at = new DateTime();
        $this->save();
    }
    
    /**
     * Get verified reviews
     */
    public static function getVerified(int $limit = 10): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE is_verified = 1 ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get featured reviews
     */
    public static function getFeatured(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE is_featured = 1 AND is_verified = 1 ORDER BY created_at DESC"
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get reviews by rating
     */
    public static function getByRating(int $rating): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE rating = ? ORDER BY created_at DESC",
            [$rating]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get reviews for menu item
     */
    public static function getForMenuItem(int $itemId): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE item_id = ? AND is_verified = 1 ORDER BY created_at DESC",
            [$itemId]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get customer reviews
     */
    public static function getByCustomer(int $customerId): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE customer_id = ? ORDER BY created_at DESC",
            [$customerId]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get recent reviews
     */
    public static function getRecent(int $days = 7, int $limit = 10): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY created_at DESC LIMIT ?",
            [$days, $limit]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get review statistics
     */
    public static function getStatistics(): array
    {
        $instance = new static();
        
        // Overall stats
        $overall = $instance->db->fetchRow(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_count
             FROM {$instance->table}"
        );
        
        // Rating breakdown
        $breakdown = $instance->db->fetchAll(
            "SELECT rating, COUNT(*) as count 
             FROM {$instance->table} 
             WHERE is_verified = 1 
             GROUP BY rating 
             ORDER BY rating DESC"
        );
        
        return [
            'total_reviews' => (int)$overall['total_reviews'],
            'average_rating' => round((float)$overall['average_rating'], 2),
            'verified_count' => (int)$overall['verified_count'],
            'rating_breakdown' => $breakdown
        ];
    }
    
    /**
     * Get average rating for menu item
     */
    public static function getAverageRatingForItem(int $itemId): float
    {
        $instance = new static();
        $result = $instance->db->fetchRow(
            "SELECT AVG(rating) as avg_rating FROM {$instance->table} WHERE item_id = ? AND is_verified = 1",
            [$itemId]
        );
        
        return round((float)($result['avg_rating'] ?? 0), 2);
    }
    
    /**
     * Create collection from rows
     */
    protected static function createCollection(array $rows): array
    {
        $reviews = [];
        foreach ($rows as $row) {
            $review = new static($row);
            $review->exists = true;
            $review->original = $review->attributes;
            $reviews[] = $review;
        }
        return $reviews;
    }
    
    /**
     * Validate review data
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate customer
        if (empty($this->customer_id)) {
            $errors['customer_id'][] = 'Customer is required';
        }
        
        // Validate rating
        if (empty($this->rating)) {
            $errors['rating'][] = 'Rating is required';
        } elseif ($this->rating < 1 || $this->rating > 5) {
            $errors['rating'][] = 'Rating must be between 1 and 5';
        }
        
        // Validate review text
        if (empty($this->review_text)) {
            $errors['review_text'][] = 'Review text is required';
        } elseif (strlen($this->review_text) < 10) {
            $errors['review_text'][] = 'Review must be at least 10 characters';
        } elseif (strlen($this->review_text) > 1000) {
            $errors['review_text'][] = 'Review cannot exceed 1000 characters';
        }
        
        // Validate item exists if provided
        if (!empty($this->item_id)) {
            $item = MenuItem::find($this->item_id);
            if (!$item) {
                $errors['item_id'][] = 'Invalid menu item';
            }
        }
        
        // Validate order exists if provided
        if (!empty($this->order_id)) {
            $order = Order::find($this->order_id);
            if (!$order) {
                $errors['order_id'][] = 'Invalid order';
            } elseif ($order->customer_id !== $this->customer_id) {
                $errors['order_id'][] = 'Order does not belong to this customer';
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Review validation failed', $errors);
        }
    }
    
    /**
     * Set defaults on insert
     */
    protected function performInsert(): bool
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
        
        if ($this->is_verified === null) {
            $this->is_verified = false;
        }
        if ($this->is_featured === null) {
            $this->is_featured = false;
        }
        
        return parent::performInsert();
    }
    
    /**
     * Update timestamp on update
     */
    protected function performUpdate(): bool
    {
        $this->updated_at = new DateTime();
        return parent::performUpdate();
    }
    
    /**
     * Get review with customer and item details
     */
    public function toArrayWithDetails(): array
    {
        $array = $this->toArray();
        
        // Add customer info
        $customer = $this->getCustomer();
        if ($customer) {
            $array['customer_name'] = $customer->getFullName();
        }
        
        // Add menu item info
        $item = $this->getMenuItem();
        if ($item) {
            $array['item_name'] = $item->name;
        }
        
        // Add formatted data
        $array['rating_stars'] = $this->getRatingStars();
        $array['formatted_date'] = $this->created_at->format('F j, Y');
        
        return $array;
    }
}