<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * Reservation Model - Represents table reservations
 * 
 * Simple class for reservation management
 */
class Reservation extends BaseModel
{
    protected string $table = 'reservations';
    protected string $primaryKey = 'reservation_id';
    
    protected array $fillable = [
        'customer_id',
        'customer_name',
        'customer_email', 
        'customer_phone',
        'party_size',
        'reservation_date',
        'reservation_time',
        'table_number',
        'status',
        'special_requests',
        'notes',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'reservation_id' => 'int',
        'customer_id' => 'int',
        'party_size' => 'int',
        'table_number' => 'int',
        'reservation_date' => 'datetime',
        'reservation_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Reservation status
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SEATED = 'seated';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';
    
    private ?Customer $customer = null;
    
    /**
     * Get customer if registered
     */
    public function getCustomer(): ?Customer
    {
        if ($this->customer === null && $this->customer_id) {
            $this->customer = Customer::find($this->customer_id);
        }
        return $this->customer;
    }
    
    /**
     * Get full reservation datetime
     */
    public function getReservationDateTime(): DateTime
    {
        $date = $this->reservation_date instanceof DateTime 
            ? $this->reservation_date->format('Y-m-d')
            : date('Y-m-d', strtotime($this->reservation_date));
            
        $time = $this->reservation_time instanceof DateTime
            ? $this->reservation_time->format('H:i:s')
            : date('H:i:s', strtotime($this->reservation_time));
            
        return new DateTime($date . ' ' . $time);
    }
    
    /**
     * Check if reservation is today
     */
    public function isToday(): bool
    {
        $reservationDate = $this->reservation_date instanceof DateTime
            ? $this->reservation_date
            : new DateTime($this->reservation_date);
            
        $today = new DateTime();
        return $reservationDate->format('Y-m-d') === $today->format('Y-m-d');
    }
    
    /**
     * Check if reservation is in the past
     */
    public function isPast(): bool
    {
        $reservationDateTime = $this->getReservationDateTime();
        $now = new DateTime();
        return $reservationDateTime < $now;
    }
    
    /**
     * Check if reservation is upcoming (within next 2 hours)
     */
    public function isUpcoming(): bool
    {
        $reservationDateTime = $this->getReservationDateTime();
        $now = new DateTime();
        $twoHoursFromNow = clone $now;
        $twoHoursFromNow->add(new \DateInterval('PT2H'));
        
        return $reservationDateTime >= $now && $reservationDateTime <= $twoHoursFromNow;
    }
    
    /**
     * Get customer display name
     */
    public function getCustomerDisplayName(): string
    {
        $customer = $this->getCustomer();
        if ($customer) {
            return $customer->getFullName();
        }
        return $this->customer_name ?: 'Unknown';
    }
    
    /**
     * Get customer contact email
     */
    public function getCustomerEmail(): string
    {
        $customer = $this->getCustomer();
        if ($customer) {
            return $customer->getEmail();
        }
        return $this->customer_email ?: '';
    }
    
    /**
     * Update reservation status
     */
    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }
    
    /**
     * Confirm reservation
     */
    public function confirm(): void
    {
        $this->updateStatus(self::STATUS_CONFIRMED);
    }
    
    /**
     * Cancel reservation
     */
    public function cancel(string $reason = ''): void
    {
        $this->status = self::STATUS_CANCELLED;
        if ($reason) {
            $this->notes = ($this->notes ?: '') . "\nCancellation reason: " . $reason;
        }
        $this->save();
    }
    
    /**
     * Mark as seated
     */
    public function markSeated(int $tableNumber = null): void
    {
        $this->status = self::STATUS_SEATED;
        if ($tableNumber) {
            $this->table_number = $tableNumber;
        }
        $this->save();
    }
    
    /**
     * Mark as no-show
     */
    public function markNoShow(): void
    {
        $this->updateStatus(self::STATUS_NO_SHOW);
    }
    
    /**
     * Complete reservation
     */
    public function complete(): void
    {
        $this->updateStatus(self::STATUS_COMPLETED);
    }
    
