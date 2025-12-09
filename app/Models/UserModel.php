<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username', 'email', 'password_hash', 'full_name', 'role',
        'is_active', 'reset_token', 'reset_token_expires', 'last_login'
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'username' => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'email' => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'password_hash' => 'required',
        'role' => 'required|in_list[admin,user]',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $beforeInsert         = ['hashPassword'];
    protected $beforeUpdate         = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (!isset($data['data']['password_hash'])) {
            return $data;
        }

        // Only hash if it's a plain password (not already hashed)
        if (strlen($data['data']['password_hash']) < 60) {
            $data['data']['password_hash'] = password_hash(
                $data['data']['password_hash'],
                PASSWORD_DEFAULT
            );
        }

        return $data;
    }

    public function verifyPassword(string $username, string $password)
    {
        $user = $this->where('username', $username)
                     ->orWhere('email', $username)
                     ->first();

        if (!$user || !$user['is_active']) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            // Update last login
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }

        return false;
    }

    public function generateResetToken($email)
    {
        $user = $this->where('email', $email)->first();
        
        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->update($user['id'], [
            'reset_token' => $token,
            'reset_token_expires' => $expires,
        ]);

        return $token;
    }

    public function verifyResetToken($token)
    {
        $user = $this->where('reset_token', $token)
                     ->where('reset_token_expires >', date('Y-m-d H:i:s'))
                     ->first();

        return $user ?: false;
    }

    public function resetPassword($token, $newPassword)
    {
        $user = $this->verifyResetToken($token);
        
        if (!$user) {
            return false;
        }

        $this->update($user['id'], [
            'password_hash' => $newPassword, // Will be hashed by beforeUpdate
            'reset_token' => null,
            'reset_token_expires' => null,
        ]);

        return true;
    }
}
