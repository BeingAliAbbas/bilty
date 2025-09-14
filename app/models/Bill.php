<?php

require_once 'Model.php';

class Bill extends Model
{
    protected $table = 'bills';

    /**
     * Get bills with company information and filters
     */
    public function getBillsWithFilters($filters = [])
    {
        $where = [];
        $params = [];

        // Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "b.payment_status = ?";
            $params[] = $filters['status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $where[] = "(b.bill_no LIKE ? OR cp.name LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        // Order by
        $orderBy = "b.issue_date DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'date_asc':
                    $orderBy = "b.issue_date ASC";
                    break;
                case 'billno_asc':
                    $orderBy = "CAST(b.bill_no AS UNSIGNED) ASC";
                    break;
                case 'billno_desc':
                    $orderBy = "CAST(b.bill_no AS UNSIGNED) DESC";
                    break;
            }
        }

        // Pagination
        $page = max(1, intval($filters['page'] ?? 1));
        $pageSize = intval($filters['pageSize'] ?? 20);
        $offset = ($page - 1) * $pageSize;

        // Get total count
        $countSql = "SELECT COUNT(*) as cnt FROM {$this->table} b LEFT JOIN companies cp ON cp.id = b.company_id {$whereSql}";
        $countStmt = $this->execute($countSql, $params);
        $countResult = $countStmt->get_result();
        $totalRows = ($countRow = $countResult->fetch_assoc()) ? (int)$countRow['cnt'] : 0;
        $countStmt->close();

        // Get data
        $sql = "SELECT b.id, b.bill_no, b.issue_date, b.company_id,
                       b.gross_amount, b.tax_amount, b.net_amount,
                       b.payment_status, b.payment_date, b.payment_note,
                       b.status, b.pdf_path,
                       cp.name AS company_name
                FROM {$this->table} b
                LEFT JOIN companies cp ON cp.id = b.company_id
                {$whereSql}
                ORDER BY {$orderBy}
                LIMIT {$offset}, {$pageSize}";

        $stmt = $this->execute($sql, $params);
        $result = $stmt->get_result();
        $bills = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }
        }

        $stmt->close();

        return [
            'bills' => $bills,
            'totalRows' => $totalRows,
            'currentPage' => $page,
            'totalPages' => max(1, (int)ceil($totalRows / $pageSize)),
            'pageSize' => $pageSize
        ];
    }

    /**
     * Get bill statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total' => 0,
            'paid' => 0,
            'unpaid' => 0,
            'out_amt' => 0.0
        ];

        $sql = "SELECT payment_status, COUNT(*) as c, SUM(net_amount) as s FROM {$this->table} GROUP BY payment_status";
        $result = $this->db->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['total'] += (int)$row['c'];
                if ($row['payment_status'] === 'PAID') {
                    $stats['paid'] = (int)$row['c'];
                } else {
                    $stats['unpaid'] += (int)$row['c'];
                    $stats['out_amt'] += (float)$row['s'];
                }
            }
            $result->free();
        }

        return $stats;
    }

    /**
     * Update payment status of a bill
     */
    public function updatePaymentStatus($billId, $status, $paymentDate = null, $note = '')
    {
        $data = [
            'payment_status' => $status,
            'payment_note' => $note
        ];

        if ($status === 'PAID' && $paymentDate) {
            $data['payment_date'] = $paymentDate;
        } elseif ($status === 'UNPAID') {
            $data['payment_date'] = null;
        }

        return $this->update($billId, $data);
    }

    /**
     * Create a new bill
     */
    public function createBill($data)
    {
        // Validation
        $required = ['bill_no', 'company_id', 'gross_amount', 'net_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
            }
        }

        // Set defaults
        $billData = [
            'bill_no' => trim($data['bill_no']),
            'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
            'company_id' => intval($data['company_id']),
            'gross_amount' => floatval($data['gross_amount']),
            'tax_amount' => floatval($data['tax_amount'] ?? 0),
            'net_amount' => floatval($data['net_amount']),
            'payment_status' => $data['payment_status'] ?? 'UNPAID',
            'payment_date' => $data['payment_date'] ?? null,
            'payment_note' => trim($data['payment_note'] ?? ''),
            'status' => $data['status'] ?? 'DRAFT',
            'pdf_path' => trim($data['pdf_path'] ?? '')
        ];

        return $this->create($billData);
    }

    /**
     * Get bill by ID with company information
     */
    public function getBillWithCompany($id)
    {
        $sql = "SELECT b.*, cp.name AS company_name, cp.address AS company_address
                FROM {$this->table} b
                LEFT JOIN companies cp ON cp.id = b.company_id
                WHERE b.id = ? LIMIT 1";

        $stmt = $this->execute($sql, [$id]);
        $result = $stmt->get_result();
        $bill = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $bill;
    }
}