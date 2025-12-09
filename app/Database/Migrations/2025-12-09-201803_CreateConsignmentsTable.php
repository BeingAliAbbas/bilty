<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConsignmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'bilty_no' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'vehicle_no' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'driver_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'vehicle_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'sender_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'from_city' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'to_city' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'qty' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'km' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'rate_type' => [
                'type' => 'ENUM',
                'constraint' => ['Fixed', 'PerKM'],
                'default' => 'PerKM',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'advance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'balance' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addKey('bilty_no');
        $this->forge->addKey('date');
        $this->forge->addKey(['from_city', 'to_city']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('consignments');
    }

    public function down()
    {
        $this->forge->dropTable('consignments');
    }
}
