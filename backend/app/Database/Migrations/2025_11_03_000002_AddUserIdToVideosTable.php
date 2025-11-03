<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToVideosTable extends Migration
{
    public function up()
    {
        // 檢查欄位是否已存在（避免重複執行錯誤）
        if (!$this->db->fieldExists('user_id', 'videos')) {
            // 添加 user_id 欄位
            $fields = [
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true, // 先設為可為空，以便處理現有資料
                    'comment' => '影片擁有者 ID',
                    'after' => 'id', // 放在 id 欄位之後
                ],
            ];
            
            $this->forge->addColumn('videos', $fields);
            
            // 添加索引
            $this->db->query('ALTER TABLE `videos` ADD INDEX `idx_user_id` (`user_id`)');
            
            // 添加外鍵約束
            $this->db->query('
                ALTER TABLE `videos`
                ADD CONSTRAINT `fk_videos_user_id`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ');
            
            log_message('info', 'Successfully added user_id column to videos table');
            
            // 注意：如果需要將欄位設為 NOT NULL，請在填充現有資料後執行：
            // ALTER TABLE `videos` MODIFY `user_id` INT(11) UNSIGNED NOT NULL;
        } else {
            log_message('info', 'user_id column already exists in videos table, skipping');
        }
    }

    public function down()
    {
        // 檢查欄位是否存在
        if ($this->db->fieldExists('user_id', 'videos')) {
            // 先刪除外鍵約束
            $this->db->query('ALTER TABLE `videos` DROP FOREIGN KEY `fk_videos_user_id`');
            
            // 刪除索引
            $this->db->query('ALTER TABLE `videos` DROP INDEX `idx_user_id`');
            
            // 再刪除欄位
            $this->forge->dropColumn('videos', 'user_id');
            
            log_message('info', 'Successfully removed user_id column from videos table');
        }
    }
}
