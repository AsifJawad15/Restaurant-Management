<?php

namespace RestaurantMS\Models;

use RestaurantMS\Exceptions\ValidationException;
use DateTime;

/**
 * MenuItem Model - Represents restaurant menu items
 * 
 * Handles menu items, categories, pricing, and availability
 */
class MenuItem extends BaseModel
{
    protected string $table = 'menu_items';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'description',
        'category_id',
        'price',
        'image_url',
        'ingredients',
        'allergens',
        'nutritional_info',
        'preparation_time',
        'is_available',
        'is_featured',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'spice_level',
        'serving_size',
        'calories',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'category_id' => 'int',
        'price' => 'float',
        'ingredients' => 'json',
        'allergens' => 'json',
        'nutritional_info' => 'json',
        'preparation_time' => 'int',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_vegetarian' => 'boolean',
        'is_vegan' => 'boolean',
        'is_gluten_free' => 'boolean',
        'spice_level' => 'int',
        'calories' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Spice levels
    public const SPICE_NONE = 0;
    public const SPICE_MILD = 1;
    public const SPICE_MEDIUM = 2;
    public const SPICE_HOT = 3;
    public const SPICE_VERY_HOT = 4;
    
    private ?Category $category = null;
    
    /**
     * Get associated category
     * 
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        if ($this->category === null && $this->category_id) {
            $this->category = Category::find($this->category_id);
        }
        return $this->category;
    }
    
    /**
     * Set associated category
     * 
     * @param Category $category
     */
    public function setCategory(Category $category): void
    {
        $this->category = $category;
        $this->category_id = $category->category_id;
    }
    
    /**
     * Get formatted price
     * 
     * @param string $currency
     * @return string
     */
    public function getFormattedPrice(string $currency = '$'): string
    {
        return $currency . number_format($this->price, 2);
    }
    
    /**
     * Get spice level text
     * 
     * @return string
     */
    public function getSpiceLevelText(): string
    {
        switch ($this->spice_level) {
            case self::SPICE_NONE:
                return 'No Spice';
            case self::SPICE_MILD:
                return 'Mild';
            case self::SPICE_MEDIUM:
                return 'Medium';
            case self::SPICE_HOT:
                return 'Hot';
            case self::SPICE_VERY_HOT:
                return 'Very Hot';
            default:
                return 'Unknown';
        }
    }
    
    /**
     * Get ingredients list
     * 
     * @return array
     */
    public function getIngredients(): array
    {
        return $this->ingredients ?: [];
    }
    
    /**
     * Set ingredients list
     * 
     * @param array $ingredients
     */
    public function setIngredients(array $ingredients): void
    {
        $this->ingredients = $ingredients;
    }
    
    /**
     * Add ingredient
     * 
     * @param string $ingredient
     */
    public function addIngredient(string $ingredient): void
    {
        $ingredients = $this->getIngredients();
        if (!in_array($ingredient, $ingredients)) {
            $ingredients[] = $ingredient;
            $this->setIngredients($ingredients);
        }
    }
    
    /**
     * Remove ingredient
     * 
     * @param string $ingredient
     */
    public function removeIngredient(string $ingredient): void
    {
        $ingredients = $this->getIngredients();
        $key = array_search($ingredient, $ingredients);
        if ($key !== false) {
            unset($ingredients[$key]);
            $this->setIngredients(array_values($ingredients));
        }
    }
    
    /**
     * Get allergens list
     * 
     * @return array
     */
    public function getAllergens(): array
    {
        return $this->allergens ?: [];
    }
    
    /**
     * Set allergens list
     * 
     * @param array $allergens
     */
    public function setAllergens(array $allergens): void
    {
        $this->allergens = $allergens;
    }
    
    /**
     * Add allergen
     * 
     * @param string $allergen
     */
    public function addAllergen(string $allergen): void
    {
        $allergens = $this->getAllergens();
        if (!in_array($allergen, $allergens)) {
            $allergens[] = $allergen;
            $this->setAllergens($allergens);
        }
    }
    
    /**
     * Check if item has allergen
     * 
     * @param string $allergen
     * @return bool
     */
    public function hasAllergen(string $allergen): bool
    {
        return in_array($allergen, $this->getAllergens());
    }
    
    /**
     * Get nutritional information
     * 
     * @return array
     */
    public function getNutritionalInfo(): array
    {
        return $this->nutritional_info ?: [];
    }
    
    /**
     * Set nutritional information
     * 
     * @param array $info
     */
    public function setNutritionalInfo(array $info): void
    {
        $this->nutritional_info = $info;
    }
    
    /**
     * Check if item is available
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->is_available === true;
    }
    
    /**
     * Toggle availability
     */
    public function toggleAvailability(): void
    {
        $this->is_available = !$this->is_available;
    }
    
    /**
     * Mark as featured
     */
    public function markAsFeatured(): void
    {
        $this->is_featured = true;
    }
    
    /**
     * Unmark as featured
     */
    public function unmarkAsFeatured(): void
    {
        $this->is_featured = false;
    }
    
