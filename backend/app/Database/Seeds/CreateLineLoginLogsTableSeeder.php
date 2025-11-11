<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CreateLineLoginLogsTableSeeder extends Seeder
{
    public function run()
    {
        $forge = \Config\Database::forge();

        // 檢查表格是否已存在
        if ($forge->_tableExists('line_login_logs')) {
            echo "Table 'line_login_logs' already exists.\n";
            return;
        }

        $forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => false,
            ],
            'step' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['success', 'error', 'warning'],
                'null' => false,
            ],
            'line_user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'request_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'response_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $forge->addKey('id', true);
        $forge->addKey('session_id');
        $forge->addKey('status');
        $forge->addKey('created_at');
        $forge->createTable('line_login_logs');

        echo "Table 'line_login_logs' created successfully.\n";
    }
}
