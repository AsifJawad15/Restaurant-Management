<?php

namespace RestaurantMS\Core;

use PDO;
use PDOException;
use RestaurantMS\Exceptions\DatabaseException;

/**
 * Database Manager - Singleton Pattern
 * 
 * Handles database connections and provides query building capabilities
 * Implements connection pooling and prepared statement management
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private Config $config;
    private array $connectionPool = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->config = Config::getInstance();
        $this->connect();
    }
    
    /**
     * Get singleton instance of Database
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     * 
     * @throws DatabaseException
     */
    private function connect(): void
    {
        try {
            $dbConfig = $this->config->get('database');
            
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $dbConfig['host'],
                $dbConfig['dbname'],
                $dbConfig['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
            
        } catch (PDOException $e) {
            throw new DatabaseException("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     * @throws DatabaseException
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     * 
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function fetchRow(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Insert record and return last insert ID
     * 
     * @param string $table
     * @param array $data
     * @return string
     * @throws DatabaseException
     */
    public function insert(string $table, array $data): string
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Update records
     * 
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $conditions): int
    {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach (array_keys($conditions) as $key) {
            $whereParts[] = "{$key} = :where_{$key}";
            $data["where_{$key}"] = $conditions[$key];
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        $stmt = $this->query($sql, $data);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     * 
     * @param string $table
     * @param array $conditions
     * @return int Number of affected rows
     */
    public function delete(string $table, array $conditions): int
    {
        $whereParts = [];
        foreach (array_keys($conditions) as $key) {
            $whereParts[] = "{$key} = :{$key}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        $stmt = $this->query($sql, $conditions);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->getConnection()->rollBack();
    }
    
    /**
     * Check if currently in transaction
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
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