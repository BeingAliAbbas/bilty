<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateVehicleMaintenanceTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
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
                'constraint' => 120,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0.00,
            ],
            'narration' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('entry_date', false, false, 'idx_entry_date');
        $this->forge->addKey('vehicle_no', false, false, 'idx_vehicle_no');
        $this->forge->addKey('expense_type', false, false, 'idx_expense_type');
        $this->forge->createTable('vehicle_maintenance');
    }

    public function down()
    {
        $this->forge->dropTable('vehicle_maintenance');
    }
}
