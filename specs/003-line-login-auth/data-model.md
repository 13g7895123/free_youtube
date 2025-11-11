# Data Model: LINE Login 會員認證系統

**Feature**: 003-line-login-auth
**Date**: 2025-11-01
**Spec**: [spec.md](./spec.md)
**Research**: [research.md](./research.md)

## 概述

本文件定義 LINE Login 會員認證系統的資料模型,包含資料庫表結構、實體關係、驗證規則和狀態轉換。所有設計遵循專案憲章的「最小化變更」原則,僅新增必要的表和欄位。

## 實體關係圖 (ERD)

```text
┌─────────────────┐       1:N      ┌───────────────────┐
│     users       │─────────────────│   user_tokens     │
│                 │                 │                   │
│ * line_user_id  │                 │ * user_id (FK)    │
│ * display_name  │                 │ * access_token    │
│ * avatar_url    │                 │ * refresh_token   │
│ * status        │                 │ * expires_at      │
│ * deleted_at    │                 │ * created_at      │
└────────┬────────┘                 └───────────────────┘
         │
         │ 1:N
         │
         ├─────────────────┐
         │                 │
         ▼                 ▼
┌──────────────┐    ┌──────────────────┐
│video_library │    │   playlists      │
│              │    │                  │
│* user_id (FK)│    │ * user_id (FK)   │
│* video_id    │    │ * name           │
│* title       │    │ * description    │
│* thumbnail   │    │ * created_at     │
│* created_at  │    └────────┬─────────┘
└──────────────┘             │
                             │ 1:N
                             ▼
                    ┌──────────────────┐
                    │ playlist_items   │
                    │                  │
                    │* playlist_id (FK)│
                    │* video_id        │
                    │* position        │
                    │* created_at      │
                    └──────────────────┘

┌──────────────────┐
│ guest_sessions   │  (暫存資料,登入後遷移)
│                  │
│ * session_id     │
│ * history_data   │  (JSON: [{videoId, title, playedAt}])
│ * created_at     │
│ * expires_at     │
└──────────────────┘
```

## 資料表定義

### 1. users (會員表)

**用途**: 儲存透過 LINE Login 認證的會員基本資訊

**表結構**:
| 欄位名稱 | 資料型別 | 約束 | 說明 |
|---------|---------|------|------|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 會員 ID |
| line_user_id | VARCHAR(255) | UNIQUE, NOT NULL | LINE 使用者 ID (唯一識別) |
| display_name | VARCHAR(255) | NOT NULL | LINE 顯示名稱 |
| avatar_url | TEXT | NULL | LINE 頭像 URL |
| email | VARCHAR(255) | NULL | LINE 綁定的 Email (選用) |
| status | ENUM('active', 'soft_deleted') | NOT NULL, DEFAULT 'active' | 會員狀態 |
| created_at | DATETIME | NOT NULL | 建立時間 |
| updated_at | DATETIME | NULL | 更新時間 |
| deleted_at | DATETIME | NULL | 軟刪除時間 (NULL 表示未刪除) |

**索引**:
- PRIMARY KEY: `id`
- UNIQUE: `line_user_id`
- INDEX: `status`, `deleted_at`

**驗證規則**:
- `line_user_id`: 必填,長度 1-255,唯一
- `display_name`: 必填,長度 1-255
- `avatar_url`: 選填,必須為有效 URL
- `status`: 必填,只能是 'active' 或 'soft_deleted'

**狀態轉換**:
```text
[新會員註冊]
    ↓
  active ←─────┐ (重新授權恢復)
    │          │
    │ (取消授權/刪除 LINE 帳號)
    ↓          │
soft_deleted ──┘
    │
    │ (30 天後)
    ↓
[永久刪除]
```

