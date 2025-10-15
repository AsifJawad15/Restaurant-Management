<?php

namespace RestaurantMS\Controllers;

use RestaurantMS\Services\CustomerService;
use RestaurantMS\Services\CustomerView;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Customer Controller - HTTP Request/Response Handler
 * 
 * Handles HTTP requests related to customer management
 * Acts as a controller layer between HTTP and business logic
 */
class CustomerController
{
    private CustomerService $customerService;
    private CustomerView $customerView;
    
    public function __construct()
    {
        $this->customerService = new CustomerService();
        $this->customerView = new CustomerView();
    }
    
    /**
     * Handle customer listing page
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            // Get filter parameters from request
            $filters = $this->getFiltersFromRequest();
            $sortBy = $_GET['sort_by'] ?? 'recent';
            
            // Get customers data
            $customers = $this->customerService->getCustomers($filters, $sortBy);
            
            // Get statistics
            $stats = $this->customerService->getCustomerStatistics();
            $loyaltyTiers = $this->customerService->getLoyaltyTierDistribution();
            
            // Render the view
            $this->customerView->renderIndex([
                'customers' => $customers,
                'stats' => $stats,
                'loyalty_tiers' => $loyaltyTiers,
                'filters' => $filters,
                'sort_by' => $sortBy,
                'success' => $_SESSION['success'] ?? '',
                'error' => $_SESSION['error'] ?? ''
            ]);
            
            // Clear session messages
            unset($_SESSION['success'], $_SESSION['error']);
            
        } catch (DatabaseException $e) {
            $this->handleError("Database error: " . $e->getMessage());
        } catch (ValidationException $e) {
            $this->handleError("Validation error: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->handleError("An unexpected error occurred: " . $e->getMessage());
        }
    }
    
    /**
     * Handle customer deletion
     * 
     * @return void
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToIndex();
            return;
        }
        
        try {
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            
            if ($customerId <= 0) {
                throw new ValidationException("Invalid customer ID");
            }
            
            $success = $this->customerService->deleteCustomer($customerId);
            
            if ($success) {
                $_SESSION['success'] = "Customer deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete customer";
            }
            
        } catch (ValidationException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (DatabaseException $e) {
            $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
        } catch (\Exception $e) {
            $_SESSION['error'] = "An unexpected error occurred: " . $e->getMessage();
        }
        
        $this->redirectToIndex();
    }
    
    /**
     * Handle customer search
     * 
     * @return void
     */
    public function search(): void
    {
        try {
            $searchTerm = $_GET['search'] ?? '';
            
            if (empty($searchTerm)) {
                $this->redirectToIndex();
                return;
            }
            
            $customers = $this->customerService->searchCustomers($searchTerm);
            $stats = $this->customerService->getCustomerStatistics();
            $loyaltyTiers = $this->customerService->getLoyaltyTierDistribution();
            
            $this->customerView->renderIndex([
                'customers' => $customers,
                'stats' => $stats,
                'loyalty_tiers' => $loyaltyTiers,
                'filters' => ['search' => $searchTerm],
                'sort_by' => 'name',
                'success' => '',
                'error' => ''
            ]);
            
        } catch (\Exception $e) {
            $this->handleError("Search error: " . $e->getMessage());
        }
    }
    
    /**
     * Handle customer details view
     * 
     * @param int $customerId
     * @return void
     */
    public function show(int $customerId): void
    {
        try {
            $customer = $this->customerService->getCustomerById($customerId);
            
            if (!$customer) {
                $_SESSION['error'] = "Customer not found";
                $this->redirectToIndex();
                return;
            }
            
            $activitySummary = $this->customerService->getCustomerActivitySummary($customerId);
            
            $this->customerView->renderDetails([
                'customer' => $customer,
                'activity_summary' => $activitySummary
            ]);
            
        } catch (\Exception $e) {
            $this->handleError("Error loading customer details: " . $e->getMessage());
        }
    }
    
    /**
     * Handle loyalty points update
     * 
     * @return void
     */
    public function updateLoyaltyPoints(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToIndex();
            return;
        }
        
