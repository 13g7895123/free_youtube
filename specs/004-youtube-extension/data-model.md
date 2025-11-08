# Data Model: YouTube 瀏覽器擴充程式

**Date**: 2025-11-08
**Feature**: 004-youtube-extension

## 概述

本文件定義 YouTube 瀏覽器擴充程式的資料模型，包含本地儲存結構與後端 API 資料格式。

---

## 本地儲存模型（Browser Storage）

擴充程式使用 `browser.storage.local` 儲存以下資料：

### 1. 認證資訊 (AuthData)

```typescript
interface AuthData {
  accessToken: {
    value: string;           // 加密後的 access token
    expiresAt: number;        // Unix timestamp (毫秒)
  };
  refreshToken: {
    value: string;           // 加密後的 refresh token
    expiresAt: number;        // Unix timestamp (毫秒)
  };
  user: {
    lineUserId: string;      // LINE User ID
    displayName?: string;    // 使用者顯示名稱（選填）
    profilePictureUrl?: string; // 使用者頭像 URL（選填）
  };
}
```

**儲存鍵名**: `auth_data`

**生命週期**:
- Access token: 1 小時（3600000 毫秒）
- Refresh token: 7 天（604800000 毫秒）

**驗證規則**:
- `accessToken.value` 與 `refreshToken.value` 必須使用 AES-GCM 加密
- `expiresAt` 必須為有效的 Unix timestamp
- `lineUserId` 為必填，格式為 LINE 平台提供的唯一識別碼

---

### 2. 使用者設定 (UserSettings)

```typescript
interface UserSettings {
  playlistMode: 'default' | 'custom';  // 播放清單模式
  defaultPlaylistId?: string;          // 預設播放清單 ID（僅在 default 模式時有效）
  theme?: 'light' | 'dark' | 'auto';   // 主題設定（未來擴充）
}
```

**儲存鍵名**: `user_settings`

**預設值**:
```json
{
  "playlistMode": "default",
  "defaultPlaylistId": null,
  "theme": "auto"
}
```

**驗證規則**:
- `playlistMode` 必須為 `'default'` 或 `'custom'`
- 若 `playlistMode` 為 `'default'` 且使用者有播放清單，`defaultPlaylistId` 不可為 null
- `defaultPlaylistId` 必須對應後端存在的播放清單 ID

---

### 3. 快取資料 (CacheData)

```typescript
interface CacheData {
  playlists: Playlist[];               // 使用者的播放清單快取
  lastUpdated: number;                 // 最後更新時間 (Unix timestamp)
}

interface Playlist {
  id: string;                          // 播放清單 ID
  name: string;                        // 播放清單名稱
  videoCount: number;                  // 影片數量
  createdAt: string;                   // 建立時間 (ISO 8601)
  updatedAt: string;                   // 更新時間 (ISO 8601)
}
```

**儲存鍵名**: `cache_playlists`

**快取策略**:
- 快取有效期：5 分鐘（300000 毫秒）
- 超過有效期或使用者執行「重新整理」動作時，重新從後端 API 取得

**驗證規則**:
- `lastUpdated` 必須為有效的 Unix timestamp
- `playlists` 陣列可為空（新使用者）

---

## 後端 API 資料模型

### 1. 會員 (User)

```typescript
interface User {
  id: string;                          // 系統內部使用者 ID (UUID)
  lineUserId: string;                  // LINE User ID（唯一識別）
  displayName: string;                 // 使用者顯示名稱
  profilePictureUrl?: string;          // 使用者頭像 URL
  createdAt: string;                   // 註冊時間 (ISO 8601)
  updatedAt: string;                   // 最後更新時間 (ISO 8601)
}
```

**主鍵**: `id`
**唯一約束**: `lineUserId`

**關聯**:
- 一個 User 可擁有多個 Playlist（一對多）
- 一個 User 可擁有多個 Video in Library（一對多）

---

### 2. 播放庫影片 (LibraryVideo)

```typescript
interface LibraryVideo {
  id: string;                          // 系統內部 ID (UUID)
  userId: string;                      // 所屬使用者 ID
  youtubeVideoId: string;              // YouTube 影片 ID
  title: string;                       // 影片標題
  thumbnailUrl: string;                // 縮圖 URL（medium 尺寸）
  duration: string;                    // 影片時長（ISO 8601 duration 格式，如 "PT3M33S"）
  addedAt: string;                     // 加入時間 (ISO 8601)
}
```

**主鍵**: `id`
**唯一約束**: `(userId, youtubeVideoId)`（同一使用者不可重複加入相同影片）

**索引**:
- `userId` (查詢使用者的所有播放庫影片)
- `youtubeVideoId` (檢查影片是否已存在)

**驗證規則**:
- `youtubeVideoId` 必須符合 YouTube 影片 ID 格式（11 個字元）
- `duration` 必須符合 ISO 8601 duration 格式
- `title` 長度限制：1-200 字元
- `thumbnailUrl` 必須為有效的 HTTPS URL

---

### 3. 播放清單 (Playlist)

```typescript
interface Playlist {
  id: string;                          // 系統內部 ID (UUID)
  userId: string;                      // 所屬使用者 ID
  name: string;                        // 播放清單名稱
  description?: string;                // 播放清單描述（選填）
  videoCount: number;                  // 影片數量（denormalized）
  createdAt: string;                   // 建立時間 (ISO 8601)
  updatedAt: string;                   // 最後更新時間 (ISO 8601)
}
```