**CodeIgniter Model 範例**:
```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'line_user_id',
        'display_name',
        'avatar_url',
        'email',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'line_user_id' => 'required|max_length[255]|is_unique[users.line_user_id,id,{id}]',
        'display_name' => 'required|max_length[255]',
        'avatar_url' => 'permit_empty|valid_url',
        'status' => 'required|in_list[active,soft_deleted]'
    ];

    protected $validationMessages = [
        'line_user_id' => [
            'required' => 'LINE User ID 為必填',
            'is_unique' => 'LINE User ID 已存在'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 根據 LINE User ID 查找會員
     */
    public function findByLineUserId(string $lineUserId)
    {
        return $this->where('line_user_id', $lineUserId)->first();
    }

    /**
     * 恢復軟刪除的會員
     */
    public function restoreUser(int $userId)
    {
        return $this->update($userId, [
            'deleted_at' => null,
            'status' => 'active'
        ]);
    }
}
```

---

### 2. user_tokens (認證 Token 表)

**用途**: 儲存會員的 access token 和 refresh token,支援多裝置登入

**表結構**:
| 欄位名稱 | 資料型別 | 約束 | 說明 |
|---------|---------|------|------|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Token ID |
| user_id | INT UNSIGNED | NOT NULL, FOREIGN KEY | 會員 ID (關聯 users.id) |
| access_token | VARCHAR(512) | NOT NULL | LINE Access Token |
| refresh_token | VARCHAR(512) | NULL | LINE Refresh Token |
| token_type | VARCHAR(50) | NOT NULL, DEFAULT 'Bearer' | Token 類型 |
| expires_at | DATETIME | NOT NULL | Access Token 過期時間 |
| device_id | VARCHAR(255) | NULL | 裝置識別碼 (選用,用於多裝置管理) |
| ip_address | VARCHAR(45) | NULL | 登入 IP |
| user_agent | TEXT | NULL | 瀏覽器 User Agent |
| created_at | DATETIME | NOT NULL | 建立時間 |
| updated_at | DATETIME | NULL | 更新時間 |

**索引**:
- PRIMARY KEY: `id`
- INDEX: `user_id`, `expires_at`
- INDEX: `access_token` (前 255 字元)

**外鍵約束**:
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**驗證規則**:
- `user_id`: 必填,必須存在於 users 表
- `access_token`: 必填,長度 1-512
- `expires_at`: 必填,必須為未來時間

**CodeIgniter Model 範例**:
```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTokenModel extends Model
{
    protected $table = 'user_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
        'device_id',
        'ip_address',
        'user_agent'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_not_unique[users.id]',
        'access_token' => 'required|max_length[512]',
        'expires_at' => 'required|valid_date'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 根據 Access Token 查找記錄
     */
    public function findByAccessToken(string $accessToken)
    {
        return $this->where('access_token', $accessToken)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * 清理過期 Token
     */
    public function cleanupExpired()
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * 撤銷使用者所有 Token (登出所有裝置)
     */
    public function revokeAllUserTokens(int $userId)
    {
        return $this->where('user_id', $userId)->delete();
    }
}
```

---

### 3. video_library (影片庫表)

**用途**: 儲存會員加入的 YouTube 影片收藏

**表結構**:
| 欄位名稱 | 資料型別 | 約束 | 說明 |
|---------|---------|------|------|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 記錄 ID |
| user_id | INT UNSIGNED | NOT NULL, FOREIGN KEY | 會員 ID (關聯 users.id) |
| video_id | VARCHAR(20) | NOT NULL | YouTube 影片 ID |
| title | VARCHAR(255) | NOT NULL | 影片標題 |
| thumbnail_url | TEXT | NULL | 影片縮圖 URL |
| duration | INT UNSIGNED | NULL | 影片長度 (秒) |
| channel_name | VARCHAR(255) | NULL | 頻道名稱 |
| created_at | DATETIME | NOT NULL | 加入時間 |

**索引**:
- PRIMARY KEY: `id`
- UNIQUE: `user_id`, `video_id` (複合唯一索引,同一會員不重複收藏)
- INDEX: `user_id`

**外鍵約束**:
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**驗證規則**:
- `user_id`: 必填,必須存在於 users 表
- `video_id`: 必填,長度 11 (YouTube ID 固定長度)
- `title`: 必填,長度 1-255

