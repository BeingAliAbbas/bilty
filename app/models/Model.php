<?php

abstract class Model
{
    protected $db;
    protected $table;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Helper: safe dynamic bind for mysqli_stmt using automatic type detection
     */
    protected function bindParams($stmt, $params)
    {
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
     * Execute a prepared statement with parameters
     */
    protected function execute($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        if (!empty($params)) {
            if (!$this->bindParams($stmt, $params)) {
                $stmt->close();
                throw new Exception("Failed to bind parameters");
            }
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Execute failed: " . $error);
        }

        return $stmt;
    }

    /**
     * Get all records
     */
    public function all($orderBy = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $result = $this->db->query($sql);
        $records = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            $result->free();
        }
        
        return $records;
    }

    /**
     * Find record by ID
     */
    public function find($id)
    {
        $stmt = $this->execute("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        $result = $stmt->get_result();
        $record = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        
        return $record;
    }

    /**
     * Create new record
     */
    public function create($data)
    {
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        $stmt = $this->execute($sql, array_values($data));
        
        $id = $stmt->insert_id;
        $stmt->close();
        
        return $id;
    }

    /**
     * Update record
     */
    public function update($id, $data)
    {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->execute($sql, $params);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows > 0;
    }

    /**
     * Delete record
     */
    public function delete($id)
    {
        $stmt = $this->execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows > 0;
    }
}