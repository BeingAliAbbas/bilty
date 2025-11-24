<?php

/**
 * Company Model
 * Handles all database operations for companies
 */
class Company extends Model {
    
    /**
     * Get all companies
     */
    public function getAll($orderBy = 'name ASC') {
        $sql = "SELECT * FROM companies ORDER BY {$orderBy}";
        return $this->fetchAll($sql);
    }
    
    /**
     * Get a company by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM companies WHERE id = ? LIMIT 1";
        return $this->fetchOne($sql, [$id]);
    }
    
    /**
     * Get company name by ID
     */
    public function getNameById($id) {
        $company = $this->getById($id);
        return $company ? $company['name'] : $id;
    }
    
    /**
     * Check if company has address column
     */
    public function hasAddressColumn() {
        $sql = "SHOW COLUMNS FROM companies LIKE 'address'";
        $result = $this->fetchOne($sql);
        return !empty($result);
    }
    
    /**
     * Check if company name already exists
     */
    public function existsByName($name) {
        $sql = "SELECT id FROM companies WHERE LOWER(name) = LOWER(?) LIMIT 1";
        $result = $this->fetchOne($sql, [$name]);
        return !empty($result);
    }
    
    /**
     * Create a new company
     */
    public function create($name, $address = '') {
        if ($this->existsByName($name)) {
            throw new Exception("A company with this name already exists.");
        }
        
        $hasAddress = $this->hasAddressColumn();
        
        if ($hasAddress) {
            $sql = "INSERT INTO companies (name, address) VALUES (?, ?)";
            $stmt = $this->query($sql, [$name, $address]);
        } else {
            $sql = "INSERT INTO companies (name) VALUES (?)";
            $stmt = $this->query($sql, [$name]);
        }
        
        $insertId = $this->conn->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Update a company
     */
    public function update($id, $name, $address = '') {
        $hasAddress = $this->hasAddressColumn();
        
        if ($hasAddress) {
            $sql = "UPDATE companies SET name = ?, address = ? WHERE id = ?";
            $stmt = $this->query($sql, [$name, $address, $id]);
        } else {
            $sql = "UPDATE companies SET name = ? WHERE id = ?";
            $stmt = $this->query($sql, [$name, $id]);
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Delete a company
     */
    public function delete($id) {
        $sql = "DELETE FROM companies WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        $stmt->close();
        return true;
    }
}
