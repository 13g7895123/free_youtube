<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToPlaylistsTable extends Migration
{
    public function up()
    {
        // 檢查欄位是否已存在（避免重複執行錯誤）
        if (!$this->db->fieldExists('user_id', 'playlists')) {
            // 添加 user_id 欄位
            $fields = [
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'comment' => '會員 ID',
                    'after' => 'id', // 放在 id 欄位之後
                ],
            ];
            
            $this->forge->addColumn('playlists', $fields);
            
            // 添加索引
            $this->db->query('ALTER TABLE `playlists` ADD INDEX `idx_user_id` (`user_id`)');
            
            // 添加外鍵約束
            $this->db->query('
                ALTER TABLE `playlists`
                ADD CONSTRAINT `fk_playlists_user_id`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ');
            
            log_message('info', 'Successfully added user_id column to playlists table');
        } else {
            log_message('info', 'user_id column already exists in playlists table, skipping');
        }
    }

    public function down()
    {
        // 檢查欄位是否存在
        if ($this->db->fieldExists('user_id', 'playlists')) {
            // 先刪除外鍵約束
            $this->db->query('ALTER TABLE `playlists` DROP FOREIGN KEY `fk_playlists_user_id`');
            
            // 刪除索引
            $this->db->query('ALTER TABLE `playlists` DROP INDEX `idx_user_id`');
            
            // 再刪除欄位
            $this->forge->dropColumn('playlists', 'user_id');
            
            log_message('info', 'Successfully removed user_id column from playlists table');
        }
    }
}
