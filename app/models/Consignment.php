<?php

/**
 * Consignment Model
 * Handles all database operations for bilty/consignments
 */
class Consignment extends Model {
    
    /**
     * Get all consignments with optional filters
     */
    public function getAll($filters = []) {
        $where = [];
        $params = [];
        
        if (isset($filters['company_id']) && $filters['company_id'] > 0) {
            $where[] = "c.company_id = ?";
            $params[] = $filters['company_id'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $where[] = "(c.bilty_no LIKE ? OR cp.name LIKE ? OR c.driver_name LIKE ? OR c.from_city LIKE ? OR c.to_city LIKE ? OR c.vehicle_no LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT c.*, cp.name AS company_name
                FROM consignments c
                JOIN companies cp ON cp.id = c.company_id
                {$whereSql}
                ORDER BY c.date DESC, c.id DESC";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Get a consignment by ID
     */
    public function getById($id) {
        $sql = "SELECT c.*, cp.name AS company_name
                FROM consignments c
                JOIN companies cp ON cp.id = c.company_id
                WHERE c.id = ?
                LIMIT 1";
        
        return $this->fetchOne($sql, [$id]);
    }
    
    /**
     * Get a consignment by bilty number
     */
    public function getByBiltyNo($biltyNo) {
        $sql = "SELECT c.*, cp.name AS company_name
                FROM consignments c
                JOIN companies cp ON cp.id = c.company_id
                WHERE c.bilty_no = ?
                LIMIT 1";
        
        return $this->fetchOne($sql, [$biltyNo]);
    }
    
    /**
     * Get next bilty number
     */
    public function getNextBiltyNo() {
        $sql = "SELECT MAX(id) AS maxid FROM consignments";
        $result = $this->fetchOne($sql);
        $next = (int)($result['maxid'] ?? 0) + 1;
        return (string)$next;
    }
    
    /**
     * Check if rate_type column exists
     */
    public function hasRateTypeColumn() {
        $sql = "SHOW COLUMNS FROM consignments LIKE 'rate_type'";
        $result = $this->fetchOne($sql);
        return !empty($result);
    }
    
    /**
     * Create a new consignment
     */
    public function create($data) {
        $hasRateType = $this->hasRateTypeColumn();
        
        $cols = [
            'company_id', 'bilty_no', 'date', 'vehicle_no', 'driver_name', 'vehicle_type',
            'sender_name', 'from_city', 'to_city', 'qty', 'details', 'km', 'rate', 
            'amount', 'advance', 'balance'
        ];
        
        $placeholders = array_fill(0, count($cols), '?');
        
        if ($hasRateType) {
            $cols[] = 'rate_type';
            $placeholders[] = '?';
        }
        
        $sql = "INSERT INTO consignments (" . implode(',', $cols) . ") 
                VALUES (" . implode(',', $placeholders) . ")";
        
        $params = [
            $data['company_id'],
            $data['bilty_no'],
            $data['date'],
            $data['vehicle_no'],
            $data['driver_name'],
            $data['vehicle_type'],
            $data['sender_name'],
            $data['from_city'],
            $data['to_city'],
            $data['qty'],
            $data['details'],
            $data['km'],
            $data['rate'],
            $data['amount'],
            $data['advance'],
            $data['balance']
        ];
        
        if ($hasRateType) {
            $params[] = $data['rate_type'];
        }
        
        $stmt = $this->query($sql, $params);
        $insertId = $this->conn->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Update consignment payment
     */
    public function updatePayment($id, $advance, $balance) {
        $sql = "UPDATE consignments SET advance = ?, balance = ? WHERE id = ?";
        $stmt = $this->query($sql, [$advance, $balance, $id]);
        $stmt->close();
        return true;
    }
    
    /**
     * Get consignments by IDs (for bulk operations)
     */
    public function getByIds($ids) {
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $sql = "SELECT c.*, cp.name AS company_name
                FROM consignments c
                JOIN companies cp ON cp.id = c.company_id
                WHERE c.id IN ({$placeholders})
                ORDER BY c.date DESC, c.id DESC";
        
        return $this->fetchAll($sql, $ids);
    }
}
