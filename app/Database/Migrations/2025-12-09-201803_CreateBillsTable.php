<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillsTable extends Migration
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
            'bill_no' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'issue_date' => [
                'type' => 'DATE',
            ],
            'gross_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'tax_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'net_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'payment_status' => [
                'type' => 'ENUM',
                'constraint' => ['PAID', 'UNPAID', 'PARTIAL'],
                'default' => 'UNPAID',
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'payment_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'draft',
            ],
            'pdf_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addKey('company_id');
        $this->forge->addKey('bill_no');
        $this->forge->addKey('issue_date');
        $this->forge->addKey('payment_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bills');
    }

    public function down()
    {
        $this->forge->dropTable('bills');
    }
}
