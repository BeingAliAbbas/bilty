<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVehicleMaintenanceTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'entry_date' => [
                'type' => 'DATE',
            ],
            'vehicle_no' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'expense_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'narration' => [
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
        $this->forge->addKey('entry_date');
        $this->forge->addKey('vehicle_no');
        $this->forge->createTable('vehicle_maintenance');
    }

    public function down()
    {
        $this->forge->dropTable('vehicle_maintenance');
    }
}
