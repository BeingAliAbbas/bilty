<?php

/**
 * VehicleMaintenance Model
 * Handles all database operations for vehicle maintenance records
 */
class VehicleMaintenance extends Model {
    
    /**
     * Get all maintenance records with filters
     */
    public function getAll($filters = []) {
        $where = [];
        $params = [];
        
        $startDate = $filters['start'] ?? date('Y-m-01');
        $endDate = $filters['end'] ?? date('Y-m-d');
        
        $where[] = "entry_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        
        if (isset($filters['vehicle']) && !empty($filters['vehicle'])) {
            $where[] = "vehicle_no LIKE ?";
            $params[] = "%{$filters['vehicle']}%";
        }
        
        if (isset($filters['expense']) && !empty($filters['expense'])) {
            $where[] = "(expense_type LIKE ? OR narration LIKE ?)";
            $params[] = "%{$filters['expense']}%";
            $params[] = "%{$filters['expense']}%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $perPage = isset($filters['perPage']) ? (int)$filters['perPage'] : 25;
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM vehicle_maintenance
                {$whereSql}
                ORDER BY entry_date DESC, id DESC
                LIMIT {$offset}, {$perPage}";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Get count and total amount with filters
     */
    public function getStats($filters = []) {
        $where = [];
        $params = [];
        
        $startDate = $filters['start'] ?? date('Y-m-01');
        $endDate = $filters['end'] ?? date('Y-m-d');
        
        $where[] = "entry_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        
        if (isset($filters['vehicle']) && !empty($filters['vehicle'])) {
            $where[] = "vehicle_no LIKE ?";
            $params[] = "%{$filters['vehicle']}%";
        }
        
        if (isset($filters['expense']) && !empty($filters['expense'])) {
            $where[] = "(expense_type LIKE ? OR narration LIKE ?)";
            $params[] = "%{$filters['expense']}%";
            $params[] = "%{$filters['expense']}%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total_amt
                FROM vehicle_maintenance
                {$whereSql}";
        
        $result = $this->fetchOne($sql, $params);
        return $result ?? ['cnt' => 0, 'total_amt' => 0];
    }
    
    /**
     * Create a new maintenance record
     */
    public function create($data) {
        $sql = "INSERT INTO vehicle_maintenance (entry_date, vehicle_no, expense_type, amount, narration) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $data['entry_date'],
            $data['vehicle_no'],
            $data['expense_type'],
            $data['amount'],
            $data['narration'] ?? ''
        ];
        
        $stmt = $this->query($sql, $params);
        $insertId = $this->conn->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Get a maintenance record by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM vehicle_maintenance WHERE id = ? LIMIT 1";
        return $this->fetchOne($sql, [$id]);
    }
    
    /**
     * Update a maintenance record
     */
    public function update($id, $data) {
        $sql = "UPDATE vehicle_maintenance 
                SET entry_date = ?, vehicle_no = ?, expense_type = ?, amount = ?, narration = ? 
                WHERE id = ?";
        
        $params = [
            $data['entry_date'],
            $data['vehicle_no'],
            $data['expense_type'],
            $data['amount'],
            $data['narration'] ?? '',
            $id
        ];
        
        $stmt = $this->query($sql, $params);
        $stmt->close();
        return true;
    }
    
    /**
     * Delete a maintenance record
     */
    public function delete($id) {
        $sql = "DELETE FROM vehicle_maintenance WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        $stmt->close();
        return true;
    }
}
