# 使用者影片庫與播放清單權限實作總結

## 概述
本次實作確保所有影片和播放清單功能都正確關聯到使用者帳號，只有登入的使用者可以存取自己的資料。

## 修改內容

### 1. 資料庫結構修改

#### ✅ 已完成 - 原本就有 user_id
- `video_library` 表 - 已有 `user_id` 和外鍵約束
- `playlists` 表 - 已有 `user_id` 和外鍵約束

#### ⚠️ 需要執行 - videos 表新增 user_id
**檔案**: `backend/database/migrations/add_user_id_to_videos.sql`

執行以下 SQL 來新增 `user_id` 欄位：

```sql
-- 新增 user_id 欄位
ALTER TABLE `videos` 
ADD COLUMN `user_id` INT(11) UNSIGNED NULL AFTER `id`;

-- 新增索引
ALTER TABLE `videos` 
ADD KEY `idx_user_id` (`user_id`);

-- 新增外鍵約束
ALTER TABLE `videos` 
ADD CONSTRAINT `fk_videos_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;
```

**注意**: 如果資料庫中已有影片資料，需要先處理：
- 選項 1: 刪除現有影片 `DELETE FROM videos WHERE user_id IS NULL;`
- 選項 2: 分配給預設使用者 `UPDATE videos SET user_id = 1 WHERE user_id IS NULL;`

### 2. Model 層修改

#### VideoModel.php
- ✅ 新增 `user_id` 到 `allowedFields`
- ✅ 新增 `getUserVideos($userId)` 方法
- ✅ 所有查詢方法都支援可選的 `$userId` 參數過濾

#### PlaylistModel.php
- ✅ 已有 `getUserPlaylists($userId)` 方法
- ✅ 已有 `user_id` 在 `allowedFields`

#### VideoLibraryModel.php
- ✅ 已有 `getUserLibrary($userId)` 方法
- ✅ 已有 `user_id` 驗證和約束

### 3. Controller 層修改

#### VideoController.php (Api)
所有方法都已加入：
- ✅ 使用者認證檢查 (`$userId = $this->request->userId`)
- ✅ 自動設定 `user_id` (create 方法)
- ✅ 查詢過濾只顯示該使用者的影片
- ✅ 更新/刪除前驗證所有權

修改的方法：
- `index()` - 只顯示使用者的影片
- `search()` - 只搜尋使用者的影片
- `show()` - 驗證所有權
- `create()` - 自動設定 user_id
- `update()` - 驗證所有權，防止修改 user_id
- `delete()` - 驗證所有權
- `check()` - 只檢查使用者的影片庫

#### PlaylistController.php (Api)
所有方法都已加入：
- ✅ 使用者認證檢查
- ✅ 自動設定 `user_id` (create 方法)
- ✅ 查詢過濾只顯示該使用者的播放清單
- ✅ 更新/刪除前驗證所有權

修改的方法：
- `index()` - 只顯示使用者的播放清單
- `show()` - 驗證所有權
- `create()` - 自動設定 user_id
- `update()` - 驗證所有權，防止修改 user_id
- `delete()` - 驗證所有權

#### PlaylistItemController.php
所有方法都已加入：
- ✅ 使用者認證檢查
- ✅ 播放清單所有權驗證

修改的方法：
- `getItems()` - 驗證播放清單所有權
- `addItem()` - 驗證播放清單所有權
- `reorder()` - 驗證播放清單所有權
- `removeItem()` - 驗證播放清單所有權
- `updatePosition()` - 驗證播放清單所有權

#### Playlists.php (已存在的控制器)
- ✅ 已正確實作使用者權限檢查
- ✅ 所有操作都驗證 user_id

#### VideoLibrary.php (已存在的控制器)
- ✅ 已正確實作使用者權限檢查
- ✅ 所有操作都驗證 user_id

### 4. 路由層修改

#### Routes.php
- ✅ `videos` 路由群組已加入 `['filter' => 'auth']`
- ✅ `playlists` 路由群組已有 `['filter' => 'auth']`
- ✅ `video-library` 路由群組已有 `['filter' => 'auth']`

### 5. 認證過濾器

#### AuthFilter.php
- ✅ 已正確實作 JWT 驗證
- ✅ 將 `userId` 注入到 `$request->userId`
- ✅ 支援 Mock 模式用於開發

## 安全性檢查清單

✅ **資料庫層**
- 所有關鍵表格都有 `user_id` 外鍵約束
- CASCADE DELETE 確保使用者刪除時資料一併清理

✅ **Model 層**
- 所有查詢方法都支援使用者過濾
- `allowedFields` 包含 `user_id`

✅ **Controller 層**
- 所有操作都驗證使用者登入狀態
- 建立資料時自動設定 `user_id`
- 更新/刪除前驗證資料所有權
- 防止使用者修改 `user_id` 欄位

✅ **路由層**
- 所有需要權限的路由都套用 `auth` filter
- 認證過濾器正確注入 `userId`

## 部署步驟

1. **執行資料庫遷移**
   ```bash
   mysql -u your_user -p your_database < backend/database/migrations/add_user_id_to_videos.sql
   ```

2. **處理現有影片資料**（如果有）
   - 決定要刪除或分配給預設使用者
   
3. **測試認證流程**
   - 測試登入功能
   - 確認 JWT token 正確設定
   
4. **測試 CRUD 操作**
   - 影片的建立、讀取、更新、刪除
   - 播放清單的建立、讀取、更新、刪除
   - 播放清單項目的新增、移除、排序
   
5. **測試權限隔離**
   - 確認使用者 A 無法存取使用者 B 的資料
   - 確認未登入使用者無法存取需要權限的 API

## 已修正的錯誤

### 原始問題
```
Cannot use object of type App\Entities\PlaylistItem as array
```

### 修正位置
- `PlaylistItemController.php` 第 188 行
- 將 `$item['playlist_id']` 改為 `$item->playlist_id`
- 原因：Model 返回 Entity 物件，應使用物件語法而非陣列語法

## API 使用範例

### 建立影片
```bash
POST /api/videos
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "video_id": "dQw4w9WgXcQ",
  "title": "Never Gonna Give You Up",
  "youtube_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}
