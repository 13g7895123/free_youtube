# 資料模型設計：播放清單資料庫架構

**功能分支**: `002-playlist-database`  
**資料庫**: MariaDB 10.6+  
**字元集**: utf8mb4_unicode_ci  
**引擎**: InnoDB

## 資料庫概覽

本系統使用三個主要資料表來管理 YouTube 影片和播放清單：

1. **videos** - 儲存 YouTube 影片的基本資訊
2. **playlists** - 儲存使用者建立的播放清單
3. **playlist_items** - 建立播放清單與影片的多對多關聯

## ER 圖關聯

```
┌─────────────┐           ┌──────────────────┐           ┌─────────────┐
│  playlists  │           │  playlist_items  │           │   videos    │
├─────────────┤           ├──────────────────┤           ├─────────────┤
│ id (PK)     │◄─────────┤ playlist_id (FK) │           │ id (PK)     │
│ name        │    1:N    │ video_id (FK)    ├──────────►│ video_id    │
│ description │           │ position         │    N:1    │ title       │
│ is_active   │           │ created_at       │           │ description │
│ created_at  │           └──────────────────┘           │ ...         │
│ updated_at  │                                          │ created_at  │
└─────────────┘                                          │ updated_at  │
                                                         └─────────────┘
```

## 資料表結構

### 1. videos（影片資料表）

**用途**: 儲存所有 YouTube 影片的詳細資訊

```sql
CREATE TABLE videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主鍵 ID',
    video_id VARCHAR(20) NOT NULL UNIQUE COMMENT 'YouTube 影片 ID',
    title VARCHAR(255) NOT NULL COMMENT '影片標題',
    description TEXT COMMENT '影片描述',
    thumbnail_url VARCHAR(500) COMMENT '縮圖網址',
    duration INT UNSIGNED COMMENT '影片時長（秒）',
    channel_name VARCHAR(255) COMMENT '頻道名稱',
    channel_id VARCHAR(50) COMMENT 'YouTube 頻道 ID',
    youtube_url VARCHAR(500) NOT NULL COMMENT 'YouTube 完整網址',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    -- 索引設計
    INDEX idx_video_id (video_id),
    INDEX idx_title (title),
    INDEX idx_created_at (created_at),
    FULLTEXT INDEX ft_title_description (title, description)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='YouTube 影片資料表';
```

#### 欄位說明

| 欄位名稱 | 資料型別 | 必填 | 說明 | 範例 |
|---------|---------|------|------|------|
| id | INT UNSIGNED | ✓ | 自動遞增主鍵 | 1 |
| video_id | VARCHAR(20) | ✓ | YouTube 影片唯一識別碼 | dQw4w9WgXcQ |
| title | VARCHAR(255) | ✓ | 影片標題 | Never Gonna Give You Up |
| description | TEXT | ✗ | 影片描述內容 | Official music video... |
| thumbnail_url | VARCHAR(500) | ✗ | 縮圖圖片網址 | https://i.ytimg.com/vi/... |
| duration | INT UNSIGNED | ✗ | 影片長度（秒） | 213 |
| channel_name | VARCHAR(255) | ✗ | 頻道名稱 | Rick Astley |
| channel_id | VARCHAR(50) | ✗ | YouTube 頻道 ID | UCuAXFkgsw1L7xaCfnd5JJOw |
| youtube_url | VARCHAR(500) | ✓ | 完整的 YouTube 網址 | https://youtube.com/watch?v=... |
| created_at | DATETIME | ✓ | 資料建立時間 | 2025-10-27 12:00:00 |
| updated_at | DATETIME | ✓ | 資料最後更新時間 | 2025-10-27 12:00:00 |

#### 索引策略

- **PRIMARY KEY (id)**: 主鍵索引，用於快速查找
- **UNIQUE INDEX (video_id)**: 確保 YouTube 影片 ID 唯一性，防止重複儲存
- **INDEX (title)**: 支援標題搜尋與排序
- **INDEX (created_at)**: 支援依時間排序（最新、最舊）
- **FULLTEXT INDEX (title, description)**: 全文檢索，支援中英文搜尋

#### 業務規則

1. `video_id` 必須唯一，同一支 YouTube 影片只能儲存一次
2. `title` 和 `youtube_url` 為必填欄位
3. `duration` 儲存為秒數，前端顯示時轉換為 mm:ss 格式
4. `thumbnail_url` 通常使用 YouTube 提供的縮圖 URL

---

### 2. playlists（播放清單資料表）

**用途**: 儲存使用者建立的播放清單資訊

```sql
CREATE TABLE playlists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主鍵 ID',
    name VARCHAR(255) NOT NULL COMMENT '播放清單名稱',
    description TEXT COMMENT '播放清單描述',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否啟用（0:停用, 1:啟用）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    -- 索引設計
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='播放清單資料表';
```

