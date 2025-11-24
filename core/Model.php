<?php

/**
 * Base Model Class
 * All models extend this class
 */
class Model {
    protected $db;
    protected $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Dynamic parameter binding for prepared statements
     */
    protected function bindParams($stmt, $params) {
        if (empty($params)) return true;
        
        $types = '';
        foreach ($params as $index => &$v) {
            if ($v === null) {
                $v = '';
                $types .= 's';
                continue;
            }
            if (is_int($v)) {
                $types .= 'i';
                continue;
            }
            if (is_float($v)) {
                $types .= 'd';
                continue;
            }
            
            $sv = (string)$v;
            if (preg_match('/^-?\d+$/', $sv)) {
                $v = (int)$sv;
                $types .= 'i';
                continue;
            }
            if (is_numeric($sv) && preg_match('/[.eE]/', $sv)) {
                $v = (float)$sv;
                $types .= 'd';
                continue;
            }
            
            $v = $sv;
            $types .= 's';
        }
        
        $refs = [];
        $refs[] = &$types;
        foreach ($params as $k => &$val) {
            $refs[] = &$val;
        }
        
        return call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    /**
     * Execute a prepared query and return results
     */
    protected function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        if (!empty($params)) {
            $this->bindParams($stmt, $params);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Fetch all rows from a query
     */
    protected function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        
        $stmt->close();
        return $rows;
    }
    
    /**
     * Fetch a single row from a query
     */
    protected function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }
}
