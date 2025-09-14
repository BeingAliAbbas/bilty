<?php

require_once 'Model.php';

class Consignment extends Model
{
    protected $table = 'consignments';

    /**
     * Get next bilty number (based on MAX(id) + 1)
     */
    public function getNextBiltyNo()
    {
        $next = 1;
        $res = $this->db->query("SELECT MAX(id) AS maxid FROM {$this->table}");
        if ($res) {
            $row = $res->fetch_assoc();
            $res->free();
            $next = (int)($row['maxid'] ?? 0) + 1;
        }
        return (string)$next;
    }

    /**
     * Create a new consignment with full validation and retry logic
     */
    public function createConsignment($data)
    {
        // Extract and validate data
        $bilty_no = trim($data['bilty_no'] ?? $this->getNextBiltyNo());
        $date = $data['date'] ?? date('Y-m-d');
        $company_id = intval($data['company_id'] ?? 0);
        $vehicle_no = trim($data['vehicle_no'] ?? '');
        $vehicle_owner = (($data['vehicle_owner'] ?? 'own') === 'rental') ? 'rental' : 'own';
        $driver_name = trim($data['driver_name'] ?? '');
        $driver_number = trim($data['driver_number'] ?? '');
        $vehicle_type = trim($data['vehicle_type'] ?? '');
        $sender_name = trim($data['sender_name'] ?? '');
        $from_city = trim($data['from_city'] ?? '');
        $to_city = trim($data['to_city'] ?? '');
        $qty = intval($data['qty'] ?? 0);
        $details = trim($data['details'] ?? '');
        $km = intval($data['km'] ?? 0);
        $rate = floatval($data['rate'] ?? 0);

        // Calculate server-side: Amount = km * rate
        $amount = round($km * $rate, 2);
        $advance = floatval($data['advance'] ?? 0);
        $balance = round($amount - $advance, 2);

        // Validations
        $errors = [];
        if ($bilty_no === '') {
            $errors[] = "Bilty number is required.";
        } elseif (strlen($bilty_no) > 50) {
            $errors[] = "Bilty number must be 50 characters or less.";
        }

        if ($company_id <= 0) {
            $errors[] = "Please select a company.";
        }
        if ($qty < 0) {
            $errors[] = "Quantity cannot be negative.";
        }
        if ($km < 0) {
            $errors[] = "Distance (KM) cannot be negative.";
        }
        if ($rate < 0) {
            $errors[] = "Rate cannot be negative.";
        }
        if ($advance < 0) {
            $errors[] = "Advance cannot be negative.";
        }

        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }

        // Add extra info to details
        $extra = [];
        $extra[] = "Vehicle: " . ($vehicle_owner === 'rental' ? "Rental" : "Own");
        if ($driver_number !== '') {
            $extra[] = "Driver number: " . $driver_number;
        }
        if (!empty($extra)) {
            $details = trim($details);
            if ($details !== '') $details .= "\n\n";
            $details .= "Additional info:\n" . implode("\n", $extra);
        }

        // Prepare data for insertion
        $insertData = [
            'company_id' => $company_id,
            'bilty_no' => $bilty_no,
            'date' => $date,
            'vehicle_no' => $vehicle_no,
            'driver_name' => $driver_name,
            'vehicle_type' => $vehicle_type,
            'sender_name' => $sender_name,
            'from_city' => $from_city,
            'to_city' => $to_city,
            'qty' => $qty,
            'details' => $details,
            'km' => $km,
            'rate' => $rate,
            'amount' => $amount,
            'advance' => $advance,
            'balance' => $balance
        ];

        // Retry logic for duplicate bilty_no
        $attempts = 0;
        $maxAttempts = 3;
        $inserted = false;

        while ($attempts < $maxAttempts && !$inserted) {
            try {
                $id = $this->create($insertData);
                $inserted = true;
                return $id;
            } catch (Exception $e) {
                if ($this->db->errno === 1062) { // duplicate bilty_no
                    $attempts++;
                    $bilty_no = $this->getNextBiltyNo();
                    $insertData['bilty_no'] = $bilty_no;
                    continue;
                } else {
                    throw $e;
                }
            }
        }

        if (!$inserted) {
            throw new Exception("Failed to save bilty after multiple attempts. Please try again.");
        }
    }

    /**
     * Get consignments with company information and filters
     */
    public function getWithCompany($filters = [])
    {
        $where = [];
        $params = [];

        // Build WHERE clause based on filters
        if (!empty($filters['company_id'])) {
            $where[] = "c.company_id = ?";
            $params[] = intval($filters['company_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $where[] = "(c.bilty_no LIKE ? OR cp.name LIKE ? OR c.driver_name LIKE ? OR c.from_city LIKE ? OR c.to_city LIKE ? OR c.vehicle_no LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "SELECT c.*, cp.name AS company_name 
                FROM {$this->table} c 
                JOIN companies cp ON cp.id = c.company_id 
                {$whereSql} 
                ORDER BY c.date DESC, c.id DESC";

        $stmt = $this->execute($sql, $params);
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
     * Get consignment with company details by ID
     */
    public function getWithCompanyById($id)
    {
        $sql = "SELECT c.*, cp.name AS company_name, cp.address AS company_address 
                FROM {$this->table} c 
                JOIN companies cp ON cp.id = c.company_id 
                WHERE c.id = ? LIMIT 1";

        $stmt = $this->execute($sql, [$id]);
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row;
    }

    /**
     * Get multiple consignments by IDs for bulk operations
     */
    public function getByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT c.*, cp.name AS company_name 
                FROM {$this->table} c 
                JOIN companies cp ON cp.id = c.company_id 
                WHERE c.id IN ({$placeholders}) 
                ORDER BY c.date DESC, c.id DESC";

        $stmt = $this->execute($sql, $ids);
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
}