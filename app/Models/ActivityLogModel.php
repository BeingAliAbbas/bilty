<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table            = 'activity_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'action', 'entity_type', 'entity_id', 'description',
        'ip_address', 'user_agent'
    ];

    protected array $casts = [
        'user_id' => 'int',
        'entity_id' => 'int',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules      = [
        'action' => 'required|max_length[100]',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    public function log($action, $entityType = null, $entityId = null, $description = null)
    {
        $data = [
            'user_id' => session()->get('user_id'),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        return $this->insert($data);
    }

    public function getRecentActivity($limit = 50)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                    ->join('users', 'users.id = activity_logs.user_id', 'left')
                    ->orderBy('activity_logs.created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getActivityForEntity($entityType, $entityId)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                    ->join('users', 'users.id = activity_logs.user_id', 'left')
                    ->where('entity_type', $entityType)
                    ->where('entity_id', $entityId)
                    ->orderBy('activity_logs.created_at', 'DESC')
                    ->findAll();
    }
}
