<?php

/**
 * Database Connection Class
 * Singleton pattern for database connection management
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $user;
    private $pass;
    private $dbname;
    
    private function __construct() {
        // Use config constants if available, otherwise fallback to defaults
        $this->host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $this->user = defined('DB_USER') ? DB_USER : 'root';
        $this->pass = defined('DB_PASS') ? DB_PASS : '';
        $this->dbname = defined('DB_NAME') ? DB_NAME : 'bilty_db';
        
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->connection->connect_error) {
            die("Database connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset('utf8mb4');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning of instance
    private function __clone() {}
    
    // Prevent unserializing of instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
