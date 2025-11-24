<?php

/**
 * Bill Model
 * Handles all database operations for bills
 */
class Bill extends Model {
    
    /**
     * Get all bills with filters
     */
    public function getAll($filters = []) {
        $where = [];
        $params = [];
        
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "b.payment_status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $where[] = "(b.bill_no LIKE ? OR cp.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $orderBy = isset($filters['sort']) ? $this->getSortOrder($filters['sort']) : "b.issue_date DESC";
        
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? (int)$filters['pageSize'] : 20;
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT b.*, cp.name AS company_name
                FROM bills b
                LEFT JOIN companies cp ON cp.id = b.company_id
                {$whereSql}
                ORDER BY {$orderBy}
                LIMIT {$offset}, {$pageSize}";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Get total count with filters
     */
    public function getCount($filters = []) {
        $where = [];
        $params = [];
        
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "b.payment_status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $where[] = "(b.bill_no LIKE ? OR cp.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) AS cnt
                FROM bills b
                LEFT JOIN companies cp ON cp.id = b.company_id
                {$whereSql}";
        
        $result = $this->fetchOne($sql, $params);
        return $result ? (int)$result['cnt'] : 0;
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        $sql = "SELECT payment_status, COUNT(*) AS c, SUM(net_amount) AS s 
                FROM bills 
                GROUP BY payment_status";
        
        $results = $this->fetchAll($sql);
        
        $stats = [
            'total' => 0,
            'paid' => 0,
            'unpaid' => 0,
            'out_amt' => 0.0
        ];
        
        foreach ($results as $r) {
            $stats['total'] += (int)$r['c'];
            if ($r['payment_status'] === 'PAID') {
                $stats['paid'] = (int)$r['c'];
            } else {
                $stats['unpaid'] += (int)$r['c'];
                $stats['out_amt'] += (float)$r['s'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Get a bill by ID
     */
    public function getById($id) {
        $sql = "SELECT b.*, cp.name AS company_name
                FROM bills b
                LEFT JOIN companies cp ON cp.id = b.company_id
                WHERE b.id = ?
                LIMIT 1";
        
        return $this->fetchOne($sql, [$id]);
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status, $paymentDate = null, $note = '') {
        if ($status === 'PAID') {
            if (!$paymentDate) {
                $paymentDate = date('Y-m-d');
            }
            $sql = "UPDATE bills SET payment_status = 'PAID', payment_date = ?, payment_note = ? WHERE id = ?";
            $stmt = $this->query($sql, [$paymentDate, $note, $id]);
        } else {
            $sql = "UPDATE bills SET payment_status = 'UNPAID', payment_date = NULL, payment_note = ? WHERE id = ?";
            $stmt = $this->query($sql, [$note, $id]);
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Create a new bill
     */
    public function create($data) {
        $sql = "INSERT INTO bills (bill_no, issue_date, company_id, gross_amount, tax_amount, 
                net_amount, payment_status, status, pdf_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['bill_no'],
            $data['issue_date'],
            $data['company_id'],
            $data['gross_amount'],
            $data['tax_amount'],
            $data['net_amount'],
            $data['payment_status'] ?? 'UNPAID',
            $data['status'] ?? 'FINAL',
            $data['pdf_path'] ?? ''
        ];
        
        $stmt = $this->query($sql, $params);
        $insertId = $this->conn->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Get sort order SQL
     */
    private function getSortOrder($sort) {
        $orders = [
            'date_desc' => 'b.issue_date DESC',
            'date_asc' => 'b.issue_date ASC',
            'billno_asc' => 'CAST(b.bill_no AS UNSIGNED) ASC',
            'billno_desc' => 'CAST(b.bill_no AS UNSIGNED) DESC'
        ];
        
        return $orders[$sort] ?? 'b.issue_date DESC';
    }
}
