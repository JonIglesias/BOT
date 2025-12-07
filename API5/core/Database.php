<?php
/**
 * Database Connection Class
 *
 * @version 4.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

class Database {
    private static $instance = null;
    private $pdo = null;

    /**
     * Constructor privado para Singleton
     */
    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $this->pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Log error with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Database connection failed', [
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database connection error: " . $e->getMessage());
            }
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Obtener instancia única
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Ejecutar query con parámetros
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Log with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Query failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database query error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Obtener un solo registro
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            // Log with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Query failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database fetchOne error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Obtener todos los registros
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Log with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Query failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database fetchAll error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Preparar statement
     */
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }

    /**
     * Ejecutar INSERT
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ":$col"; }, $columns);

        $sql = sprintf(
            "INSERT INTO %s%s (%s) VALUES (%s)",
            DB_PREFIX,
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Log with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Insert failed', [
                    'table' => $table,
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database insert error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Ejecutar UPDATE
     */
    public function update($table, $data, $where, $whereParams = []) {
        $sets = array_map(function($col) { return "$col = :$col"; }, array_keys($data));

        $sql = sprintf(
            "UPDATE %s%s SET %s WHERE %s",
            DB_PREFIX,
            $table,
            implode(', ', $sets),
            $where
        );

        try {
            $stmt = $this->pdo->prepare($sql);
            // Merge data params and where params
            $allParams = array_merge($data, $whereParams);
            $stmt->execute($allParams);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Log with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Update failed', [
                    'table' => $table,
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database update error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Ejecutar DELETE
     */
    public function delete($table, $where, $whereParams = []) {
        $sql = sprintf(
            "DELETE FROM %s%s WHERE %s",
            DB_PREFIX,
            $table,
            $where
        );

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($whereParams);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Log with Logger if available (improvement)
            if (class_exists('Logger')) {
                Logger::error('Delete failed', [
                    'table' => $table,
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
            } else {
                error_log("Database delete error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Obtener último ID insertado
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Comenzar transacción
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transacción
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transacción
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Obtener conexión PDO (para casos especiales)
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Prevenir clonación
     */
    private function __clone() {}

    /**
     * Prevenir unserialize
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