**CodeIgniter Model 範例**:
```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class VideoLibraryModel extends Model
{
    protected $table = 'video_library';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'video_id',
        'title',
        'thumbnail_url',
        'duration',
        'channel_name'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_not_unique[users.id]',
        'video_id' => 'required|max_length[20]',
        'title' => 'required|max_length[255]'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 取得會員的影片庫
     */
    public function getUserLibrary(int $userId, int $limit = 100, int $offset = 0)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit, $offset);
    }

    /**
     * 檢查影片是否已在影片庫
     */
    public function isVideoInLibrary(int $userId, string $videoId): bool
    {
        return $this->where(['user_id' => $userId, 'video_id' => $videoId])->countAllResults() > 0;
    }
}
```

---

### 4. playlists (播放清單表)

**用途**: 儲存會員建立的播放清單

**表結構**:
| 欄位名稱 | 資料型別 | 約束 | 說明 |
|---------|---------|------|------|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 播放清單 ID |
| user_id | INT UNSIGNED | NOT NULL, FOREIGN KEY | 會員 ID (關聯 users.id) |
| name | VARCHAR(255) | NOT NULL | 播放清單名稱 |
| description | TEXT | NULL | 描述 |
| is_public | TINYINT(1) | NOT NULL, DEFAULT 0 | 是否公開 (0=私有, 1=公開) |
| created_at | DATETIME | NOT NULL | 建立時間 |
| updated_at | DATETIME | NULL | 更新時間 |

**索引**:
- PRIMARY KEY: `id`
- INDEX: `user_id`

**外鍵約束**:
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

**驗證規則**:
- `user_id`: 必填,必須存在於 users 表
- `name`: 必填,長度 1-255
- `is_public`: 必填,0 或 1

**CodeIgniter Model 範例**:
```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class PlaylistModel extends Model
{
    protected $table = 'playlists';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'name',
        'description',
        'is_public'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_not_unique[users.id]',
        'name' => 'required|max_length[255]',
        'is_public' => 'required|in_list[0,1]'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 取得會員的播放清單
     */
    public function getUserPlaylists(int $userId)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
```

---

### 5. playlist_items (播放清單項目表)

**用途**: 儲存播放清單中的影片項目及順序

**表結構**:
| 欄位名稱 | 資料型別 | 約束 | 說明 |
|---------|---------|------|------|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 項目 ID |
| playlist_id | INT UNSIGNED | NOT NULL, FOREIGN KEY | 播放清單 ID (關聯 playlists.id) |
| video_id | VARCHAR(20) | NOT NULL | YouTube 影片 ID |
| title | VARCHAR(255) | NOT NULL | 影片標題 |
| thumbnail_url | TEXT | NULL | 影片縮圖 URL |
| position | INT UNSIGNED | NOT NULL | 排序位置 (0-based) |
| created_at | DATETIME | NOT NULL | 加入時間 |

**索引**:
- PRIMARY KEY: `id`
- UNIQUE: `playlist_id`, `video_id` (複合唯一索引,同一清單不重複影片)
- INDEX: `playlist_id`, `position`

**外鍵約束**:
- `playlist_id` REFERENCES `playlists(id)` ON DELETE CASCADE

**驗證規則**:
- `playlist_id`: 必填,必須存在於 playlists 表
- `video_id`: 必填,長度 11
- `title`: 必填,長度 1-255
- `position`: 必填,>= 0

**CodeIgniter Model 範例**:
```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class PlaylistItemModel extends Model
{
    protected $table = 'playlist_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'playlist_id',
        'video_id',
        'title',
        'thumbnail_url',
        'position'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    // Validation
    protected $validationRules = [
        'playlist_id' => 'required|is_not_unique[playlists.id]',
        'video_id' => 'required|max_length[20]',
        'title' => 'required|max_length[255]',
        'position' => 'required|integer'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 取得播放清單的所有影片
     */
    public function getPlaylistItems(int $playlistId)
    {
        return $this->where('playlist_id', $playlistId)
                    ->orderBy('position', 'ASC')
                    ->findAll();
    }

    /**
     * 重新排序項目位置
     */
    public function reorderItems(int $playlistId, array $videoIds)
    {
        $this->db->transStart();

        foreach ($videoIds as $position => $videoId) {
            $this->where([
                'playlist_id' => $playlistId,
                'video_id' => $videoId
            ])->set('position', $position)->update();
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
```

---

### 6. guest_sessions (訪客暫存資料表)

**用途**: 儲存訪客的播放歷史,供登入後遷移使用

