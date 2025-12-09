<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'consignment_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'payment_date' => [
                'type' => 'DATE',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'cash',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('consignment_id');
        $this->forge->addKey('payment_date');
        $this->forge->addForeignKey('consignment_id', 'consignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payments');
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}
