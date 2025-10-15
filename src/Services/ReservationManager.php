<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\Reservation;
use RestaurantMS\Models\Customer;
use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * Reservation Manager - Simple reservation business logic
 * 
 * Handles reservation creation, management, and scheduling
 */
class ReservationManager
{
    /**
     * Create new reservation
     */
    public function createReservation(array $data): Reservation
    {
        // If customer_id provided, get customer info
        if (!empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
            if ($customer) {
                $user = $customer->getUser();
                if ($user) {
                    $data['customer_name'] = $user->getFullName();
                    $data['customer_email'] = $user->email;
                    $data['customer_phone'] = $user->phone;
                }
            }
        }
        
        $reservation = new Reservation($data);
        
        // Check for conflicts
        if ($reservation->hasConflicts()) {
            throw new ValidationException('Reservation time conflicts with existing reservation');
        }
        
        $reservation->save();
        return $reservation;
    }
    
    /**
     * Update reservation
     */
    public function updateReservation(int $reservationId, array $data): Reservation
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            throw new ValidationException('Reservation not found');
        }
        
        $reservation->fill($data);
        
        // Check for conflicts if date/time changed
        if (isset($data['reservation_date']) || isset($data['reservation_time'])) {
            if ($reservation->hasConflicts()) {
                throw new ValidationException('Updated time conflicts with existing reservation');
            }
        }
        
        $reservation->save();
        return $reservation;
    }
    
    /**
     * Cancel reservation
     */
    public function cancelReservation(int $reservationId, string $reason = ''): void
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            throw new ValidationException('Reservation not found');
        }
        
        $reservation->cancel($reason);
    }
    
    /**
     * Confirm reservation
     */
    public function confirmReservation(int $reservationId): void
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            throw new ValidationException('Reservation not found');
        }
        
        $reservation->confirm();
    }
    
    /**
     * Mark reservation as seated
     */
    public function seatReservation(int $reservationId, int $tableNumber): void
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            throw new ValidationException('Reservation not found');
        }
        
        $reservation->markSeated($tableNumber);
    }
    
    /**
     * Mark as no-show
     */
    public function markNoShow(int $reservationId): void
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            throw new ValidationException('Reservation not found');
        }
        
        $reservation->markNoShow();
    }
    
    /**
     * Get reservations for a specific date
     */
    public function getReservationsByDate(string $date): array
    {
        return Reservation::getByDate($date);
    }
    
    /**
     * Get today's reservations
     */
    public function getTodaysReservations(): array
    {
        return Reservation::getTodaysReservations();
    }
    
    /**
     * Get upcoming reservations
     */
    public function getUpcomingReservations(int $hours = 2): array
    {
        return Reservation::getUpcoming($hours);
    }
    
    /**
     * Get reservations by status
     */
    public function getReservationsByStatus(string $status): array
    {
        return Reservation::getByStatus($status);
    }
    
    /**
     * Get customer's reservations
     */
    public function getCustomerReservations(int $customerId): array
    {
        return Reservation::getByCustomer($customerId);
    }
    
    /**
     * Check table availability
     */
    public function checkTableAvailability(string $date, string $time, int $partySize): array
    {
        $db = \RestaurantMS\Core\Database::getInstance();
        
        // Get all tables with sufficient capacity
        $availableTables = $db->fetchAll(
            "SELECT table_id, table_number, capacity 
             FROM restaurant_tables 
             WHERE capacity >= ? AND is_active = 1",
            [$partySize]
        );
        
        // Check which tables are already reserved at this time
        $reservedTables = $db->fetchAll(
            "SELECT DISTINCT table_number 
             FROM reservations 
             WHERE DATE(reservation_date) = ? 
             AND TIME(reservation_time) BETWEEN DATE_SUB(?, INTERVAL 2 HOUR) AND DATE_ADD(?, INTERVAL 2 HOUR)
             AND status IN (?, ?)",
            [$date, $time, $time, Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED]
        );
        
        $reservedTableNumbers = array_column($reservedTables, 'table_number');
        
        // Filter out reserved tables
        $available = array_filter($availableTables, function($table) use ($reservedTableNumbers) {
            return !in_array($table['table_number'], $reservedTableNumbers);
        });
        
        return array_values($available);
    }
    
    /**
     * Get reservation statistics
     */
    public function getReservationStatistics(): array
    {
        $db = \RestaurantMS\Core\Database::getInstance();
        
        // Today's stats
        $todayStats = $db->fetchRow(
            "SELECT 
                COUNT(*) as total_reservations,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as confirmed_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as seated_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as no_show_count
             FROM reservations 
             WHERE DATE(reservation_date) = CURDATE()",
            [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_SEATED,
                Reservation::STATUS_COMPLETED,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_NO_SHOW
            ]
        );
        
        // Upcoming reservations (next 4 hours)
        $upcomingCount = $db->fetchRow(
            "SELECT COUNT(*) as count 
             FROM reservations 
             WHERE CONCAT(reservation_date, ' ', reservation_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 4 HOUR)
             AND status IN (?, ?)",
            [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED]
        );
        
        // Weekly reservation trend
        $weeklyTrend = $db->fetchAll(
            "SELECT 
                DATE(reservation_date) as date,
                COUNT(*) as reservations
             FROM reservations 
             WHERE reservation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(reservation_date)
             ORDER BY date"
        );
        
        return [
            'today' => [
                'total' => (int)$todayStats['total_reservations'],
                'pending' => (int)$todayStats['pending_count'],
                'confirmed' => (int)$todayStats['confirmed_count'],
                'seated' => (int)$todayStats['seated_count'],
                'completed' => (int)$todayStats['completed_count'],
                'cancelled' => (int)$todayStats['cancelled_count'],
                'no_show' => (int)$todayStats['no_show_count']
            ],
            'upcoming_count' => (int)$upcomingCount['count'],
            'weekly_trend' => $weeklyTrend
        ];
    }
    
    /**
     * Auto-confirm reservations (for scheduled task)
     */
    public function autoConfirmReservations(): int
    {
        $db = \RestaurantMS\Core\Database::getInstance();
        
        // Auto-confirm reservations made more than 1 hour ago
        $affected = $db->update(
            'reservations',
            ['status' => Reservation::STATUS_CONFIRMED],
            ['status' => Reservation::STATUS_PENDING]
        );
        
        // Additional condition with raw SQL for time check
        $affected = $db->query(
            "UPDATE reservations 
             SET status = ? 
             WHERE status = ? 
             AND created_at <= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PENDING]
        )->rowCount();
        
        return $affected;
    }
    
    /**
     * Mark overdue reservations as no-show
     */
    public function markOverdueAsNoShow(): int
    {
        $db = \RestaurantMS\Core\Database::getInstance();
        
        // Mark as no-show if 30 minutes past reservation time
        $affected = $db->query(
            "UPDATE reservations 
             SET status = ? 
             WHERE status IN (?, ?) 
             AND CONCAT(reservation_date, ' ', reservation_time) <= DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            [
                Reservation::STATUS_NO_SHOW,
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED
            ]
        )->rowCount();
        
        return $affected;
    }
}