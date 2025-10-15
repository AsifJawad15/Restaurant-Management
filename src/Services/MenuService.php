<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\MenuItem;
use RestaurantMS\Models\Category;
use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Menu Service - Business Logic Layer
 * 
 * Handles menu-related business operations
 */
class MenuService
{
    private Database $db;
    private MenuRepository $menuRepository;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->menuRepository = new MenuRepository();
    }
    
    /**
     * Get all menu items with categories
     * 
     * @param array $filters
     * @return array
     */
    public function getMenuItems(array $filters = []): array
    {
        return $this->menuRepository->getMenuItemsWithFilters($filters);
    }
    
    /**
     * Get menu item by ID
     * 
     * @param int $itemId
     * @return MenuItem|null
     */
    public function getMenuItemById(int $itemId): ?MenuItem
    {
        return $this->menuRepository->findById($itemId);
    }
    
    /**
     * Create new menu item
     * 
     * @param array $data
     * @return MenuItem
     */
    public function createMenuItem(array $data): MenuItem
    {
        $this->validateMenuItemData($data);
        
        $menuItem = new MenuItem();
        $menuItem->fill($data);
        
        if ($menuItem->save()) {
            return $menuItem;
        }
        
        throw new DatabaseException("Failed to create menu item");
    }
    
    /**
     * Update menu item
     * 
     * @param int $itemId
     * @param array $data
     * @return MenuItem
     */
    public function updateMenuItem(int $itemId, array $data): MenuItem
    {
        $menuItem = $this->getMenuItemById($itemId);
        if (!$menuItem) {
            throw new ValidationException("Menu item not found");
        }
        
        $this->validateMenuItemData($data, $itemId);
        
        $menuItem->fill($data);
        
        if ($menuItem->save()) {
            return $menuItem;
        }
        
        throw new DatabaseException("Failed to update menu item");
    }
    
    /**
     * Delete menu item
     * 
     * @param int $itemId
     * @return bool
     */
    public function deleteMenuItem(int $itemId): bool
    {
        $menuItem = $this->getMenuItemById($itemId);
        if (!$menuItem) {
            throw new ValidationException("Menu item not found");
        }
        
        return $this->menuRepository->deleteMenuItem($itemId);
    }
    
    /**
     * Toggle menu item availability
     * 
     * @param int $itemId
     * @return bool
     */
    public function toggleAvailability(int $itemId): bool
    {
        $menuItem = $this->getMenuItemById($itemId);
        if (!$menuItem) {
            throw new ValidationException("Menu item not found");
        }
        
        $menuItem->is_available = !$menuItem->is_available;
        return $menuItem->save();
    }
    
    /**
     * Toggle menu item featured status
     * 
     * @param int $itemId
     * @return bool
     */
    public function toggleFeatured(int $itemId): bool
    {
        $menuItem = $this->getMenuItemById($itemId);
        if (!$menuItem) {
            throw new ValidationException("Menu item not found");
        }
        
        $menuItem->is_featured = !$menuItem->is_featured;
        return $menuItem->save();
    }
    
    /**
     * Get menu statistics
     * 
     * @return array
     */
    public function getMenuStatistics(): array
    {
        return $this->menuRepository->getMenuStatistics();
    }
    
    /**
     * Get popular menu items
     * 
     * @param int $limit
     * @return array
     */
    public function getPopularMenuItems(int $limit = 10): array
    {
        return $this->menuRepository->getPopularMenuItems($limit);
    }
    
    /**
     * Search menu items
     * 
     * @param string $searchTerm
     * @return array
     */
    public function searchMenuItems(string $searchTerm): array
    {
        return $this->menuRepository->searchMenuItems($searchTerm);
    }
    
    /**
     * Get menu items by category
     * 
     * @param int $categoryId
     * @return array
     */
    public function getMenuItemsByCategory(int $categoryId): array
    {
        return $this->menuRepository->getMenuItemsByCategory($categoryId);
    }
    
    /**
     * Validate menu item data
     * 
     * @param array $data
     * @param int|null $excludeId
     * @throws ValidationException
     */
    private function validateMenuItemData(array $data, ?int $excludeId = null): void
    {
        $errors = [];
        
        // Validate name
        if (empty($data['name'])) {
            $errors['name'][] = 'Menu item name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'][] = 'Menu item name must be at least 2 characters';
        }
        
        // Validate category
        if (empty($data['category_id'])) {
            $errors['category_id'][] = 'Category is required';
        } else {
            $category = Category::find($data['category_id']);
            if (!$category) {
                $errors['category_id'][] = 'Invalid category';
            }
        }
        
        // Validate price
        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors['price'][] = 'Valid price is required';
        }
        
        // Validate preparation time
        if (!empty($data['preparation_time']) && (!is_numeric($data['preparation_time']) || $data['preparation_time'] < 0)) {
            $errors['preparation_time'][] = 'Preparation time must be a positive number';
        }
        
        // Validate calories
        if (!empty($data['calories']) && (!is_numeric($data['calories']) || $data['calories'] < 0)) {
            $errors['calories'][] = 'Calories must be a positive number';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Menu item validation failed', $errors);
        }
    }
}
