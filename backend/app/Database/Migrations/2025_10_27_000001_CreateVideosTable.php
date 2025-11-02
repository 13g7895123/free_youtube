<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideosTable extends Migration
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
            'video_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
                'comment' => 'YouTube 影片 ID',
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '影片標題',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => '影片描述',
            ],
            'duration' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'comment' => '影片長度（秒）',
            ],
            'thumbnail_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => '縮圖 URL',
            ],
            'youtube_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'comment' => 'YouTube 網址',
            ],
            'channel_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '頻道 ID',
            ],
            'channel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '頻道名稱',
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '發布時間',
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

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('video_id');
        $this->forge->addKey('channel_id');
        $this->forge->addKey('created_at');

        $this->forge->createTable('videos');
    }

    public function down()
    {
        $this->forge->dropTable('videos');
    }
}
