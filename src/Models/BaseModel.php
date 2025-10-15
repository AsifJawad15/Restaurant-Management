<?php

namespace RestaurantMS\Models;

use RestaurantMS\Core\Database;
use RestaurantMS\Exceptions\DatabaseException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Base Model Class - Active Record Pattern
 * 
 * Provides common functionality for all model classes
 * Implements Active Record pattern for database operations
 */
abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->fill($attributes);
    }
    
    /**
     * Fill model with attributes
     * 
     * @param array $attributes
     * @return self
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }
    
    /**
     * Fill model attributes from database row (all attributes allowed)
     * 
     * @param array $attributes
     * @return self
     */
    public function fillFromDatabase(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        $this->exists = true;
        return $this;
    }
    
    /**
     * Check if attribute is fillable
     * 
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        return in_array($key, $this->fillable);
    }
    
    /**
     * Set attribute value
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $this->castAttribute($key, $value);
    }
    
    /**
     * Get attribute value
     * 
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }
        return null;
    }
    
    /**
     * Cast attribute to specified type
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute(string $key, $value)
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'json':
                return json_decode($value, true);
            case 'datetime':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            default:
                return $value;
        }
    }
    
    /**
     * Get all attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Get attributes for database storage
     * 
     * @return array
     */
    protected function getAttributesForStorage(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $attributes[$key] = $this->prepareValueForStorage($key, $value);
            }
        }
        return $attributes;
    }
    
    /**
     * Prepare value for database storage
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function prepareValueForStorage(string $key, $value)
    {
        if (isset($this->casts[$key])) {
            switch ($this->casts[$key]) {
                case 'array':
                case 'json':
                    return json_encode($value);
                case 'datetime':
                    return $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value;
            }
        }
        return $value;
    }
    
    /**
     * Save model to database
     * 
     * @return bool
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function save(): bool
    {
        $this->validate();
        
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }
    
    /**
     * Perform insert operation
     * 
     * @return bool
     * @throws DatabaseException
     */
    protected function performInsert(): bool
    {
        $attributes = $this->getAttributesForStorage();
        unset($attributes[$this->primaryKey]); // Remove ID for insert
        
        $id = $this->db->insert($this->table, $attributes);
        $this->setAttribute($this->primaryKey, $id);
        $this->exists = true;
        $this->original = $this->attributes;
        
        return true;
    }
    
    /**
     * Perform update operation
     * 
     * @return bool
     * @throws DatabaseException
     */
    protected function performUpdate(): bool
    {
        $attributes = $this->getAttributesForStorage();
        $id = $attributes[$this->primaryKey];
        unset($attributes[$this->primaryKey]);
        
        $affected = $this->db->update($this->table, $attributes, [$this->primaryKey => $id]);
        
        if ($affected > 0) {
            $this->original = $this->attributes;
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete model from database
     * 
     * @return bool
     * @throws DatabaseException
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $affected = $this->db->delete($this->table, [
            $this->primaryKey => $this->getAttribute($this->primaryKey)
        ]);
        
        if ($affected > 0) {
            $this->exists = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Find model by ID
     * 
     * @param mixed $id
     * @return static|null
     */
    public static function find($id): ?self
    {
        $instance = new static();
        $row = $instance->db->fetchRow(
            "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = ?",
            [$id]
        );
        
        if ($row) {
            $instance->fill($row);
            $instance->exists = true;
            $instance->original = $instance->attributes;
            return $instance;
        }
        
        return null;
    }
    
    /**
     * Find all records
     * 
     * @return array
     */
    public static function all(): array
    {
        $instance = new static();
        $rows = $instance->db->fetchAll("SELECT * FROM {$instance->table}");
        
        $models = [];
        foreach ($rows as $row) {
            $model = new static($row);
            $model->exists = true;
            $model->original = $model->attributes;
            $models[] = $model;
        }
        
        return $models;
    }
    
    /**
     * Create new model instance
     * 
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes): self
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }
    
    /**
     * Validate model attributes
     * Override in child classes for specific validation rules
     * 
     * @throws ValidationException
     */
    protected function validate(): void
    {
        // Base validation - override in child classes
    }
    
    /**
     * Magic getter
     * 
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     * 
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Check if attribute exists
     * 
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
    
    /**
     * Convert model to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $array[$key] = $value;
            }
        }
        return $array;
    }
    
    /**
     * Convert model to JSON
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Get all records with optional ordering
     * 
     * @param string $orderBy
     * @return static[]
     */
    public static function orderBy(string $orderBy): array
    {
        $instance = new static();
        $sql = "SELECT * FROM {$instance->table} ORDER BY {$orderBy}";
        
        $rows = $instance->db->fetchAll($sql);
        $models = [];
        
        foreach ($rows as $row) {
            $model = new static();
            $model->fillFromDatabase($row);
            $models[] = $model;
        }
        
        return $models;
    }
    
    /**
     * Get all records (alias for better readability)
     * 
     * @return static[]
     */
    public function get(): array
    {
        return static::all();
    }
}