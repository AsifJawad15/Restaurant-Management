<?php

namespace RestaurantMS\Core;

/**
 * Configuration Manager - Singleton Pattern
 * 
 * Centralized configuration management for the entire application
 * Implements Singleton pattern to ensure single instance throughout app lifecycle
 */
class Config
{
    private static ?Config $instance = null;
    private array $config = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->loadConfiguration();
    }
    
    /**
     * Get singleton instance of Config
     * 
     * @return Config
     */
    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration from various sources
     */
    private function loadConfiguration(): void
    {
        // Database configuration
        $this->config['database'] = [
            'host' => 'localhost',
            'dbname' => 'restaurant_management',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
        
        // Application configuration
        $this->config['app'] = [
            'name' => 'Restaurant Management System',
            'version' => '2.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'debug' => $_ENV['APP_DEBUG'] ?? false,
            'url' => $_ENV['APP_URL'] ?? 'http://localhost',
            'timezone' => 'UTC'
        ];
        
        // Session configuration
        $this->config['session'] = [
            'name' => 'RESTAURANT_SESSION',
            'lifetime' => 3600, // 1 hour
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true
        ];
        
        // Security configuration
        $this->config['security'] = [
            'password_min_length' => 8,
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'csrf_token_name' => '_token'
        ];
        
        // Business rules
        $this->config['business'] = [
            'max_table_capacity' => 8,
            'reservation_advance_days' => 30,
            'loyalty_points_ratio' => 10, // 1 point per $10 spent
            'default_currency' => 'USD'
        ];
    }
    
    /**
     * Get configuration value by key
     * 
     * @param string $key Dot notation key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            if (!is_array($config)) {
                $config = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    /**
     * Check if configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get all configuration
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }
    
    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {}
}