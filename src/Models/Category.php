<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * Category Model - Represents menu categories
 * 
 * Handles menu categorization and organization
 */
class Category extends BaseModel
{
    protected string $table = 'categories';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'description',
        'image_url',
        'sort_order',
        'is_active',
        'parent_category_id',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'sort_order' => 'int',
        'is_active' => 'boolean',
        'parent_category_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    private ?Category $parentCategory = null;
    private array $subCategories = [];
    private array $menuItems = [];
    
    /**
     * Get parent category
     * 
     * @return Category|null
     */
    public function getParentCategory(): ?Category
    {
        if ($this->parentCategory === null && $this->parent_category_id) {
            $this->parentCategory = Category::find($this->parent_category_id);
        }
        return $this->parentCategory;
    }
    
    /**
     * Set parent category
     * 
     * @param Category|null $category
     */
    public function setParentCategory(?Category $category): void
    {
        $this->parentCategory = $category;
        $this->parent_category_id = $category ? $category->category_id : null;
    }
    
    /**
     * Get subcategories
     * 
     * @return array
     */
    public function getSubCategories(): array
    {
        if (empty($this->subCategories)) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM {$this->table} WHERE parent_category_id = ? ORDER BY sort_order, name",
                [$this->category_id]
            );
            
            foreach ($rows as $row) {
                $category = new static($row);
                $category->exists = true;
                $category->original = $category->attributes;
                $this->subCategories[] = $category;
            }
        }
        
        return $this->subCategories;
    }
    
    /**
     * Get menu items in this category
     * 
     * @return array
     */
    public function getMenuItems(): array
    {
        if (empty($this->menuItems)) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM menu_items WHERE category_id = ? ORDER BY name",
                [$this->category_id]
            );
            
            foreach ($rows as $row) {
                $item = new MenuItem($row);
                $item->exists = true;
                $item->original = $item->attributes;
                $this->menuItems[] = $item;
            }
        }
        
        return $this->menuItems;
    }
    
    /**
     * Get available menu items in this category
     * 
     * @return array
     */
    public function getAvailableMenuItems(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY name",
            [$this->category_id]
        );
        
        $items = [];
        foreach ($rows as $row) {
            $item = new MenuItem($row);
            $item->exists = true;
            $item->original = $item->attributes;
            $items[] = $item;
        }
        
        return $items;
    }
    
    /**
     * Check if category is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
    
    /**
     * Check if category has parent
     * 
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->parent_category_id !== null;
    }
    
    /**
     * Check if category has subcategories
     * 
     * @return bool
     */
    public function hasSubCategories(): bool
    {
        return count($this->getSubCategories()) > 0;
    }
    
    /**
     * Check if category has menu items
     * 
     * @return bool
     */
    public function hasMenuItems(): bool
    {
        return count($this->getMenuItems()) > 0;
    }
    
    /**
     * Get category hierarchy path
     * 
     * @return array
     */
    public function getHierarchyPath(): array
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, $current);
            $current = $current->getParentCategory();
        }
        
        return $path;
    }
    
    /**
     * Get breadcrumb string
     * 
     * @param string $separator
     * @return string
     */
    public function getBreadcrumb(string $separator = ' > '): string
    {
        $path = $this->getHierarchyPath();
        $names = array_map(function($category) {
            return $category->name;
        }, $path);
        
        return implode($separator, $names);
    }
    
    /**
     * Get all root categories (no parent)
     * 
     * @return array
     */
    public static function getRootCategories(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE parent_category_id IS NULL ORDER BY sort_order, name"
        );
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Get active categories
     * 
     * @return array
     */
    public static function getActive(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE is_active = 1 ORDER BY sort_order, name"
        );
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Get categories by parent
     * 
     * @param int|null $parentId
     * @return array
     */
    public static function getByParent(?int $parentId = null): array
    {
        $instance = new static();
        
        if ($parentId === null) {
            $sql = "SELECT * FROM {$instance->table} WHERE parent_category_id IS NULL ORDER BY sort_order, name";
            $params = [];
        } else {
            $sql = "SELECT * FROM {$instance->table} WHERE parent_category_id = ? ORDER BY sort_order, name";
            $params = [$parentId];
        }
        
        $rows = $instance->db->fetchAll($sql, $params);
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Get category tree structure
     * 
     * @return array
     */
    public static function getTree(): array
    {
        $rootCategories = static::getRootCategories();
        
        foreach ($rootCategories as $category) {
            $category->loadSubCategoriesRecursively();
        }
        
        return $rootCategories;
    }
    
    /**
     * Load subcategories recursively
     */
    protected function loadSubCategoriesRecursively(): void
    {
        $this->subCategories = $this->getSubCategories();
        
        foreach ($this->subCategories as $subCategory) {
            $subCategory->loadSubCategoriesRecursively();
        }
    }
    
    /**
     * Create collection from database rows
     * 
     * @param array $rows
     * @return array
     */
    protected static function createCollectionFromRows(array $rows): array
    {
        $categories = [];
        foreach ($rows as $row) {
            $category = new static($row);
            $category->exists = true;
            $category->original = $category->attributes;
            $categories[] = $category;
        }
        
        return $categories;
    }
    
    /**
     * Validate category data
     * 
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate name
        if (empty($this->name)) {
            $errors['name'][] = 'Category name is required';
        } elseif (strlen($this->name) < 2) {
            $errors['name'][] = 'Category name must be at least 2 characters';
        } else {
            // Check for duplicate name at same level
            $existing = $this->checkDuplicateName();
            if ($existing) {
                $errors['name'][] = 'Category name already exists at this level';
            }
        }
        
        // Validate sort order
        if ($this->sort_order !== null && $this->sort_order < 0) {
            $errors['sort_order'][] = 'Sort order cannot be negative';
        }
        
        // Validate parent category
        if ($this->parent_category_id !== null) {
            // Check if parent exists
            $parent = Category::find($this->parent_category_id);
            if (!$parent) {
                $errors['parent_category_id'][] = 'Invalid parent category';
            } elseif ($parent->category_id === $this->category_id) {
                $errors['parent_category_id'][] = 'Category cannot be its own parent';
            } elseif ($this->wouldCreateCircularReference($parent)) {
                $errors['parent_category_id'][] = 'This would create a circular reference';
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Category validation failed', $errors);
        }
    }
    
    /**
     * Check for duplicate category name at same level
     * 
     * @return bool
     */
    protected function checkDuplicateName(): bool
    {
        $sql = "SELECT category_id FROM {$this->table} WHERE name = ?";
        $params = [$this->name];
        
        if ($this->parent_category_id === null) {
            $sql .= " AND parent_category_id IS NULL";
        } else {
            $sql .= " AND parent_category_id = ?";
            $params[] = $this->parent_category_id;
        }
        
        if ($this->exists) {
            $sql .= " AND category_id != ?";
            $params[] = $this->category_id;
        }
        
        $result = $this->db->fetchRow($sql, $params);
        return $result !== false;
    }
    
    /**
     * Check if setting parent would create circular reference
     * 
     * @param Category $potentialParent
     * @return bool
     */
    protected function wouldCreateCircularReference(Category $potentialParent): bool
    {
        $current = $potentialParent;
        
        while ($current) {
            if ($current->category_id === $this->category_id) {
                return true;
            }
            $current = $current->getParentCategory();
        }
        
        return false;
    }
    
    /**
     * Perform insert operation with timestamps
     * 
     * @return bool
     */
    protected function performInsert(): bool
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
        
        // Set default values
        if ($this->is_active === null) {
            $this->is_active = true;
        }
        if ($this->sort_order === null) {
            $this->sort_order = $this->getNextSortOrder();
        }
        
        return parent::performInsert();
    }
    
    /**
     * Perform update operation with timestamps
     * 
     * @return bool
     */
    protected function performUpdate(): bool
    {
        $this->updated_at = new DateTime();
        return parent::performUpdate();
    }
    
    /**
     * Get next sort order for category
     * 
     * @return int
     */
    protected function getNextSortOrder(): int
    {
        if ($this->parent_category_id === null) {
            $sql = "SELECT MAX(sort_order) as max_order FROM {$this->table} WHERE parent_category_id IS NULL";
            $params = [];
        } else {
            $sql = "SELECT MAX(sort_order) as max_order FROM {$this->table} WHERE parent_category_id = ?";
            $params = [$this->parent_category_id];
        }
        
        $result = $this->db->fetchRow($sql, $params);
        return ($result['max_order'] ?? 0) + 1;
    }
    
    /**
     * Convert to array with additional hierarchy information
     * 
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add hierarchy information
        $array['has_parent'] = $this->hasParent();
        $array['has_subcategories'] = $this->hasSubCategories();
        $array['has_menu_items'] = $this->hasMenuItems();
        $array['breadcrumb'] = $this->getBreadcrumb();
        
        return $array;
    }
    
    /**
     * Get count of active categories
     */
    public function getActiveCount(): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = 1";
        $result = $this->db->fetchAll($query);
        return (int) $result[0]['total'];
    }
}