```
- `user_id` 會自動從 JWT token 中取得並設定

### 取得我的影片
```bash
GET /api/videos
Authorization: Bearer {jwt_token}
```
- 只會返回當前使用者的影片

### 建立播放清單
```bash
POST /api/playlists
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "name": "我的最愛",
  "description": "最喜歡的音樂"
}
```
- `user_id` 會自動設定

## 測試建議

1. **單元測試**
   - 測試 Model 的使用者過濾方法
   - 測試 Controller 的認證檢查

2. **整合測試**
   - 測試完整的 API 流程
   - 測試跨使用者的權限隔離

3. **手動測試**
   - 使用不同使用者帳號測試
   - 確認資料隔離正確

## 注意事項

- 所有新建立的影片和播放清單都必須登入後才能操作
- 使用者只能看到和操作自己的資料
- 刪除使用者時，相關的影片和播放清單會自動刪除（CASCADE）
- AuthFilter 會自動將 `userId` 注入到 request 中
- 所有 Controller 都應該檢查 `$this->request->userId` 是否存在

## 相關檔案清單

### 修改的檔案
- `backend/app/Models/VideoModel.php`
- `backend/app/Controllers/Api/VideoController.php`
- `backend/app/Controllers/Api/PlaylistController.php`
- `backend/app/Controllers/Api/PlaylistItemController.php`
- `backend/app/Config/Routes.php`

### 新增的檔案
- `backend/database/migrations/add_user_id_to_videos.sql`

### 已存在且正確的檔案
- `backend/app/Models/PlaylistModel.php`
- `backend/app/Models/VideoLibraryModel.php`
- `backend/app/Controllers/Playlists.php`
- `backend/app/Controllers/VideoLibrary.php`
- `backend/app/Filters/AuthFilter.php`