**主鍵**: `id`

**索引**:
- `userId` (查詢使用者的所有播放清單)

**驗證規則**:
- `name` 長度限制：1-100 字元
- `description` 長度限制：0-500 字元
- `videoCount` 自動更新（透過資料庫 trigger 或應用層邏輯）

**關聯**:
- 一個 Playlist 可包含多個 PlaylistVideo（一對多）

---

### 4. 播放清單影片 (PlaylistVideo)

```typescript
interface PlaylistVideo {
  id: string;                          // 系統內部 ID (UUID)
  playlistId: string;                  // 所屬播放清單 ID
  youtubeVideoId: string;              // YouTube 影片 ID
  title: string;                       // 影片標題
  thumbnailUrl: string;                // 縮圖 URL
  duration: string;                    // 影片時長 (ISO 8601 duration)
  order: number;                       // 播放順序（從 0 開始）
  addedAt: string;                     // 加入時間 (ISO 8601)
}
```

**主鍵**: `id`
**唯一約束**: `(playlistId, youtubeVideoId)`（同一播放清單不可重複加入相同影片）

**索引**:
- `playlistId` (查詢播放清單的所有影片)
- `(playlistId, order)` (按順序排列影片)

**驗證規則**:
- 同 `LibraryVideo` 的 `youtubeVideoId`, `title`, `thumbnailUrl`, `duration` 規則
- `order` 必須為非負整數，且在同一播放清單內唯一

---

## 資料流程圖

### 加入播放庫流程

```
擴充程式                     YouTube API              後端 API
    |                           |                        |
    | 1. 解析 YouTube URL       |                        |
    |-------------------------->|                        |
    |                           |                        |
    | 2. 取得影片資訊           |                        |
    |    (title, thumbnail,     |                        |
    |     duration)             |                        |
    |<--------------------------|                        |
    |                           |                        |
    | 3. 呼叫 POST /api/library |                        |
    |-------------------------------------------------->|
    |                           |                        |
    |                           |    4. 檢查影片是否已存在|
    |                           |                        |
    |                           |    5. 若不存在，新增至資料庫|
    |                           |                        |
    | 6. 回傳成功/失敗          |                        |
    |<--------------------------------------------------|
    |                           |                        |
    | 7. 顯示成功訊息           |                        |
```

### Token 更新流程

```
擴充程式                     後端 API
    |                           |
    | 1. 檢查 access token 過期  |
    |                           |
    | 2. 若過期，使用 refresh    |
    |    token 呼叫 POST         |
    |    /api/auth/refresh       |
    |-------------------------->|
    |                           |
    |                           | 3. 驗證 refresh token
    |                           |
    |                           | 4. 產生新 access token
    |                           |
    | 5. 回傳新 token            |
    |<--------------------------|
    |                           |
    | 6. 更新 browser.storage    |
    |    中的 token              |
```

---

## 資料驗證與約束

### 通用規則

1. **時間格式**: 所有時間欄位使用 ISO 8601 格式（例如 `2025-11-08T12:00:00Z`）
2. **ID 格式**: 系統內部 ID 使用 UUID v4
3. **字串長度**: 所有字串欄位必須有最大長度限制，避免資料庫溢位
4. **URL 驗證**: 所有 URL 欄位必須為有效的 HTTPS URL

### 業務規則

1. **唯一性約束**:
   - 同一使用者不可在播放庫中重複加入相同影片
   - 同一播放清單不可重複加入相同影片

2. **級聯刪除**:
   - 刪除使用者 → 刪除該使用者的所有播放庫影片與播放清單
   - 刪除播放清單 → 刪除該播放清單的所有影片

3. **自動更新**:
   - `Playlist.videoCount` 在新增/刪除影片時自動更新
   - `Playlist.updatedAt` 在任何變更時自動更新

---

## 資料庫 Schema（參考）

> 此為後端資料庫設計參考，擴充程式開發不直接涉及

### MySQL Schema

```sql
CREATE TABLE users (
  id VARCHAR(36) PRIMARY KEY,
  line_user_id VARCHAR(255) UNIQUE NOT NULL,
  display_name VARCHAR(255) NOT NULL,
  profile_picture_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_line_user_id (line_user_id)
);

CREATE TABLE library_videos (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  youtube_video_id VARCHAR(11) NOT NULL,
  title VARCHAR(200) NOT NULL,
  thumbnail_url VARCHAR(500) NOT NULL,
  duration VARCHAR(20) NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_video (user_id, youtube_video_id),
  INDEX idx_user_id (user_id),
  INDEX idx_youtube_video_id (youtube_video_id)
);

CREATE TABLE playlists (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(500),
  video_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
);

CREATE TABLE playlist_videos (
  id VARCHAR(36) PRIMARY KEY,
  playlist_id VARCHAR(36) NOT NULL,
  youtube_video_id VARCHAR(11) NOT NULL,
  title VARCHAR(200) NOT NULL,
  thumbnail_url VARCHAR(500) NOT NULL,
  duration VARCHAR(20) NOT NULL,
  `order` INT NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
  UNIQUE KEY unique_playlist_video (playlist_id, youtube_video_id),
  INDEX idx_playlist_id (playlist_id),
  INDEX idx_playlist_order (playlist_id, `order`)
);
```

---

## 下一步

資料模型已定義完成，接下來將設計 API 合約（contracts/）與快速入門指南（quickstart.md）。