    /**
     * Get menu items by category
     * 
     * @param int $categoryId
     * @return array
     */
    public static function getByCategory(int $categoryId): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE category_id = ? ORDER BY name",
            [$categoryId]
        );
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Get available menu items
     * 
     * @return array
     */
    public static function getAvailable(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE is_available = 1 ORDER BY category_id, name"
        );
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Get featured menu items
     * 
     * @return array
     */
    public static function getFeatured(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} WHERE is_featured = 1 AND is_available = 1 ORDER BY name"
        );
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Search menu items by name or description
     * 
     * @param string $query
     * @return array
     */
    public static function search(string $query): array
    {
        $instance = new static();
        $searchTerm = '%' . $query . '%';
        $rows = $instance->db->fetchAll(
            "SELECT * FROM {$instance->table} 
             WHERE (name LIKE ? OR description LIKE ?) AND is_available = 1 
             ORDER BY name",
            [$searchTerm, $searchTerm]
        );
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Get menu items by dietary requirements
     * 
     * @param array $requirements ['vegetarian', 'vegan', 'gluten_free']
     * @return array
     */
    public static function getByDietaryRequirements(array $requirements): array
    {
        $instance = new static();
        $conditions = ['is_available = 1'];
        $params = [];
        
        foreach ($requirements as $requirement) {
            switch ($requirement) {
                case 'vegetarian':
                    $conditions[] = 'is_vegetarian = 1';
                    break;
                case 'vegan':
                    $conditions[] = 'is_vegan = 1';
                    break;
                case 'gluten_free':
                    $conditions[] = 'is_gluten_free = 1';
                    break;
            }
        }
        
        $sql = "SELECT * FROM {$instance->table} WHERE " . implode(' AND ', $conditions) . " ORDER BY name";
        $rows = $instance->db->fetchAll($sql, $params);
        
        return static::createCollectionFromRows($rows);
    }
    
    /**
     * Create collection from database rows
     * 
     * @param array $rows
     * @return array
     */
    protected static function createCollectionFromRows(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $item = new static($row);
            $item->exists = true;
            $item->original = $item->attributes;
            $items[] = $item;
        }
        
        return $items;
    }
    
    /**
     * Validate menu item data
     * 
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $errors = [];
        
        // Validate name
        if (empty($this->name)) {
            $errors['name'][] = 'Item name is required';
        } elseif (strlen($this->name) < 2) {
            $errors['name'][] = 'Item name must be at least 2 characters';
        }
        
        // Validate price
        if ($this->price === null || $this->price === '') {
            $errors['price'][] = 'Price is required';
        } elseif ($this->price < 0) {
            $errors['price'][] = 'Price cannot be negative';
        } elseif ($this->price > 9999.99) {
            $errors['price'][] = 'Price cannot exceed $9,999.99';
        }
        
        // Validate category
        if (empty($this->category_id)) {
            $errors['category_id'][] = 'Category is required';
        }
        
        // Validate spice level
        if ($this->spice_level !== null && !in_array($this->spice_level, [0, 1, 2, 3, 4])) {
            $errors['spice_level'][] = 'Invalid spice level';
        }
        
        // Validate preparation time
        if ($this->preparation_time !== null && $this->preparation_time < 0) {
            $errors['preparation_time'][] = 'Preparation time cannot be negative';
        }
        
        // Validate calories
        if ($this->calories !== null && $this->calories < 0) {
            $errors['calories'][] = 'Calories cannot be negative';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Menu item validation failed', $errors);
        }
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
        if ($this->is_available === null) {
            $this->is_available = true;
        }
        if ($this->is_featured === null) {
            $this->is_featured = false;
        }
        if ($this->is_vegetarian === null) {
            $this->is_vegetarian = false;
        }
        if ($this->is_vegan === null) {
            $this->is_vegan = false;
        }
        if ($this->is_gluten_free === null) {
            $this->is_gluten_free = false;
        }
        if ($this->spice_level === null) {
            $this->spice_level = self::SPICE_NONE;
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
     * Get count of active menu items
     */
    public function getActiveCount(): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_available = 1";
        $result = $this->db->fetchAll($query);
        return (int) $result[0]['total'];
    }
    
    /**
     * Get count of featured menu items
     */
    public function getFeaturedCount(): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_featured = 1 AND is_available = 1";
        $result = $this->db->fetchAll($query);
        return (int) $result[0]['total'];
    }
    
    /**
     * Get popular menu items (by order count)
     */
    public function getPopularItems(int $limit = 5, int $days = 7): array
    {
        $query = "SELECT mi.name, mi.price, COUNT(oi.id) as order_count,
                         SUM(oi.total_price) as total_revenue
                  FROM {$this->table} mi
                  JOIN order_items oi ON mi.id = oi.menu_item_id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY mi.id
                  ORDER BY order_count DESC
                  LIMIT ?";
        return $this->db->fetchAll($query, [$days, $limit]);
    }
}