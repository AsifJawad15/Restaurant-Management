<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\Customer;
use RestaurantMS\Models\User;
use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;

/**
 * Customer Repository - Data Access Layer
 * 
 * Handles all database operations related to customers
 * Implements Repository pattern for data access abstraction
 */
class CustomerRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get customers with filters and sorting
     * 
     * @param array $filters
     * @param string $sortBy
     * @return array
     */
    public function getCustomersWithFilters(array $filters = [], string $sortBy = 'recent'): array
    {
        $query = "
            SELECT u.*, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, 
                   cp.loyalty_tier, cp.total_spent,
                   COUNT(DISTINCT o.id) as total_orders,
                   COUNT(DISTINCT r.id) as total_reviews
            FROM users u
            LEFT JOIN customer_profiles cp ON u.id = cp.user_id
            LEFT JOIN orders o ON u.id = o.customer_id
            LEFT JOIN reviews r ON u.id = r.customer_id
            WHERE u.user_type = 'customer'
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['loyalty_tier'])) {
            $query .= " AND cp.loyalty_tier = :loyalty_tier";
            $params[':loyalty_tier'] = $filters['loyalty_tier'];
        }
        
        $query .= " GROUP BY u.id, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, cp.loyalty_tier, cp.total_spent";
        
        // Apply sorting
        $query .= $this->buildSortClause($sortBy);
        
        try {
            return $this->db->fetchAll($query, $params);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch customers: " . $e->getMessage());
        }
    }
    
    /**
     * Find customer by ID
     * 
     * @param int $customerId
     * @return Customer|null
     */
    public function findById(int $customerId): ?Customer
    {
        try {
            $query = "
                SELECT u.*, cp.*
                FROM users u
                LEFT JOIN customer_profiles cp ON u.id = cp.user_id
                WHERE u.id = :id AND u.user_type = 'customer'
            ";
            
            $row = $this->db->fetchRow($query, [':id' => $customerId]);
            
            if ($row) {
                $customer = new Customer();
                $customer->fillFromDatabase($row);
                return $customer;
            }
            
            return null;
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to find customer: " . $e->getMessage());
        }
    }
    
    /**
     * Delete customer and related data
     * 
     * @param int $customerId
     * @return bool
     */
    public function deleteCustomer(int $customerId): bool
    {
        try {
            $this->db->beginTransaction();
            
            // Delete customer (cascade will handle related records)
            $affected = $this->db->delete('users', ['id' => $customerId, 'user_type' => 'customer']);
            
            if ($affected > 0) {
                $this->db->commit();
                return true;
            }
            
            $this->db->rollback();
            return false;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new DatabaseException("Failed to delete customer: " . $e->getMessage());
        }
    }
    
    /**
     * Search customers by criteria
     * 
     * @param string $searchTerm
     * @return array
     */
    public function searchCustomers(string $searchTerm): array
    {
        try {
            $query = "
                SELECT u.*, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, 
                       cp.loyalty_tier, cp.total_spent,
                       COUNT(DISTINCT o.id) as total_orders,
                       COUNT(DISTINCT r.id) as total_reviews
                FROM users u
                LEFT JOIN customer_profiles cp ON u.id = cp.user_id
                LEFT JOIN orders o ON u.id = o.customer_id
                LEFT JOIN reviews r ON u.id = r.customer_id
                WHERE u.user_type = 'customer'
                AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)
                GROUP BY u.id, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, cp.loyalty_tier, cp.total_spent
                ORDER BY u.first_name ASC, u.last_name ASC
            ";
            
            return $this->db->fetchAll($query, [':search' => "%{$searchTerm}%"]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to search customers: " . $e->getMessage());
        }
    }
    
    /**
     * Get customers by loyalty tier
     * 
     * @param string $tier
     * @return array
     */
    public function getCustomersByTier(string $tier): array
    {
        try {
            $query = "
                SELECT u.*, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, 
                       cp.loyalty_tier, cp.total_spent,
                       COUNT(DISTINCT o.id) as total_orders,
                       COUNT(DISTINCT r.id) as total_reviews
                FROM users u
                LEFT JOIN customer_profiles cp ON u.id = cp.user_id
                LEFT JOIN orders o ON u.id = o.customer_id
                LEFT JOIN reviews r ON u.id = r.customer_id
                WHERE u.user_type = 'customer' AND cp.loyalty_tier = :tier
                GROUP BY u.id, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, cp.loyalty_tier, cp.total_spent
                ORDER BY cp.loyalty_points DESC
            ";
            
            return $this->db->fetchAll($query, [':tier' => $tier]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get customers by tier: " . $e->getMessage());
        }
    }
    
    /**
     * Get top spending customers
     * 
     * @param int $limit
     * @return array
     */
    public function getTopSpendingCustomers(int $limit = 10): array
    {
        try {
            $query = "
                SELECT u.*, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, 
                       cp.loyalty_tier, cp.total_spent,
                       COUNT(DISTINCT o.id) as total_orders,
                       COUNT(DISTINCT r.id) as total_reviews
                FROM users u
                LEFT JOIN customer_profiles cp ON u.id = cp.user_id
                LEFT JOIN orders o ON u.id = o.customer_id
                LEFT JOIN reviews r ON u.id = r.customer_id
                WHERE u.user_type = 'customer'
                GROUP BY u.id, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, cp.loyalty_tier, cp.total_spent
                ORDER BY cp.total_spent DESC
                LIMIT :limit
            ";
            
            return $this->db->fetchAll($query, [':limit' => $limit]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get top spending customers: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer activity summary
     * 
     * @param int $customerId
     * @return array
     */
    public function getCustomerActivitySummary(int $customerId): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT r.id) as total_reviews,
                    COUNT(DISTINCT res.id) as total_reservations,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.created_at) as last_order_date,
                    MAX(r.created_at) as last_review_date,
                    MAX(res.reservation_date) as last_reservation_date
                FROM users u
                LEFT JOIN orders o ON u.id = o.customer_id
                LEFT JOIN reviews r ON u.id = r.customer_id
                LEFT JOIN reservations res ON u.id = res.customer_id
                WHERE u.id = :customer_id AND u.user_type = 'customer'
            ";
            
            $result = $this->db->fetchRow($query, [':customer_id' => $customerId]);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get customer activity summary: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer count by date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    public function getCustomerCountByDateRange(string $startDate, string $endDate): int
    {
        try {
            $query = "
                SELECT COUNT(*) as count
                FROM users 
                WHERE user_type = 'customer' 
                AND DATE(created_at) BETWEEN :start_date AND :end_date
            ";
            
            $result = $this->db->fetchRow($query, [
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get customer count by date range: " . $e->getMessage());
        }
    }
    
    /**
     * Build sort clause based on sort parameter
     * 
     * @param string $sortBy
     * @return string
     */
    private function buildSortClause(string $sortBy): string
    {
        switch ($sortBy) {
            case 'name':
                return " ORDER BY u.first_name ASC, u.last_name ASC";
            case 'orders':
                return " ORDER BY total_orders DESC";
            case 'spending':
                return " ORDER BY cp.total_spent DESC";
            case 'points':
                return " ORDER BY cp.loyalty_points DESC";
            case 'recent':
            default:
                return " ORDER BY u.created_at DESC";
        }
    }
    
    /**
     * Get customers with pagination
     * 
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @param string $sortBy
     * @return array
     */
    public function getCustomersPaginated(int $page = 1, int $perPage = 20, array $filters = [], string $sortBy = 'recent'): array
    {
        $offset = ($page - 1) * $perPage;
        
        $query = "
            SELECT u.*, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, 
                   cp.loyalty_tier, cp.total_spent,
                   COUNT(DISTINCT o.id) as total_orders,
                   COUNT(DISTINCT r.id) as total_reviews
            FROM users u
            LEFT JOIN customer_profiles cp ON u.id = cp.user_id
            LEFT JOIN orders o ON u.id = o.customer_id
            LEFT JOIN reviews r ON u.id = r.customer_id
            WHERE u.user_type = 'customer'
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['loyalty_tier'])) {
            $query .= " AND cp.loyalty_tier = :loyalty_tier";
            $params[':loyalty_tier'] = $filters['loyalty_tier'];
        }
        
        $query .= " GROUP BY u.id, cp.address, cp.city, cp.state, cp.zip_code, cp.loyalty_points, cp.loyalty_tier, cp.total_spent";
        $query .= $this->buildSortClause($sortBy);
        $query .= " LIMIT :limit OFFSET :offset";
        
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        try {
            return $this->db->fetchAll($query, $params);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch paginated customers: " . $e->getMessage());
        }
    }
    
    /**
     * Get total customer count with filters
     * 
     * @param array $filters
     * @return int
     */
    public function getTotalCustomerCount(array $filters = []): int
    {
        $query = "SELECT COUNT(DISTINCT u.id) as count FROM users u WHERE u.user_type = 'customer'";
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['loyalty_tier'])) {
            $query .= " AND EXISTS (SELECT 1 FROM customer_profiles cp WHERE cp.user_id = u.id AND cp.loyalty_tier = :loyalty_tier)";
            $params[':loyalty_tier'] = $filters['loyalty_tier'];
        }
        
        try {
            $result = $this->db->fetchRow($query, $params);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get total customer count: " . $e->getMessage());
        }
    }
}
