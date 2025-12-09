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
        'bill_no', 'financial_year', 'issue_date', 'company_id', 'consignment_ids',
        'gross_amount', 'tax_percent', 'tax_amount', 'net_amount', 'meta',
        'status', 'payment_status', 'payment_date', 'payment_note', 
        'printed_at', 'pdf_path'
    ];

    protected array $casts = [
        'company_id' => '?int',
        'gross_amount' => 'float',
        'tax_percent' => 'float',
        'tax_amount' => 'float',
        'net_amount' => 'float',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    protected $validationRules      = [
        'issue_date' => 'required|valid_date',
        'consignment_ids' => 'required',
        'net_amount' => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    public function getWithCompany($id = null)
    {
        $builder = $this->select('bills.*, companies.name as company_name')
                        ->join('companies', 'companies.id = bills.company_id', 'left');
        
        if ($id !== null) {
            return $builder->where('bills.id', $id)->first();
        }
        
        return $builder->findAll();
    }

    public function search(array $filters)
    {
        $builder = $this->select('bills.*, companies.name as company_name')
                        ->join('companies', 'companies.id = bills.company_id', 'left');
        
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
