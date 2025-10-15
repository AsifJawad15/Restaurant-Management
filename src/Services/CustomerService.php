<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\Customer;
use RestaurantMS\Models\User;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Customer Service - Business Logic Layer
 * 
 * Handles customer-related business operations and data processing
 * Acts as a service layer between controllers and models
 */
class CustomerService
{
    private CustomerRepository $customerRepository;
    private CustomerStats $customerStats;
    
    public function __construct()
    {
        $this->customerRepository = new CustomerRepository();
        $this->customerStats = new CustomerStats();
    }
    
    /**
     * Get all customers with filters and sorting
     * 
     * @param array $filters
     * @param string $sortBy
     * @return array
     */
    public function getCustomers(array $filters = [], string $sortBy = 'recent'): array
    {
        return $this->customerRepository->getCustomersWithFilters($filters, $sortBy);
    }
    
    /**
     * Get customer by ID
     * 
     * @param int $customerId
     * @return Customer|null
     */
    public function getCustomerById(int $customerId): ?Customer
    {
        return $this->customerRepository->findById($customerId);
    }
    
    /**
     * Delete customer and all related data
     * 
     * @param int $customerId
     * @return bool
     * @throws DatabaseException
     */
    public function deleteCustomer(int $customerId): bool
    {
        // Get customer first to validate existence
        $customer = $this->getCustomerById($customerId);
        if (!$customer) {
            throw new ValidationException("Customer not found");
        }
        
        // Delete customer (cascade will handle related records)
        return $this->customerRepository->deleteCustomer($customerId);
    }
    
    /**
     * Get customer statistics
     * 
     * @return array
     */
    public function getCustomerStatistics(): array
    {
        return $this->customerStats->getStatistics();
    }
    
    /**
     * Get loyalty tier distribution
     * 
     * @return array
     */
    public function getLoyaltyTierDistribution(): array
    {
        return $this->customerStats->getLoyaltyTierDistribution();
    }
    
    /**
     * Search customers by criteria
     * 
     * @param string $searchTerm
     * @return array
     */
    public function searchCustomers(string $searchTerm): array
    {
        return $this->customerRepository->searchCustomers($searchTerm);
    }
    
    /**
     * Get customers by loyalty tier
     * 
     * @param string $tier
     * @return array
     */
    public function getCustomersByTier(string $tier): array
    {
        return $this->customerRepository->getCustomersByTier($tier);
    }
    
    /**
     * Get top spending customers
     * 
     * @param int $limit
     * @return array
     */
    public function getTopSpendingCustomers(int $limit = 10): array
    {
        return $this->customerRepository->getTopSpendingCustomers($limit);
    }
    
    /**
     * Update customer loyalty points
     * 
     * @param int $customerId
     * @param int $points
     * @return bool
     */
    public function updateLoyaltyPoints(int $customerId, int $points): bool
    {
        $customer = $this->getCustomerById($customerId);
        if (!$customer) {
            throw new ValidationException("Customer not found");
        }
        
        $customer->loyalty_points = $points;
        $customer->updateTierLevel();
        
        return $customer->save();
    }
    
    /**
     * Add loyalty points to customer
     * 
     * @param int $customerId
     * @param int $points
     * @return bool
     */
    public function addLoyaltyPoints(int $customerId, int $points): bool
    {
        $customer = $this->getCustomerById($customerId);
        if (!$customer) {
            throw new ValidationException("Customer not found");
        }
        
        $customer->addLoyaltyPoints($points);
        return $customer->save();
    }
    
    /**
     * Redeem loyalty points from customer
     * 
     * @param int $customerId
     * @param int $points
     * @return bool
     */
    public function redeemLoyaltyPoints(int $customerId, int $points): bool
    {
        $customer = $this->getCustomerById($customerId);
        if (!$customer) {
            throw new ValidationException("Customer not found");
        }
        
        return $customer->redeemLoyaltyPoints($points) && $customer->save();
    }
    
    /**
     * Update customer tier level
     * 
     * @param int $customerId
     * @param string $tier
     * @return bool
     */
    public function updateCustomerTier(int $customerId, string $tier): bool
    {
        $customer = $this->getCustomerById($customerId);
        if (!$customer) {
            throw new ValidationException("Customer not found");
        }
        
        $validTiers = [Customer::TIER_BRONZE, Customer::TIER_SILVER, Customer::TIER_GOLD, Customer::TIER_PLATINUM];
        if (!in_array($tier, $validTiers)) {
            throw new ValidationException("Invalid tier level");
        }
        
        $customer->tier_level = $tier;
        return $customer->save();
    }
    
    /**
     * Get customer activity summary
     * 
     * @param int $customerId
     * @return array
     */
    public function getCustomerActivitySummary(int $customerId): array
    {
        return $this->customerRepository->getCustomerActivitySummary($customerId);
    }
    
    /**
     * Export customers data
     * 
     * @param array $filters
     * @param string $format
     * @return string
     */
    public function exportCustomers(array $filters = [], string $format = 'csv'): string
    {
        $customers = $this->getCustomers($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($customers);
            case 'json':
                return json_encode($customers);
            default:
                throw new ValidationException("Unsupported export format");
        }
    }
    
    /**
     * Export customers to CSV format
     * 
     * @param array $customers
     * @return string
     */
    private function exportToCsv(array $customers): string
    {
        $output = fopen('php://temp', 'r+');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Phone', 'City', 'State', 
            'Loyalty Tier', 'Points', 'Total Spent', 'Orders', 'Reviews', 'Joined'
        ]);
        
        // CSV data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['first_name'] . ' ' . $customer['last_name'],
                $customer['email'],
                $customer['phone'] ?? 'N/A',
                $customer['city'] ?? 'N/A',
                $customer['state'] ?? 'N/A',
                $customer['loyalty_tier'] ?? 'bronze',
                $customer['loyalty_points'] ?? 0,
                $customer['total_spent'] ?? 0,
                $customer['total_orders'] ?? 0,
                $customer['total_reviews'] ?? 0,
                $customer['created_at']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