        try {
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            $points = (int) ($_POST['points'] ?? 0);
            $action = $_POST['action'] ?? '';
            
            if ($customerId <= 0) {
                throw new ValidationException("Invalid customer ID");
            }
            
            if ($points < 0) {
                throw new ValidationException("Points cannot be negative");
            }
            
            $success = false;
            
            switch ($action) {
                case 'add':
                    $success = $this->customerService->addLoyaltyPoints($customerId, $points);
                    $message = "Loyalty points added successfully!";
                    break;
                case 'redeem':
                    $success = $this->customerService->redeemLoyaltyPoints($customerId, $points);
                    $message = $success ? "Loyalty points redeemed successfully!" : "Insufficient loyalty points";
                    break;
                case 'set':
                    $success = $this->customerService->updateLoyaltyPoints($customerId, $points);
                    $message = "Loyalty points updated successfully!";
                    break;
                default:
                    throw new ValidationException("Invalid action");
            }
            
            if ($success) {
                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = "Failed to update loyalty points";
            }
            
        } catch (ValidationException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $_SESSION['error'] = "Error updating loyalty points: " . $e->getMessage();
        }
        
        $this->redirectToIndex();
    }
    
    /**
     * Handle customer tier update
     * 
     * @return void
     */
    public function updateTier(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToIndex();
            return;
        }
        
        try {
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            $tier = $_POST['tier'] ?? '';
            
            if ($customerId <= 0) {
                throw new ValidationException("Invalid customer ID");
            }
            
            if (empty($tier)) {
                throw new ValidationException("Tier is required");
            }
            
            $success = $this->customerService->updateCustomerTier($customerId, $tier);
            
            if ($success) {
                $_SESSION['success'] = "Customer tier updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update customer tier";
            }
            
        } catch (ValidationException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $_SESSION['error'] = "Error updating customer tier: " . $e->getMessage();
        }
        
        $this->redirectToIndex();
    }
    
    /**
     * Handle customer export
     * 
     * @return void
     */
    public function export(): void
    {
        try {
            $format = $_GET['format'] ?? 'csv';
            $filters = $this->getFiltersFromRequest();
            
            $data = $this->customerService->exportCustomers($filters, $format);
            
            $filename = 'customers_' . date('Y-m-d_H-i-s') . '.' . $format;
            
            header('Content-Type: ' . $this->getContentTypeForFormat($format));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($data));
            
            echo $data;
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = "Export error: " . $e->getMessage();
            $this->redirectToIndex();
        }
    }
    
    /**
     * Handle AJAX requests for customer data
     * 
     * @return void
     */
    public function ajax(): void
    {
        try {
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'stats':
                    $this->handleAjaxStats();
                    break;
                case 'search':
                    $this->handleAjaxSearch();
                    break;
                case 'tier_distribution':
                    $this->handleAjaxTierDistribution();
                    break;
                default:
                    $this->sendJsonResponse(['error' => 'Invalid action'], 400);
            }
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle AJAX stats request
     * 
     * @return void
     */
    private function handleAjaxStats(): void
    {
        $stats = $this->customerService->getCustomerStatistics();
        $this->sendJsonResponse($stats);
    }
    
    /**
     * Handle AJAX search request
     * 
     * @return void
     */
    private function handleAjaxSearch(): void
    {
        $searchTerm = $_GET['q'] ?? '';
        
        if (empty($searchTerm)) {
            $this->sendJsonResponse([]);
            return;
        }
        
        $customers = $this->customerService->searchCustomers($searchTerm);
        $this->sendJsonResponse($customers);
    }
    
    /**
     * Handle AJAX tier distribution request
     * 
     * @return void
     */
    private function handleAjaxTierDistribution(): void
    {
        $distribution = $this->customerService->getLoyaltyTierDistribution();
        $this->sendJsonResponse($distribution);
    }
    
    /**
     * Get filters from request parameters
     * 
     * @return array
     */
    private function getFiltersFromRequest(): array
    {
        return [
            'search' => $_GET['search'] ?? '',
            'loyalty_tier' => $_GET['loyalty_tier'] ?? ''
        ];
    }
    
    /**
     * Handle errors by setting session message and redirecting
     * 
     * @param string $message
     * @return void
     */
    private function handleError(string $message): void
    {
        $_SESSION['error'] = $message;
        $this->redirectToIndex();
    }
    
    /**
     * Redirect to customer index page
     * 
     * @return void
     */
    private function redirectToIndex(): void
    {
        header('Location: customers.php');
        exit;
    }
    
    /**
     * Send JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    private function sendJsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get content type for export format
     * 
     * @param string $format
     * @return string
     */
    private function getContentTypeForFormat(string $format): string
    {
        switch ($format) {
            case 'csv':
                return 'text/csv';
            case 'json':
                return 'application/json';
            default:
                return 'text/plain';
        }
    }
}
