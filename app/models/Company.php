<?php

require_once 'Model.php';

class Company extends Model
{
    protected $table = 'companies';

    /**
     * Get all companies ordered by name
     */
    public function getAll()
    {
        return $this->all('name ASC');
    }

    /**
     * Create a new company with validation
     */
    public function createCompany($name, $address = '')
    {
        $name = trim($name);
        $address = trim($address);

        // Validation
        if (empty($name)) {
            throw new Exception("Company name is required.");
        }
        if (mb_strlen($name) > 150) {
            throw new Exception("Company name too long (max 150).");
        }
        if ($address !== '' && mb_strlen($address) > 255) {
            throw new Exception("Address too long (max 255).");
        }

        // Check for duplicates (case-insensitive)
        if ($this->existsByName($name)) {
            throw new Exception("A company with this name already exists.");
        }

        $data = [
            'name' => $name,
            'address' => $address
        ];

        return $this->create($data);
    }

    /**
     * Check if company exists by name (case-insensitive)
     */
    public function existsByName($name)
    {
        $stmt = $this->execute("SELECT id FROM {$this->table} WHERE LOWER(name) = LOWER(?) LIMIT 1", [$name]);
        $result = $stmt->get_result();
        $exists = $result && $result->fetch_assoc();
        $stmt->close();
        
        return (bool)$exists;
    }

    /**
     * Get company with address column check
     */
    public function getAllWithAddressCheck()
    {
        // Check if address column exists
        $hasAddress = false;
        $colCheck = $this->db->query("SHOW COLUMNS FROM companies LIKE 'address'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasAddress = true;
        }
        if ($colCheck) {
            $colCheck->close();
        }

        $companies = [];
        if ($hasAddress) {
            $res = $this->db->query("SELECT id, name, address FROM companies ORDER BY name ASC");
        } else {
            $res = $this->db->query("SELECT id, name FROM companies ORDER BY name ASC");
        }
        
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $companies[] = $row;
            }
            $res->free();
        }

        return $companies;
    }

    /**
     * Search companies by name
     */
    public function search($query)
    {
        $stmt = $this->execute(
            "SELECT * FROM {$this->table} WHERE name LIKE ? ORDER BY name ASC",
            ["%{$query}%"]
        );
        
        $result = $stmt->get_result();
        $companies = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $companies[] = $row;
            }
        }
        
        $stmt->close();
        return $companies;
    }
}