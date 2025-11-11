<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLineLoginTables extends Migration
{
    public function up()
    {
        // 1. users 表
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'line_user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'display_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'avatar_url' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'soft_deleted'],
                'default' => 'active',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('line_user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted_at');
        $this->forge->createTable('users');

        // 2. user_tokens 表
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'access_token' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => false,
            ],
            'refresh_token' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ],
            'token_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'Bearer',
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'device_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('expires_at');
        $this->forge->addKey(['access_token'], false, false, 255);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_tokens');

        // 3. video_library 表
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'video_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'thumbnail_url' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'duration' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'channel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'video_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('video_library');

        // 4. playlists 表 - 已在 2025_10_27_000002_CreatePlaylistsTable.php 中定義
        // user_id 欄位將由 2025_11_03_000001_AddUserIdToPlaylistsTable.php 添加
        // 跳過此表的創建，避免重複定義
        
        // 5. playlist_items 表 - 已在 2025_10_27_000003_CreatePlaylistItemsTable.php 中定義
        // 跳過此表的創建，避免重複定義

        // 6. guest_sessions 表
        $this->forge->addField([
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
            'history_data' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('session_id');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('guest_sessions');
    }

    public function down()
    {
        // 不刪除 playlist_items 和 playlists，因為它們在其他 Migration 中定義
        // $this->forge->dropTable('playlist_items', true);
        // $this->forge->dropTable('playlists', true);
        
        $this->forge->dropTable('guest_sessions', true);
        $this->forge->dropTable('video_library', true);
        $this->forge->dropTable('user_tokens', true);
        $this->forge->dropTable('users', true);
    }
}