#### 欄位說明

| 欄位名稱 | 資料型別 | 必填 | 說明 | 範例 |
|---------|---------|------|------|------|
| id | INT UNSIGNED | ✓ | 自動遞增主鍵 | 1 |
| name | VARCHAR(255) | ✓ | 播放清單名稱 | 我的最愛 |
| description | TEXT | ✗ | 播放清單描述 | 收藏喜歡的音樂影片 |
| is_active | TINYINT(1) | ✓ | 是否啟用狀態 | 1 (啟用) / 0 (停用) |
| created_at | DATETIME | ✓ | 建立時間 | 2025-10-27 12:00:00 |
| updated_at | DATETIME | ✓ | 最後更新時間 | 2025-10-27 12:00:00 |

#### 索引策略

- **PRIMARY KEY (id)**: 主鍵索引
- **INDEX (name)**: 支援名稱搜尋與排序
- **INDEX (is_active)**: 快速篩選啟用/停用的播放清單
- **INDEX (created_at)**: 依建立時間排序

#### 業務規則

1. `name` 為必填欄位，不可為空字串
2. `is_active` 預設為 1（啟用狀態）
3. 刪除播放清單時，相關的 `playlist_items` 會自動級聯刪除
4. 播放清單可以是空的（沒有任何影片）

---

### 3. playlist_items（播放清單項目資料表）

**用途**: 建立播放清單與影片的多對多關聯，並記錄播放順序

```sql
CREATE TABLE playlist_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主鍵 ID',
    playlist_id INT UNSIGNED NOT NULL COMMENT '播放清單 ID（外鍵）',
    video_id INT UNSIGNED NOT NULL COMMENT '影片 ID（外鍵）',
    position INT UNSIGNED NOT NULL COMMENT '播放順序位置（從 1 開始）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    
    -- 外鍵約束
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    
    -- 索引設計
    INDEX idx_playlist_id (playlist_id),
    INDEX idx_video_id (video_id),
    INDEX idx_position (position),
    INDEX idx_playlist_position (playlist_id, position),
    
    -- 唯一約束：同一播放清單中，同一位置不能有兩個項目
    UNIQUE KEY unique_playlist_video_position (playlist_id, position)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='播放清單項目關聯表';
```

#### 欄位說明

| 欄位名稱 | 資料型別 | 必填 | 說明 | 範例 |
|---------|---------|------|------|------|
| id | INT UNSIGNED | ✓ | 自動遞增主鍵 | 1 |
| playlist_id | INT UNSIGNED | ✓ | 關聯的播放清單 ID | 1 |
| video_id | INT UNSIGNED | ✓ | 關聯的影片 ID | 5 |
| position | INT UNSIGNED | ✓ | 播放順序（1, 2, 3...） | 1 |
| created_at | DATETIME | ✓ | 加入時間 | 2025-10-27 12:00:00 |

#### 索引策略

- **PRIMARY KEY (id)**: 主鍵索引
- **INDEX (playlist_id)**: 快速查詢某播放清單的所有影片
- **INDEX (video_id)**: 快速查詢某影片在哪些播放清單中
- **INDEX (playlist_id, position)**: 組合索引，優化排序查詢
- **UNIQUE (playlist_id, position)**: 確保順序唯一性

#### 外鍵約束

- **ON DELETE CASCADE**: 當播放清單或影片被刪除時，自動刪除相關項目
  - 刪除播放清單 → 自動刪除該清單的所有項目
  - 刪除影片 → 自動從所有播放清單中移除該影片

#### 業務規則

1. 同一播放清單中的 `position` 必須唯一且連續（1, 2, 3...）
2. 同一影片可以在同一播放清單中出現多次（不同的 position）
3. 同一影片可以出現在多個不同的播放清單中
4. 新增項目時，如果未指定 position，自動設為最大值 + 1
5. 刪除項目後，需要重新調整後續項目的 position 保持連續

---

## CodeIgniter 4 模型實作

### VideoModel.php

```php
<?php
namespace App\Models;

use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table            = 'videos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'App\Entities\Video';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields = [
        'video_id', 'title', 'description', 'thumbnail_url',
        'duration', 'channel_name', 'channel_id', 'youtube_url'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'video_id'    => 'required|max_length[20]|is_unique[videos.video_id,id,{id}]',
        'title'       => 'required|max_length[255]',
        'youtube_url' => 'required|max_length[500]|valid_url'
    ];

    protected $validationMessages = [
        'video_id' => [
            'required'  => '影片 ID 為必填欄位',
            'is_unique' => '此影片已存在於資料庫中'
        ],
        'title' => [
            'required' => '影片標題為必填欄位'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // 檢查影片是否存在
    public function videoExists(string $videoId): bool
    {
        return $this->where('video_id', $videoId)->countAllResults() > 0;
    }

    // 搜尋影片
    public function search(string $keyword, int $limit = 20)
    {
        return $this->like('title', $keyword)
                    ->orLike('description', $keyword)
                    ->orLike('channel_name', $keyword)
                    ->paginate($limit);
    }
}
```

