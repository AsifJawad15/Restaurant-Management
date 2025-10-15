<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\Order;
use RestaurantMS\Models\Customer;
use RestaurantMS\Models\MenuItem;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Order Manager - Simple order business logic
 * 
 * Handles order creation, management, and calculations
 */
class OrderManager
{
    /**
     * Create new order
     */
    public function createOrder(int $customerId, string $orderType = Order::TYPE_DINE_IN): Order
    {
        // Validate customer exists
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new ValidationException('Customer not found');
        }
        
        $order = Order::create([
            'customer_id' => $customerId,
            'order_type' => $orderType,
            'status' => Order::STATUS_PENDING,
            'payment_status' => Order::PAYMENT_PENDING,
            'total_amount' => 0.00
        ]);
        
        return $order;
    }
    
    /**
     * Add item to order
     */
    public function addItemToOrder(int $orderId, int $itemId, int $quantity, ?string $notes = null): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new ValidationException('Order not found');
        }
        
        $menuItem = MenuItem::find($itemId);
        if (!$menuItem || !$menuItem->isAvailable()) {
            throw new ValidationException('Menu item not available');
        }
        
        $order->addItem($itemId, $quantity, $notes);
    }
    
    /**
     * Calculate order total
     */
    public function calculateOrderTotal(int $orderId): float
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new ValidationException('Order not found');
        }
        
        $order->calculateTotal();
        $order->save();
        
        return $order->total_amount;
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus(int $orderId, string $status): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new ValidationException('Order not found');
        }
        
        $order->updateStatus($status);
    }
    
    /**
     * Complete order
     */
    public function completeOrder(int $orderId): void
    {
        $this->updateOrderStatus($orderId, Order::STATUS_COMPLETED);
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId, string $reason = ''): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new ValidationException('Order not found');
        }
        
        $order->cancel($reason);
    }
    
    /**
     * Get orders by status
     */
    public function getOrdersByStatus(string $status): array
    {
        return Order::getByStatus($status);
    }
    
    /**
     * Get today's orders
     */
    public function getTodaysOrders(): array
    {
        return Order::getTodaysOrders();
    }
    
    /**
     * Get customer's order history
     */
    public function getCustomerOrders(int $customerId, int $limit = 10): array
    {
        return Order::getByCustomer($customerId, $limit);
    }
    
    /**
     * Get order statistics
     */
    public function getOrderStatistics(): array
    {
        $db = \RestaurantMS\Core\Database::getInstance();
        
        // Today's stats
        $todayStats = $db->fetchRow(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value
             FROM orders 
             WHERE DATE(created_at) = CURDATE()"
        );
        
        // Status breakdown
        $statusBreakdown = $db->fetchAll(
            "SELECT status, COUNT(*) as count 
             FROM orders 
             WHERE DATE(created_at) = CURDATE()
             GROUP BY status"
        );
        
        // Popular items today
        $popularItems = $db->fetchAll(
            "SELECT 
                mi.name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_revenue
             FROM order_items oi
             JOIN menu_items mi ON oi.item_id = mi.item_id
             JOIN orders o ON oi.order_id = o.order_id
             WHERE DATE(o.created_at) = CURDATE()
             GROUP BY oi.item_id, mi.name
             ORDER BY total_quantity DESC
             LIMIT 5"
        );
        
        return [
            'today' => [
                'total_orders' => (int)$todayStats['total_orders'],
                'total_revenue' => (float)$todayStats['total_revenue'],
                'average_order_value' => round((float)$todayStats['average_order_value'], 2)
            ],
            'status_breakdown' => $statusBreakdown,
            'popular_items' => $popularItems
        ];
    }
}