<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\Category;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Category Service - Business Logic Layer
 * 
 * Handles category-related business operations
 */
class CategoryService
{
    private CategoryRepository $categoryRepository;
    
    public function __construct()
    {
        $this->categoryRepository = new CategoryRepository();
    }
    
    /**
     * Get all categories
     * 
     * @return array
     */
    public function getAllCategories(): array
    {
        return $this->categoryRepository->getAllCategories();
    }
    
    /**
     * Get active categories
     * 
     * @return array
     */
    public function getActiveCategories(): array
    {
        return $this->categoryRepository->getActiveCategories();
    }
    
    /**
     * Get category by ID
     * 
     * @param int $categoryId
     * @return Category|null
     */
    public function getCategoryById(int $categoryId): ?Category
    {
        return $this->categoryRepository->findById($categoryId);
    }
    
    /**
     * Create new category
     * 
     * @param array $data
     * @return Category
     */
    public function createCategory(array $data): Category
    {
        $this->validateCategoryData($data);
        
        $category = new Category();
        $category->fill($data);
        
        if ($category->save()) {
            return $category;
        }
        
        throw new DatabaseException("Failed to create category");
    }
    
    /**
     * Update category
     * 
     * @param int $categoryId
     * @param array $data
     * @return Category
     */
    public function updateCategory(int $categoryId, array $data): Category
    {
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            throw new ValidationException("Category not found");
        }
        
        $this->validateCategoryData($data, $categoryId);
        
        $category->fill($data);
        
        if ($category->save()) {
            return $category;
        }
        
        throw new DatabaseException("Failed to update category");
    }
    
    /**
     * Delete category
     * 
     * @param int $categoryId
     * @return bool
     */
    public function deleteCategory(int $categoryId): bool
    {
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            throw new ValidationException("Category not found");
        }
        
        // Check if category has menu items
        $menuItemCount = $this->categoryRepository->getMenuItemCount($categoryId);
        if ($menuItemCount > 0) {
            throw new ValidationException("Cannot delete category with menu items");
        }
        
        return $this->categoryRepository->deleteCategory($categoryId);
    }
    
    /**
     * Toggle category status
     * 
     * @param int $categoryId
     * @return bool
     */
    public function toggleStatus(int $categoryId): bool
    {
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            throw new ValidationException("Category not found");
        }
        
        $category->is_active = !$category->is_active;
        return $category->save();
    }
    
    /**
     * Get category statistics
     * 
     * @return array
     */
    public function getCategoryStatistics(): array
    {
        return $this->categoryRepository->getCategoryStatistics();
    }
    
    /**
     * Validate category data
     * 
     * @param array $data
     * @param int|null $excludeId
     * @throws ValidationException
     */
    private function validateCategoryData(array $data, ?int $excludeId = null): void
    {
        $errors = [];
        
        // Validate name
        if (empty($data['name'])) {
            $errors['name'][] = 'Category name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'][] = 'Category name must be at least 2 characters';
        } else {
            // Check for duplicate name
            $existing = $this->categoryRepository->findByName($data['name']);
            if ($existing && $existing->id !== $excludeId) {
                $errors['name'][] = 'Category name already exists';
            }
        }
        
        // Validate sort order
        if (!empty($data['sort_order']) && (!is_numeric($data['sort_order']) || $data['sort_order'] < 0)) {
            $errors['sort_order'][] = 'Sort order must be a positive number';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Category validation failed', $errors);
        }
    }
}
