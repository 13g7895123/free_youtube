<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlaylistsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '播放清單名稱',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => '播放清單描述',
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'comment' => '是否啟用',
            ],
            'item_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'default' => 0,
                'comment' => '項目數量',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '軟刪除時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('is_active');
        $this->forge->addKey('created_at');

        $this->forge->createTable('playlists');
    }

    public function down()
    {
        $this->forge->dropTable('playlists');
    }
}
