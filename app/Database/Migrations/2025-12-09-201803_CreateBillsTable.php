<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBillsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'bill_no' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'financial_year' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
            ],
            'issue_date' => [
                'type' => 'DATE',
            ],
            'company_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'consignment_ids' => [
                'type' => 'TEXT',
            ],
            'gross_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'tax_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '6,3',
            ],
            'tax_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'net_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'meta' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['FINAL'],
                'default' => 'FINAL',
            ],
            'payment_status' => [
                'type' => 'ENUM',
                'constraint' => ['UNPAID', 'PAID'],
                'default' => 'UNPAID',
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'payment_note' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'printed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'pdf_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('bill_no');
        $this->forge->createTable('bills');
    }

    public function down()
    {
        $this->forge->dropTable('bills');
    }
}
