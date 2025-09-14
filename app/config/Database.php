<?php

class Database
{
    private static $instance = null;
    private $connection;

    private $host = '127.0.0.1';
    private $user = 'root';
    private $password = '';
    private $database = 'bilty_db';

    private function __construct()
    {
        try {
            $this->connection = new mysqli($this->host, $this->user, $this->password, $this->database);
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            $this->connection->set_charset('utf8mb4');
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {}
}