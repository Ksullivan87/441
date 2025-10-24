<?php

class Database {
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $pdo;
    
    public function __construct($host, $database, $username, $password) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }
    
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            return true;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function disconnect() {
        $this->pdo = null;
    }
    
    public function prepare($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            if (!empty($params)) {
                $stmt->execute($params);
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query preparation failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function select($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return false;
    }
    
    public function insert($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        if ($stmt) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    public function update($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }
    
    public function delete($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }
    
    public function rowCount() {
        return $this->pdo->lastInsertId();
    }
    
    public function execute($query) {
        try {
            return $this->pdo->exec($query);
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function isConnected() {
        return $this->pdo !== null;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

?>
