<?php

namespace App\Models;

use CodeIgniter\Model;

class BillModel extends Model
{
    protected $table            = 'bills';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'company_id', 'bill_no', 'issue_date', 'gross_amount', 'tax_amount',
        'net_amount', 'payment_status', 'payment_date', 'payment_note',
        'status', 'pdf_path'
    ];

    protected array $casts = [
        'company_id' => 'int',
        'gross_amount' => 'float',
        'tax_amount' => 'float',
        'net_amount' => 'float',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'company_id' => 'required|is_natural_no_zero',
        'bill_no' => 'required|max_length[50]',
        'issue_date' => 'required|valid_date',
        'net_amount' => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    public function getWithCompany($id = null)
    {
        $builder = $this->select('bills.*, companies.name as company_name')
                        ->join('companies', 'companies.id = bills.company_id');
        
        if ($id !== null) {
            return $builder->where('bills.id', $id)->first();
        }
        
        return $builder->findAll();
    }

    public function search(array $filters)
    {
        $builder = $this->select('bills.*, companies.name as company_name')
                        ->join('companies', 'companies.id = bills.company_id');
        
        if (!empty($filters['q'])) {
            $builder->groupStart()
                    ->like('bill_no', $filters['q'])
                    ->orLike('companies.name', $filters['q'])
                    ->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            $builder->where('payment_status', $filters['status']);
        }
        
        if (!empty($filters['from_date'])) {
            $builder->where('issue_date >=', $filters['from_date']);
        }
        
        if (!empty($filters['to_date'])) {
            $builder->where('issue_date <=', $filters['to_date']);
        }
        
        return $builder->orderBy('issue_date', 'DESC')->orderBy('id', 'DESC');
    }

    public function getStats()
    {
        $stats = [
            'total' => $this->countAll(),
            'paid' => $this->where('payment_status', 'PAID')->countAllResults(false),
            'unpaid' => $this->where('payment_status', 'UNPAID')->countAllResults(false),
        ];

        $unpaidSum = $this->selectSum('net_amount')
                          ->where('payment_status', 'UNPAID')
                          ->first();
        
        $stats['outstanding_amount'] = $unpaidSum['net_amount'] ?? 0;
        
        return $stats;
    }
}
