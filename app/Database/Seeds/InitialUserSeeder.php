<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialUserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'username' => 'admin',
            'email' => 'admin@bilty.local',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'Administrator',
            'role' => 'admin',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($data);
        
        echo "Initial admin user created.\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Please change the password after first login!\n";
    }
}
