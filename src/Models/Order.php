<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * Order Model - Represents customer orders
 * 
 * Simple class for order management without complex patterns
 */
class Order extends BaseModel
{
    protected string $table = 'orders';
    protected string $primaryKey = 'order_id';
    
    protected array $fillable = [
        'customer_id',
        'table_number',
        'staff_id',
        'order_type',
        'status',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'payment_method',
        'payment_status',
        'special_instructions',
        'estimated_time',
        'completed_at',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'order_id' => 'int',
        'customer_id' => 'int',
        'table_number' => 'int',
        'staff_id' => 'int',
        'total_amount' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'estimated_time' => 'int',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Order types
    public const TYPE_DINE_IN = 'dine_in';
    public const TYPE_TAKEOUT = 'takeout';
    public const TYPE_DELIVERY = 'delivery';
    
    // Order status
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    
    // Payment status
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';
    
    private ?Customer $customer = null;
    private ?User $staff = null;
    private array $orderItems = [];
    
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
     * Get staff member
     */
    public function getStaff(): ?User
    {
        if ($this->staff === null && $this->staff_id) {
            $this->staff = User::find($this->staff_id);
        }
        return $this->staff;
    }
    
    /**
     * Get order items
     */
    public function getOrderItems(): array
    {
        if (empty($this->orderItems)) {
            $rows = $this->db->fetchAll(
                "SELECT oi.*, mi.name as item_name, mi.price as item_price 
                 FROM order_items oi 
                 JOIN menu_items mi ON oi.item_id = mi.item_id 
                 WHERE oi.order_id = ?",
                [$this->order_id]
            );
            
            foreach ($rows as $row) {
                $this->orderItems[] = $row;
            }
        }
        return $this->orderItems;
    }
    
    /**
     * Add item to order
     */
    public function addItem(int $itemId, int $quantity, ?string $notes = null): void
    {
        // Get item price
        $menuItem = MenuItem::find($itemId);
        if (!$menuItem) {
            throw new \Exception("Menu item not found");
        }
        
        $price = $menuItem->price;
        $subtotal = $price * $quantity;
        
        $this->db->insert('order_items', [
            'order_id' => $this->order_id,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => $subtotal,
            'special_instructions' => $notes
        ]);
        
        // Clear cached items
        $this->orderItems = [];
        
        // Recalculate total
        $this->calculateTotal();
    }
    
    /**
     * Calculate order total
     */
    public function calculateTotal(): void
    {
        $items = $this->getOrderItems();
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['subtotal'];
        }
        
        // Apply discount
        $discountAmount = $this->discount_amount ?: 0;
        $afterDiscount = $subtotal - $discountAmount;
        
        // Calculate tax (assume 8.5%)
        $taxRate = 0.085;
        $taxAmount = $afterDiscount * $taxRate;
        
        $this->tax_amount = round($taxAmount, 2);
        $this->total_amount = round($afterDiscount + $taxAmount, 2);
    }
    
    /**
     * Update order status
     */
    public function updateStatus(string $status): void
    {
        $this->status = $status;
        
        if ($status === self::STATUS_COMPLETED) {
            $this->completed_at = new DateTime();
            
            // Update customer purchase history
            $customer = $this->getCustomer();
            if ($customer) {
                $customer->addPurchase($this->total_amount);
                $customer->save();
            }
        }
        
        $this->save();
    }
    
    /**
     * Cancel order
     */
    public function cancel(string $reason = ''): void
    {
        $this->status = self::STATUS_CANCELLED;
        if ($reason) {
            $this->special_instructions = ($this->special_instructions ?: '') . "\nCancellation reason: " . $reason;
        }
        $this->save();
    }
    
    /**
     * Get orders by status
     */
    public static function getByStatus(string $status): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE status = ? ORDER BY created_at DESC",
            [$status]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get customer orders
     */
    public static function getByCustomer(int $customerId, int $limit = 10): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?",
            [$customerId, $limit]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get today's orders
     */
    public static function getTodaysOrders(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC"
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Create collection from rows
     */
    protected static function createCollection(array $rows): array
    {
        $orders = [];
        foreach ($rows as $row) {
            $order = new static($row);
            $order->exists = true;
            $order->original = $order->attributes;
            $orders[] = $order;
        }
        return $orders;
    }
    
    /**
     * Validate order data
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate customer
        if (empty($this->customer_id)) {
            $errors['customer_id'][] = 'Customer is required';
        }
        
        // Validate order type
        $validTypes = [self::TYPE_DINE_IN, self::TYPE_TAKEOUT, self::TYPE_DELIVERY];
        if (empty($this->order_type) || !in_array($this->order_type, $validTypes)) {
            $errors['order_type'][] = 'Valid order type is required';
        }
        
        // Validate status
        $validStatuses = [
            self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PREPARING,
            self::STATUS_READY, self::STATUS_COMPLETED, self::STATUS_CANCELLED
        ];
        if (empty($this->status) || !in_array($this->status, $validStatuses)) {
            $errors['status'][] = 'Valid status is required';
        }
        
        // Validate amounts
        if ($this->total_amount !== null && $this->total_amount < 0) {
            $errors['total_amount'][] = 'Total amount cannot be negative';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Order validation failed', $errors);
        }
    }
    
    /**
     * Set defaults on insert
     */
    protected function performInsert(): bool
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
        
        if (empty($this->status)) {
            $this->status = self::STATUS_PENDING;
        }
        if (empty($this->payment_status)) {
            $this->payment_status = self::PAYMENT_PENDING;
        }
        if ($this->total_amount === null) {
            $this->total_amount = 0.00;
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
     * Get today's order count
     */
    public function getTodaysOrderCount(): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $result = $this->db->fetchAll($query);
        return (int) $result[0]['total'];
    }
    
    /**
     * Get today's revenue (paid orders only)
     */
    public function getTodaysRevenue(): float
    {
        $query = "SELECT COALESCE(SUM(final_amount), 0) as total FROM {$this->table} 
                 WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'";
        $result = $this->db->fetchAll($query);
        return (float) $result[0]['total'];
    }
    
    /**
     * Get pending orders count
     */
    public function getPendingOrderCount(): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} 
                 WHERE status IN ('pending', 'confirmed', 'preparing')";
        $result = $this->db->fetchAll($query);
        return (int) $result[0]['total'];
    }
    
    /**
     * Get recent orders with customer details
     */
    public function getRecentOrdersWithCustomers(int $limit = 10): array
    {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email 
                 FROM {$this->table} o 
                 JOIN users u ON o.customer_id = u.id 
                 ORDER BY o.created_at DESC 
                 LIMIT ?";
        return $this->db->fetchAll($query, [$limit]);
    }
}