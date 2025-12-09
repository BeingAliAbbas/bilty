<?php

namespace App\Models;

use CodeIgniter\Model;

class ConsignmentModel extends Model
{
    protected $table            = 'consignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'company_id', 'bilty_no', 'date', 'vehicle_no', 'driver_name',
        'vehicle_type', 'sender_name', 'from_city', 'to_city', 'qty',
        'details', 'km', 'rate', 'rate_type', 'amount', 'advance', 'balance'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'company_id' => 'int',
        'qty' => 'int',
        'km' => 'int',
        'rate' => 'float',
        'amount' => 'float',
        'advance' => 'float',
        'balance' => 'float',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'company_id' => 'required|is_natural_no_zero',
        'bilty_no' => 'required|max_length[50]',
        'date' => 'required|valid_date',
        'amount' => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    public function getWithCompany($id = null)
    {
        $builder = $this->select('consignments.*, companies.name as company_name')
                        ->join('companies', 'companies.id = consignments.company_id');
        
        if ($id !== null) {
            return $builder->where('consignments.id', $id)->first();
        }
        
        return $builder->findAll();
    }
    
    public function search(array $filters)
    {
        $builder = $this->select('consignments.*, companies.name as company_name')
                        ->join('companies', 'companies.id = consignments.company_id');
        
        if (!empty($filters['q'])) {
            $builder->groupStart()
                    ->like('bilty_no', $filters['q'])
                    ->orLike('companies.name', $filters['q'])
                    ->orLike('driver_name', $filters['q'])
                    ->orLike('from_city', $filters['q'])
                    ->orLike('to_city', $filters['q'])
                    ->orLike('vehicle_no', $filters['q'])
                    ->groupEnd();
        }
        
        if (!empty($filters['company_id'])) {
            $builder->where('company_id', $filters['company_id']);
        }
        
        if (!empty($filters['from_date'])) {
            $builder->where('date >=', $filters['from_date']);
        }
        
        if (!empty($filters['to_date'])) {
            $builder->where('date <=', $filters['to_date']);
        }
        
        return $builder->orderBy('date', 'DESC')->orderBy('id', 'DESC');
    }
    
    public function getNextBiltyNo()
    {
        $result = $this->selectMax('id')->first();
        return (isset($result['id']) ? (int)$result['id'] + 1 : 1);
    }
}