    /**
     * Get reservations by date
     */
    public static function getByDate(string $date): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE DATE(reservation_date) = ? ORDER BY reservation_time",
            [$date]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get today's reservations
     */
    public static function getTodaysReservations(): array
    {
        return static::getByDate(date('Y-m-d'));
    }
    
    /**
     * Get reservations by status
     */
    public static function getByStatus(string $status): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE status = ? ORDER BY reservation_date, reservation_time",
            [$status]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get customer reservations
     */
    public static function getByCustomer(int $customerId): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE customer_id = ? ORDER BY reservation_date DESC",
            [$customerId]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Get upcoming reservations
     */
    public static function getUpcoming(int $hours = 2): array
    {
        $instance = new static();
        $now = new DateTime();
        $future = clone $now;
        $future->add(new \DateInterval("PT{$hours}H"));
        
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} 
             WHERE CONCAT(reservation_date, ' ', reservation_time) BETWEEN ? AND ?
             AND status IN (?, ?)
             ORDER BY reservation_date, reservation_time",
            [
                $now->format('Y-m-d H:i:s'),
                $future->format('Y-m-d H:i:s'),
                self::STATUS_PENDING,
                self::STATUS_CONFIRMED
            ]
        );
        
        return static::createCollection($rows);
    }
    
    /**
     * Check for conflicts with existing reservations
     */
    public function hasConflicts(): bool
    {
        $reservationDateTime = $this->getReservationDateTime();
        $startTime = clone $reservationDateTime;
        $startTime->sub(new \DateInterval('PT1H')); // 1 hour buffer
        $endTime = clone $reservationDateTime;
        $endTime->add(new \DateInterval('PT1H')); // 1 hour buffer
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE CONCAT(reservation_date, ' ', reservation_time) BETWEEN ? AND ?
                AND status IN (?, ?)";
        $params = [
            $startTime->format('Y-m-d H:i:s'),
            $endTime->format('Y-m-d H:i:s'),
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED
        ];
        
        if ($this->exists) {
            $sql .= " AND reservation_id != ?";
            $params[] = $this->reservation_id;
        }
        
        $result = $this->db->fetchRow($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Create collection from rows
     */
    protected static function createCollection(array $rows): array
    {
        $reservations = [];
        foreach ($rows as $row) {
            $reservation = new static($row);
            $reservation->exists = true;
            $reservation->original = $reservation->attributes;
            $reservations[] = $reservation;
        }
        return $reservations;
    }
    
    /**
     * Validate reservation data
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate customer information
        if (empty($this->customer_name)) {
            $errors['customer_name'][] = 'Customer name is required';
        }
        
        if (empty($this->customer_phone)) {
            $errors['customer_phone'][] = 'Customer phone is required';
        }
        
        // Validate party size
        if (empty($this->party_size) || $this->party_size < 1) {
            $errors['party_size'][] = 'Party size must be at least 1';
        } elseif ($this->party_size > 20) {
            $errors['party_size'][] = 'Party size cannot exceed 20';
        }
        
        // Validate reservation date/time
        if (empty($this->reservation_date)) {
            $errors['reservation_date'][] = 'Reservation date is required';
        } else {
            try {
                $reservationDateTime = $this->getReservationDateTime();
                $now = new DateTime();
                
                if ($reservationDateTime < $now) {
                    $errors['reservation_date'][] = 'Cannot make reservations in the past';
                }
                
                // Check if too far in advance (30 days)
                $maxAdvance = clone $now;
                $maxAdvance->add(new \DateInterval('P30D'));
                if ($reservationDateTime > $maxAdvance) {
                    $errors['reservation_date'][] = 'Cannot make reservations more than 30 days in advance';
                }
                
            } catch (\Exception $e) {
                $errors['reservation_date'][] = 'Invalid date/time format';
            }
        }
        
        // Validate status
        $validStatuses = [
            self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SEATED,
            self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_NO_SHOW
        ];
        if (!empty($this->status) && !in_array($this->status, $validStatuses)) {
            $errors['status'][] = 'Invalid status';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Reservation validation failed', $errors);
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
}