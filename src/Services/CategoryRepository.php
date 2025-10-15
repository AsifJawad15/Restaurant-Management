<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\Category;
use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;

/**
 * Category Repository - Data Access Layer
 * 
 * Handles all database operations related to categories
 */
class CategoryRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all categories
     * 
     * @return array
     */
    public function getAllCategories(): array
    {
        try {
            $query = "
                SELECT c.*, COUNT(m.id) as menu_item_count
                FROM categories c
                LEFT JOIN menu_items m ON c.id = m.category_id
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ";
            
            return $this->db->fetchAll($query);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch categories: " . $e->getMessage());
        }
    }
    
    /**
     * Get active categories
     * 
     * @return array
     */
    public function getActiveCategories(): array
    {
        try {
            $query = "
                SELECT c.*, COUNT(m.id) as menu_item_count
                FROM categories c
                LEFT JOIN menu_items m ON c.id = m.category_id AND m.is_available = 1
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ";
            
            return $this->db->fetchAll($query);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch active categories: " . $e->getMessage());
        }
    }
    
    /**
     * Find category by ID
     * 
     * @param int $categoryId
     * @return Category|null
     */
    public function findById(int $categoryId): ?Category
    {
        try {
            $query = "
                SELECT c.*, COUNT(m.id) as menu_item_count
                FROM categories c
                LEFT JOIN menu_items m ON c.id = m.category_id
                WHERE c.id = :id
                GROUP BY c.id
            ";
            
            $row = $this->db->fetchRow($query, [':id' => $categoryId]);
            
            if ($row) {
                $category = new Category();
                $category->fillFromDatabase($row);
                return $category;
            }
            
            return null;
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to find category: " . $e->getMessage());
        }
    }
    
    /**
     * Find category by name
     * 
     * @param string $name
     * @return Category|null
     */
    public function findByName(string $name): ?Category
    {
        try {
            $query = "SELECT * FROM categories WHERE name = :name";
            $row = $this->db->fetchRow($query, [':name' => $name]);
            
            if ($row) {
                $category = new Category();
                $category->fillFromDatabase($row);
                return $category;
            }
            
            return null;
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to find category by name: " . $e->getMessage());
        }
    }
    
    /**
     * Delete category
     * 
     * @param int $categoryId
     * @return bool
     */
    public function deleteCategory(int $categoryId): bool
    {
        try {
            $affected = $this->db->delete('categories', ['id' => $categoryId]);
            return $affected > 0;
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to delete category: " . $e->getMessage());
        }
    }
    
    /**
     * Get menu item count for category
     * 
     * @param int $categoryId
     * @return int
     */
    public function getMenuItemCount(int $categoryId): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = :category_id";
            $result = $this->db->fetchRow($query, [':category_id' => $categoryId]);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get menu item count: " . $e->getMessage());
        }
    }
    
    /**
     * Get category statistics
     * 
     * @return array
     */
    public function getCategoryStatistics(): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_categories,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_categories,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_categories,
                    AVG(menu_item_count) as avg_items_per_category
                FROM (
                    SELECT c.*, COUNT(m.id) as menu_item_count
                    FROM categories c
                    LEFT JOIN menu_items m ON c.id = m.category_id
                    GROUP BY c.id
                ) as category_stats
            ";
            
            $result = $this->db->fetchRow($query);
            return $result ?: [];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get category statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get categories with pagination
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getCategoriesPaginated(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        try {
            $query = "
                SELECT c.*, COUNT(m.id) as menu_item_count
                FROM categories c
                LEFT JOIN menu_items m ON c.id = m.category_id
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
                LIMIT :limit OFFSET :offset
            ";
            
            return $this->db->fetchAll($query, [
                ':limit' => $perPage,
                ':offset' => $offset
            ]);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to fetch paginated categories: " . $e->getMessage());
        }
    }
    
    /**
     * Get total category count
     * 
     * @return int
     */
    public function getTotalCategoryCount(): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM categories";
            $result = $this->db->fetchRow($query);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get total category count: " . $e->getMessage());
        }
    }
}