### PlaylistModel.php

```php
<?php
namespace App\Models;

use CodeIgniter\Model;

class PlaylistModel extends Model
{
    protected $table            = 'playlists';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'App\Entities\Playlist';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields = [
        'name', 'description', 'is_active'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|max_length[255]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => '播放清單名稱為必填欄位'
        ]
    ];

    // 取得播放清單及其影片數量
    public function getWithVideoCount(int $id)
    {
        return $this->select('playlists.*, COUNT(playlist_items.id) as video_count')
                    ->join('playlist_items', 'playlists.id = playlist_items.playlist_id', 'left')
                    ->where('playlists.id', $id)
                    ->groupBy('playlists.id')
                    ->first();
    }

    // 取得所有啟用的播放清單
    public function getActive()
    {
        return $this->where('is_active', 1)->findAll();
    }
}
```

### PlaylistItemModel.php

```php
<?php
namespace App\Models;

use CodeIgniter\Model;

class PlaylistItemModel extends Model
{
    protected $table            = 'playlist_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'App\Entities\PlaylistItem';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields = [
        'playlist_id', 'video_id', 'position'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules = [
        'playlist_id' => 'required|integer|is_not_unique[playlists.id]',
        'video_id'    => 'required|integer|is_not_unique[videos.id]',
        'position'    => 'required|integer|greater_than[0]'
    ];

    // 取得播放清單的所有影片（依順序）
    public function getPlaylistVideos(int $playlistId)
    {
        return $this->select('videos.*, playlist_items.id as item_id, playlist_items.position')
                    ->join('videos', 'playlist_items.video_id = videos.id')
                    ->where('playlist_items.playlist_id', $playlistId)
                    ->orderBy('playlist_items.position', 'ASC')
                    ->findAll();
    }

    // 取得播放清單的下一個位置
    public function getNextPosition(int $playlistId): int
    {
        $maxPosition = $this->selectMax('position')
                            ->where('playlist_id', $playlistId)
                            ->first();
        
        return ($maxPosition ? $maxPosition->position : 0) + 1;
    }

    // 重新排序項目
    public function reorderItems(int $playlistId, array $items): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($items as $item) {
            $this->update($item['id'], ['position' => $item['position']]);
        }

        $db->transComplete();
        return $db->transStatus();
    }

    // 刪除項目後重新調整順序
    public function deleteAndReorder(int $id): bool
    {
        $item = $this->find($id);
        if (!$item) return false;

        $db = \Config\Database::connect();
        $db->transStart();

        // 刪除項目
        $this->delete($id);

        // 調整後續項目的位置
        $this->where('playlist_id', $item->playlist_id)
             ->where('position >', $item->position)
             ->set('position', 'position - 1', false)
             ->update();

        $db->transComplete();
        return $db->transStatus();
    }
}
```

## 資料庫遷移檔案

### Migration: CreateVideosTable

```php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'video_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'thumbnail_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'duration' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'channel_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'channel_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'youtube_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('video_id');
        $this->forge->addKey('title');
        $this->forge->addKey('created_at');
        
        $this->forge->createTable('videos');
    }

    public function down()
    {
        $this->forge->dropTable('videos');
    }
}
```

## 查詢優化建議

### 常用查詢範例

#### 1. 取得播放清單及其所有影片（優化版）
```php
// 使用 JOIN 一次查詢完成，避免 N+1 問題
$sql = "
    SELECT 
        p.id, p.name, p.description,
        v.id as video_id, v.title, v.thumbnail_url, v.duration,
        pi.position
    FROM playlists p
    LEFT JOIN playlist_items pi ON p.id = pi.playlist_id
    LEFT JOIN videos v ON pi.video_id = v.id
    WHERE p.id = ?
    ORDER BY pi.position ASC
";
```

#### 2. 搜尋影片（全文檢索）
```php
// 使用 FULLTEXT 索引提升搜尋效能
$sql = "
    SELECT * FROM videos
    WHERE MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)
    LIMIT 20
";
```

#### 3. 取得最受歡迎的影片（被加入最多播放清單）
```php
$sql = "
    SELECT v.*, COUNT(pi.id) as playlist_count
    FROM videos v
    LEFT JOIN playlist_items pi ON v.id = pi.video_id
    GROUP BY v.id
    ORDER BY playlist_count DESC
    LIMIT 10
";
```

---

**文件版本**: 1.0  
**最後更新**: 2025-10-27  
**維護者**: 開發團隊
