<?php

namespace RestaurantMS\Services;

use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;

/**
 * Customer Statistics Service
 * 
 * Handles calculation and retrieval of customer-related statistics
 * Provides analytics and reporting functionality
 */
class CustomerStats
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get comprehensive customer statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as new_this_quarter,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 365 DAY) THEN 1 END) as new_this_year
                FROM users 
                WHERE user_type = 'customer'
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get customer statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get loyalty tier distribution
     * 
     * @return array
     */
    public function getLoyaltyTierDistribution(): array
    {
        try {
            $query = "
                SELECT 
                    loyalty_tier,
                    COUNT(*) as count
                FROM customer_profiles
                GROUP BY loyalty_tier
            ";
            
            $results = $this->db->fetchAll($query);
            $distribution = [];
            
            foreach ($results as $row) {
                $distribution[$row['loyalty_tier']] = (int) $row['count'];
            }
            
            return $distribution;
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get loyalty tier distribution: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer growth statistics by period
     * 
     * @param string $period (daily, weekly, monthly, yearly)
     * @param int $limit
     * @return array
     */
    public function getCustomerGrowthStats(string $period = 'monthly', int $limit = 12): array
    {
        try {
            $dateFormat = $this->getDateFormatForPeriod($period);
            
            $query = "
                SELECT 
                    DATE_FORMAT(created_at, :date_format) as period,
                    COUNT(*) as new_customers
                FROM users 
                WHERE user_type = 'customer'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL :interval_days DAY)
                GROUP BY DATE_FORMAT(created_at, :date_format)
                ORDER BY period DESC
                LIMIT :limit
            ";
            
            $params = [
                ':date_format' => $dateFormat,
                ':interval_days' => $this->getIntervalDaysForPeriod($period, $limit),
                ':limit' => $limit
            ];
            
            return $this->db->fetchAll($query, $params);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get customer growth stats: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer spending statistics
     * 
     * @return array
     */
    public function getSpendingStatistics(): array
    {
        try {
            $query = "
                SELECT 
                    AVG(cp.total_spent) as avg_spending,
                    MIN(cp.total_spent) as min_spending,
                    MAX(cp.total_spent) as max_spending,
                    SUM(cp.total_spent) as total_revenue,
                    COUNT(CASE WHEN cp.total_spent > 0 THEN 1 END) as customers_with_purchases,
                    COUNT(CASE WHEN cp.total_spent = 0 THEN 1 END) as customers_without_purchases
                FROM customer_profiles cp
                JOIN users u ON cp.user_id = u.id
                WHERE u.user_type = 'customer'
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get spending statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer activity statistics
     * 
     * @return array
     */
    public function getActivityStatistics(): array
    {
        try {
            $query = "
                SELECT 
                    AVG(cp.visit_count) as avg_visits,
                    MAX(cp.visit_count) as max_visits,
                    COUNT(CASE WHEN cp.visit_count = 0 THEN 1 END) as inactive_customers,
                    COUNT(CASE WHEN cp.visit_count > 0 THEN 1 END) as active_customers,
                    COUNT(CASE WHEN cp.last_visit >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_visitors
                FROM customer_profiles cp
                JOIN users u ON cp.user_id = u.id
                WHERE u.user_type = 'customer'
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get activity statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get top performing customers
     * 
     * @param int $limit
     * @return array
     */
    public function getTopPerformingCustomers(int $limit = 10): array
    {
        try {
            $query = "
                SELECT 
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    cp.loyalty_points,
                    cp.total_spent,
                    cp.visit_count,
                    cp.loyalty_tier,
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT r.id) as total_reviews
                FROM users u
                JOIN customer_profiles cp ON u.id = cp.user_id
                LEFT JOIN orders o ON u.id = o.customer_id
                LEFT JOIN reviews r ON u.id = r.customer_id
                WHERE u.user_type = 'customer'
                GROUP BY u.id, cp.loyalty_points, cp.total_spent, cp.visit_count, cp.loyalty_tier
                ORDER BY cp.total_spent DESC, cp.loyalty_points DESC
                LIMIT :limit
            ";
            
            return $this->db->fetchAll($query, [':limit' => $limit]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get top performing customers: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer demographics
     * 
     * @return array
     */
    public function getCustomerDemographics(): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(CASE WHEN cp.date_of_birth IS NOT NULL THEN 1 END) as customers_with_age,
                    AVG(YEAR(CURDATE()) - YEAR(cp.date_of_birth)) as avg_age,
                    MIN(YEAR(CURDATE()) - YEAR(cp.date_of_birth)) as min_age,
                    MAX(YEAR(CURDATE()) - YEAR(cp.date_of_birth)) as max_age,
                    COUNT(CASE WHEN cp.city IS NOT NULL AND cp.city != '' THEN 1 END) as customers_with_location
                FROM customer_profiles cp
                JOIN users u ON cp.user_id = u.id
                WHERE u.user_type = 'customer'
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get customer demographics: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer retention statistics
     * 
     * @return array
     */
    public function getRetentionStatistics(): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(CASE WHEN cp.last_visit >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_7_days,
                    COUNT(CASE WHEN cp.last_visit >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as active_30_days,
                    COUNT(CASE WHEN cp.last_visit >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as active_90_days,
                    COUNT(CASE WHEN cp.last_visit < DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as inactive_90_days
                FROM customer_profiles cp
                JOIN users u ON cp.user_id = u.id
                WHERE u.user_type = 'customer'
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get retention statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get comprehensive dashboard statistics
     * 
     * @return array
     */
    public function getDashboardStatistics(): array
    {
        return [
            'basic_stats' => $this->getStatistics(),
            'loyalty_distribution' => $this->getLoyaltyTierDistribution(),
            'spending_stats' => $this->getSpendingStatistics(),
            'activity_stats' => $this->getActivityStatistics(),
            'retention_stats' => $this->getRetentionStatistics(),
            'demographics' => $this->getCustomerDemographics(),
            'top_customers' => $this->getTopPerformingCustomers(5)
        ];
    }
    
    /**
     * Get date format for period grouping
     * 
     * @param string $period
     * @return string
     */
    private function getDateFormatForPeriod(string $period): string
    {
        switch ($period) {
            case 'daily':
                return '%Y-%m-%d';
            case 'weekly':
                return '%Y-%u';
            case 'monthly':
                return '%Y-%m';
            case 'yearly':
                return '%Y';
            default:
                return '%Y-%m';
        }
    }
    
    /**
     * Get interval days for period
     * 
     * @param string $period
     * @param int $limit
     * @return int
     */
    private function getIntervalDaysForPeriod(string $period, int $limit): int
    {
        switch ($period) {
            case 'daily':
                return $limit;
            case 'weekly':
                return $limit * 7;
            case 'monthly':
                return $limit * 30;
            case 'yearly':
                return $limit * 365;
            default:
                return $limit * 30;
        }
    }
}
