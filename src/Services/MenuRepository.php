<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\MenuItem;
use RestaurantMS\Models\Category;
use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;

/**
 * Menu Repository - Data Access Layer
 * 
 * Handles all database operations related to menu items
 */
class MenuRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get menu items with filters
     * 
     * @param array $filters
     * @return array
     */
    public function getMenuItemsWithFilters(array $filters = []): array
    {
        try {
            $query = "
                SELECT m.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.id) as review_count,
                       COUNT(oi.id) as order_count
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN reviews r ON m.id = r.menu_item_id
                LEFT JOIN order_items oi ON m.id = oi.menu_item_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $query .= " AND m.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (m.name LIKE :search OR m.description LIKE :search)";
                $params[':search'] = "%{$filters['search']}%";
            }
            
            if (isset($filters['is_available'])) {
                $query .= " AND m.is_available = :is_available";
                $params[':is_available'] = $filters['is_available'] ? 1 : 0;
            }
            
            if (isset($filters['is_featured'])) {
                $query .= " AND m.is_featured = :is_featured";
                $params[':is_featured'] = $filters['is_featured'] ? 1 : 0;
            }
            
            $query .= " GROUP BY m.id, c.name";
            
            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'name';
            switch ($sortBy) {
                case 'price_asc':
                    $query .= " ORDER BY m.price ASC";
                    break;
                case 'price_desc':
                    $query .= " ORDER BY m.price DESC";
                    break;
                case 'rating':
                    $query .= " ORDER BY avg_rating DESC";
                    break;
                case 'popularity':
                    $query .= " ORDER BY order_count DESC";
                    break;
                case 'created':
                    $query .= " ORDER BY m.created_at DESC";
                    break;
                case 'name':
                default:
                    $query .= " ORDER BY m.name ASC";
                    break;
            }
            
            return $this->db->fetchAll($query, $params);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch menu items: " . $e->getMessage());
        }
    }
    
    /**
     * Find menu item by ID
     * 
     * @param int $itemId
     * @return MenuItem|null
     */
    public function findById(int $itemId): ?MenuItem
    {
        try {
            $query = "
                SELECT m.*, c.name as category_name
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.id = :id
            ";
            
            $row = $this->db->fetchRow($query, [':id' => $itemId]);
            
            if ($row) {
                $menuItem = new MenuItem();
                $menuItem->fillFromDatabase($row);
                return $menuItem;
            }
            
            return null;
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to find menu item: " . $e->getMessage());
        }
    }
    
    /**
     * Delete menu item
     * 
     * @param int $itemId
     * @return bool
     */
    public function deleteMenuItem(int $itemId): bool
    {
        try {
            $this->db->beginTransaction();
            
            // Check if menu item has orders
            $orderCount = $this->db->fetchRow(
                "SELECT COUNT(*) as count FROM order_items WHERE menu_item_id = :id",
                [':id' => $itemId]
            )['count'];
            
            if ($orderCount > 0) {
                // Soft delete - mark as unavailable instead
                $affected = $this->db->update(
                    'menu_items',
                    ['is_available' => 0],
                    ['id' => $itemId]
                );
            } else {
                // Hard delete if no orders
                $affected = $this->db->delete('menu_items', ['id' => $itemId]);
            }
            
            $this->db->commit();
            return $affected > 0;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new DatabaseException("Failed to delete menu item: " . $e->getMessage());
        }
    }
    
    /**
     * Search menu items
     * 
     * @param string $searchTerm
     * @return array
     */
    public function searchMenuItems(string $searchTerm): array
    {
        try {
            $query = "
                SELECT m.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.id) as review_count
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN reviews r ON m.id = r.menu_item_id
                WHERE m.is_available = 1
                AND (m.name LIKE :search OR m.description LIKE :search OR c.name LIKE :search)
                GROUP BY m.id, c.name
                ORDER BY m.name ASC
            ";
            
            return $this->db->fetchAll($query, [':search' => "%{$searchTerm}%"]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to search menu items: " . $e->getMessage());
        }
    }
    
    /**
     * Get menu items by category
     * 
     * @param int $categoryId
     * @return array
     */
    public function getMenuItemsByCategory(int $categoryId): array
    {
        try {
            $query = "
                SELECT m.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.id) as review_count
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN reviews r ON m.id = r.menu_item_id
                WHERE m.category_id = :category_id AND m.is_available = 1
                GROUP BY m.id, c.name
                ORDER BY m.name ASC
            ";
            
            return $this->db->fetchAll($query, [':category_id' => $categoryId]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get menu items by category: " . $e->getMessage());
        }
    }
    
    /**
     * Get popular menu items
     * 
     * @param int $limit
     * @return array
     */
    public function getPopularMenuItems(int $limit = 10): array
    {
        try {
            $query = "
                SELECT m.*, c.name as category_name,
                       COUNT(oi.id) as order_count,
                       SUM(oi.quantity) as total_quantity,
                       COALESCE(AVG(r.rating), 0) as avg_rating
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN order_items oi ON m.id = oi.menu_item_id
                LEFT JOIN reviews r ON m.id = r.menu_item_id
                WHERE m.is_available = 1
                GROUP BY m.id, c.name
                ORDER BY total_quantity DESC, avg_rating DESC
                LIMIT :limit
            ";
            
            return $this->db->fetchAll($query, [':limit' => $limit]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get popular menu items: " . $e->getMessage());
        }
    }
    
    /**
     * Get menu statistics
     * 
     * @return array
     */
    public function getMenuStatistics(): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_items,
                    COUNT(CASE WHEN is_available = 1 THEN 1 END) as available_items,
                    COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_items,
                    AVG(price) as avg_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(DISTINCT category_id) as total_categories
                FROM menu_items
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get menu statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get menu items with pagination
     * 
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getMenuItemsPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        $query = "
            SELECT m.*, c.name as category_name,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.id) as review_count
            FROM menu_items m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN reviews r ON m.id = r.menu_item_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND m.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (m.name LIKE :search OR m.description LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (isset($filters['is_available'])) {
            $query .= " AND m.is_available = :is_available";
            $params[':is_available'] = $filters['is_available'] ? 1 : 0;
        }
        
        $query .= " GROUP BY m.id, c.name";
        $query .= " ORDER BY m.name ASC";
        $query .= " LIMIT :limit OFFSET :offset";
        
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        try {
            return $this->db->fetchAll($query, $params);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch paginated menu items: " . $e->getMessage());
        }
    }
    
    /**
     * Get total menu item count with filters
     * 
     * @param array $filters
     * @return int
     */
    public function getTotalMenuItemCount(array $filters = []): int
    {
        $query = "SELECT COUNT(*) as count FROM menu_items WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (isset($filters['is_available'])) {
            $query .= " AND is_available = :is_available";
            $params[':is_available'] = $filters['is_available'] ? 1 : 0;
        }
        
        try {
            $result = $this->db->fetchRow($query, $params);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get total menu item count: " . $e->getMessage());
        }
    }
}
