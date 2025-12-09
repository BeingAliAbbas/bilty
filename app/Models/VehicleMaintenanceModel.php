<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleMaintenanceModel extends Model
{
    protected $table            = 'vehicle_maintenance';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'entry_date', 'vehicle_no', 'expense_type', 'amount', 'narration'
    ];

    protected array $casts = [
        'amount' => 'float',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'entry_date' => 'required|valid_date',
        'vehicle_no' => 'required|max_length[50]',
        'expense_type' => 'required|max_length[100]',
        'amount' => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    public function search(array $filters)
    {
        $builder = $this;
        
        if (!empty($filters['vehicle'])) {
            $builder->like('vehicle_no', $filters['vehicle']);
        }
        
        if (!empty($filters['expense'])) {
            $builder->groupStart()
                    ->like('expense_type', $filters['expense'])
                    ->orLike('narration', $filters['expense'])
                    ->groupEnd();
        }
        
        if (!empty($filters['from_date'])) {
            $builder->where('entry_date >=', $filters['from_date']);
        }
        
        if (!empty($filters['to_date'])) {
            $builder->where('entry_date <=', $filters['to_date']);
        }
        
        return $builder->orderBy('entry_date', 'DESC')->orderBy('id', 'DESC');
    }

    public function getTotalExpense(array $filters = [])
    {
        $builder = $this->selectSum('amount');
        
        if (!empty($filters['from_date'])) {
            $builder->where('entry_date >=', $filters['from_date']);
        }
        
        if (!empty($filters['to_date'])) {
            $builder->where('entry_date <=', $filters['to_date']);
        }
        
        $result = $builder->first();
        return $result['amount'] ?? 0;
    }
}
