-- Create videos table
CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `video_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'YouTube 影片 ID',
    `title` VARCHAR(255) NOT NULL COMMENT '影片標題',
    `description` TEXT NULL COMMENT '影片描述',
    `duration` INT UNSIGNED NULL COMMENT '影片長度（秒）',
    `thumbnail_url` VARCHAR(500) NULL COMMENT '縮圖 URL',
    `youtube_url` VARCHAR(500) NOT NULL COMMENT 'YouTube 網址',
    `channel_id` VARCHAR(100) NULL COMMENT '頻道 ID',
    `channel_name` VARCHAR(255) NULL COMMENT '頻道名稱',
    `published_at` DATETIME NULL COMMENT '發布時間',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL COMMENT '軟刪除時間',
    PRIMARY KEY (`id`),
    KEY `idx_video_id` (`video_id`),
    KEY `idx_channel_id` (`channel_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create playlists table
CREATE TABLE IF NOT EXISTS `playlists` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT '播放清單名稱',
    `description` TEXT NULL COMMENT '播放清單描述',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否啟用',
    `item_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '項目數量',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL COMMENT '軟刪除時間',
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create playlist_items table
CREATE TABLE IF NOT EXISTS `playlist_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `playlist_id` INT UNSIGNED NOT NULL COMMENT '播放清單 ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '影片 ID',
    `position` INT UNSIGNED NOT NULL COMMENT '播放位置',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_playlist_id` (`playlist_id`),
    KEY `idx_video_id` (`video_id`),
    UNIQUE KEY `unique_playlist_position` (`playlist_id`, `position`),
    CONSTRAINT `fk_playlist_items_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_playlist_items_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
