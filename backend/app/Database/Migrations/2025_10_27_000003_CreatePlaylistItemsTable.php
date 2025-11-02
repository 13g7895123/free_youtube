<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlaylistItemsTable extends Migration
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
            'playlist_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => '播放清單 ID',
            ],
            'video_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => '影片 ID',
            ],
            'position' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => '播放位置',
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

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('playlist_id');
        $this->forge->addKey('video_id');
        $this->forge->addUniqueKey(['playlist_id', 'position']);

        // 添加外鍵約束
        $this->forge->addForeignKey('playlist_id', 'playlists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('video_id', 'videos', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('playlist_items');
    }

    public function down()
    {
        $this->forge->dropTable('playlist_items');
    }
}
