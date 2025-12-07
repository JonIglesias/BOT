<?php
/**
 * Database Connection Handler for API5
 * Singleton PDO connection manager
 */

defined('API_ACCESS') or die('Direct access not permitted');

class Database {

    private static $instance = null;
    private $connection = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            // Log error only if Logger is available
            if (class_exists('Logger')) {
                Logger::error('Database connection failed', [
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log('Database connection failed: ' . $e->getMessage());
            }

            // Throw exception instead of die() so it can be caught
            throw new Exception('Database connection failed: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prepare a statement (for compatibility with existing code)
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    /**
     * Execute a SELECT query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if (class_exists('Logger')) {
                Logger::error('Query failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            }
            throw $e;
        }
    }

    /**
     * Execute a SELECT query and return single row
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (class_exists('Logger')) {
                Logger::error('Query failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            }
            throw $e;
        }
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            return $result;
        } catch (PDOException $e) {
            if (class_exists('Logger')) {
                Logger::error('Execute failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log('Execute failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            }
            throw $e;
        }
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Alias for queryOne - used by models
     */
    public function fetchOne($sql, $params = []) {
        return $this->queryOne($sql, $params);
    }

    /**
     * Alias for query - used by models
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params);
    }

    /**
     * Insert data into a table
     */
    public function insert($table, $data) {
        $table = DB_PREFIX . $table;
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->execute($sql, array_values($data));
        return $this->lastInsertId();
    }

    /**
     * Update data in a table
     */
    public function update($table, $data, $where, $whereParams = []) {
        $table = DB_PREFIX . $table;
        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
        $allParams = array_merge($values, $whereParams);

        return $this->execute($sql, $allParams);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
