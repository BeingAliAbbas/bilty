<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'setting_key';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'setting_key', 'setting_value'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'setting_key' => 'required|max_length[100]',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;

    /**
     * Get a setting value by key
     */
    public function get(string $key, $default = null)
    {
        $setting = $this->find($key);
        return $setting ? $setting['setting_value'] : $default;
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value)
    {
        $data = [
            'setting_key' => $key,
            'setting_value' => $value,
        ];

        // Use replace to insert or update
        return $this->save($data);
    }
}