**表結構**:
| 欄位名稱 | 資料型別 | 約束 | 說明 |
|---------|---------|------|------|
| id | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Session ID |
| session_id | VARCHAR(128) | UNIQUE, NOT NULL | 瀏覽器 Session ID (Cookie) |
| history_data | JSON | NULL | 播放歷史 JSON 資料 |
| created_at | DATETIME | NOT NULL | 建立時間 |
| expires_at | DATETIME | NOT NULL | 過期時間 (預設 7 天) |

**索引**:
- PRIMARY KEY: `id`
- UNIQUE: `session_id`
- INDEX: `expires_at`

**JSON 資料結構** (history_data):
```json
[
  {
    "videoId": "dQw4w9WgXcQ",
    "title": "Rick Astley - Never Gonna Give You Up",
    "thumbnail": "https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg",
    "playedAt": "2025-11-01T10:30:00Z"
  },
  {
    "videoId": "9bZkp7q19f0",
    "title": "PSY - GANGNAM STYLE",
    "thumbnail": "https://i.ytimg.com/vi/9bZkp7q19f0/mqdefault.jpg",
    "playedAt": "2025-11-01T11:00:00Z"
  }
]
```

**驗證規則**:
- `session_id`: 必填,長度 1-128,唯一
- `expires_at`: 必填,必須為未來時間

**CodeIgniter Model 範例**:
```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class GuestSessionModel extends Model
{
    protected $table = 'guest_sessions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'session_id',
        'history_data',
        'expires_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    // Validation
    protected $validationRules = [
        'session_id' => 'required|max_length[128]|is_unique[guest_sessions.session_id,id,{id}]',
        'expires_at' => 'required|valid_date'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 根據 Session ID 查找
     */
    public function findBySessionId(string $sessionId)
    {
        return $this->where('session_id', $sessionId)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * 清理過期 Session
     */
    public function cleanupExpired()
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * 儲存訪客歷史記錄
     */
    public function saveHistory(string $sessionId, array $historyData)
    {
        $existing = $this->findBySessionId($sessionId);

        $data = [
            'session_id' => $sessionId,
            'history_data' => json_encode($historyData),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data);
        }
    }
}
```

---

## 資料庫遷移腳本 (CodeIgniter Migration)

### Migration: 2025110100_create_line_login_tables.php

```php
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

        // 4. playlists 表
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_public' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('playlists');

        // 5. playlist_items 表
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'playlist_id' => [
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
            'position' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['playlist_id', 'video_id']);
        $this->forge->addKey(['playlist_id', 'position']);
        $this->forge->addForeignKey('playlist_id', 'playlists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('playlist_items');

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
        $this->forge->dropTable('playlist_items', true);
        $this->forge->dropTable('playlists', true);
        $this->forge->dropTable('video_library', true);
        $this->forge->dropTable('user_tokens', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('guest_sessions', true);
    }
}
```

---

## 資料完整性與安全性

### 外鍵約束

所有外鍵使用 `ON DELETE CASCADE`,確保:
- 刪除會員時,自動刪除其 tokens、影片庫、播放清單
- 刪除播放清單時,自動刪除其項目

### 資料隔離

- 所有查詢必須包含 `user_id` 條件,確保會員只能存取自己的資料
- API 層級驗證:檢查請求的 user_id 是否與 token 中的 user_id 一致

### 軟刪除保護

- 查詢時自動過濾 `deleted_at IS NOT NULL` 的記錄
- 使用 CodeIgniter Model 的 `useSoftDeletes = true` 功能

### 索引優化

- 所有外鍵欄位都建立索引
- 頻繁查詢的欄位 (line_user_id, expires_at) 建立索引
- 複合唯一索引避免重複資料

---

## 總結

本資料模型設計遵循以下原則:

✅ **正規化**: 符合第三正規化 (3NF),避免資料冗餘
✅ **可擴展性**: 支援未來新增功能 (如播放清單分享、協作)
✅ **效能優化**: 索引設計支援高效查詢
✅ **安全性**: 外鍵約束、軟刪除、資料隔離
✅ **最小化變更**: 僅新增必要表,不修改現有結構

下一步將根據這些資料模型設計 API 合約 (contracts/)。
