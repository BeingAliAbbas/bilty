<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'consignment_id', 'payment_date', 'amount', 'payment_method', 'notes'
    ];

    protected array $casts = [
        'consignment_id' => 'int',
        'amount' => 'float',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'consignment_id' => 'required|is_natural_no_zero',
        'payment_date' => 'required|valid_date',
        'amount' => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    public function getPaymentsForConsignment($consignmentId)
    {
        return $this->where('consignment_id', $consignmentId)
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }
}
