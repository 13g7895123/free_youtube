# 專案分析報告：播放清單與資料庫整合功能

**功能分支**: `002-playlist-database`  
**分析日期**: 2025-10-27  
**分析者**: AI Assistant  
**文件版本**: 2.0

---

## 執行摘要

本報告針對「播放清單與資料庫整合」功能進行全面分析，該功能旨在將現有的純前端 YouTube 播放器升級為具備完整資料持久化與播放清單管理能力的全端應用程式。本分析涵蓋技術架構、實作計畫、風險評估及建議。

### 關鍵發現

✅ **優勢**:
- 清晰的使用者故事與驗收標準（6 個使用者故事，共 23 個驗收情境）
- 完整的技術規劃（CI4 + MariaDB + Vue.js 3）
- 詳細的任務分解（124 個具體可執行任務）
- 良好的專案結構設計（前後端分離）

⚠️ **挑戰**:
- 從純前端轉向全端開發，技術複雜度提升
- 需要整合 YouTube Data API（配額限制）
- 資料遷移與備份機制尚未規劃
- 跨瀏覽器本地儲存限制需考量

---

## 1. 專案概覽

### 1.1 目標與範圍

**主要目標**: 
將純前端的 YouTube 循環播放器升級為具備資料庫支援的完整播放清單管理系統。

**核心功能**:
1. 影片儲存與管理（CRUD）
2. 播放清單建立與組織
3. 自動順序播放
4. 影片順序調整（拖曳排序）
5. 搜尋與篩選
6. 影片資訊顯示（縮圖、標題、時長）

**範圍內**:
- ✅ 後端 API（CodeIgniter 4）
- ✅ 資料庫設計（MariaDB）
- ✅ 前端 UI 重構（Vue.js 3）
- ✅ Docker 容器化部署
- ✅ 資料庫管理工具（phpMyAdmin）

**明確排除**:
- ❌ 使用者認證系統
- ❌ 多使用者支援
- ❌ 雲端同步
- ❌ 社交功能
- ❌ 影片下載

### 1.2 利益相關者

| 角色 | 需求 | 關注點 |
|------|------|--------|
| 終端使用者 | 方便的影片管理與播放 | 易用性、效能、資料安全 |
| 開發團隊 | 清晰的架構與文件 | 可維護性、擴充性 |
| 系統管理員 | 穩定的部署環境 | 監控、備份、容錯 |

---

## 2. 技術架構分析

### 2.1 技術棧評估

#### 後端：CodeIgniter 4

**選擇理由**:
- ✅ 輕量級 PHP 框架，學習曲線平緩
- ✅ 內建 ORM（Query Builder）易於資料庫操作
- ✅ 良好的 RESTful API 支援
- ✅ 成熟的社群與文件

**潛在問題**:
- ⚠️ 相較於 Laravel，生態系統較小
- ⚠️ 需要手動處理一些高級功能（如作業佇列）

**建議**: 
- 適合中小型專案，符合本專案需求
- 建議使用 CI4 的 Entity 和 Model 提升代碼品質

#### 資料庫：MariaDB 10.6+

**選擇理由**:
- ✅ MySQL 的高效能分支
- ✅ 開源且免費
- ✅ 與 MySQL 高度相容
- ✅ 良好的 Docker 支援

**資料庫設計評估**:

**優勢**:
- 正規化設計（3NF）
- 適當的索引策略
- 外鍵約束確保資料完整性
- 支援 FULLTEXT 全文檢索

**建議優化**:
```sql
-- 建議在 videos 表新增軟刪除
ALTER TABLE videos ADD COLUMN deleted_at DATETIME NULL;

-- 建議新增播放次數統計
ALTER TABLE videos ADD COLUMN play_count INT UNSIGNED DEFAULT 0;

-- 建議新增最後播放時間
ALTER TABLE videos ADD COLUMN last_played_at DATETIME NULL;
```

#### 前端：Vue.js 3 + Vite

**選擇理由**:
- ✅ Composition API 提供更好的邏輯復用
- ✅ Vite 極快的開發體驗
- ✅ TypeScript 支援（未來可選）
- ✅ 豐富的生態系統

**架構建議**:
- 使用 Pinia 進行狀態管理（已規劃）
- 使用 Composables 封裝可復用邏輯（已規劃）
- 考慮使用 Vue Router 的路由守衛
- 建議整合 Tailwind CSS 或 Element Plus

### 2.2 系統架構圖

```
┌─────────────────────────────────────────────────────────────┐
│                         使用者                                │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  │ HTTP/HTTPS
                  │
┌─────────────────▼───────────────────────────────────────────┐
│                  Vue.js 3 前端 (Port 5173)                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Components: VideoCard, PlaylistCard, Player...      │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Stores (Pinia): videoStore, playlistStore           │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Services: videoService, playlistService             │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Router: Vue Router                                  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  │ REST API (JSON)
                  │
┌─────────────────▼───────────────────────────────────────────┐
│              CodeIgniter 4 後端 (Port 8080)                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Controllers: VideoController, PlaylistController    │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Models: VideoModel, PlaylistModel, PlaylistItem     │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Entities: Video, Playlist, PlaylistItem             │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Filters: CORS, Auth (optional), Error               │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Validation: VideoRules, PlaylistRules               │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  │ MySQLi
                  │
┌─────────────────▼───────────────────────────────────────────┐
│              MariaDB 10.6 (Port 3306)                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Tables: videos, playlists, playlist_items           │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Indexes: Primary Keys, Foreign Keys, FULLTEXT       │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│           phpMyAdmin (Port 8081) - 管理介面                   │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│           YouTube IFrame API - 外部服務                        │
└──────────────────────────────────────────────────────────────┘
```

### 2.3 資料流分析

#### 2.3.1 儲存影片流程

```
使用者點擊「儲存影片」
    ↓
Frontend: videoStore.saveVideo(videoData)
    ↓
API: POST /api/videos
    ↓
Backend: VideoController::create()
    ↓
Validation: VideoRules 驗證
    ↓
VideoModel::insert()
    ↓
MariaDB: INSERT INTO videos
    ↓
Response: 201 Created
    ↓
Frontend: 更新 UI 並顯示成功訊息
```

#### 2.3.2 播放清單播放流程

```
使用者點擊「播放清單」
    ↓
API: GET /api/playlists/{id}/items
    ↓
Backend: PlaylistItemController::index()
    ↓
PlaylistItemModel::getPlaylistVideos() 
    ↓
MariaDB: SELECT with JOIN (videos + playlist_items)
    ↓
Response: JSON array of videos (ordered by position)
    ↓
Frontend: usePlaylistPlayer.js 初始化
    ↓
播放第一首影片
    ↓
監聽 onStateChange 事件
    ↓
影片結束時自動播放下一首
    ↓
循環播放（回到第一首）
```

---

## 3. 資料模型深度分析

### 3.1 ER 關係圖

```
┌────────────────────┐
│     playlists      │
│ ─────────────────  │
│ PK: id             │
│     name           │
│     description    │
│     is_active      │
│     created_at     │
│     updated_at     │
└────────┬───────────┘
         │
         │ 1
         │
         │ N
         │
┌────────▼───────────┐
│  playlist_items    │
│ ─────────────────  │
│ PK: id             │
│ FK: playlist_id    │────┐
│ FK: video_id       │    │
│     position       │    │
│     created_at     │    │
└────────────────────┘    │
                          │
                          │ N
                          │
                          │ 1
                          │
                   ┌──────▼────────┐
                   │    videos     │
                   │ ────────────  │
                   │ PK: id        │
                   │ UK: video_id  │
                   │     title     │
                   │     duration  │
                   │     ...       │
                   └───────────────┘
```

### 3.2 關鍵關係說明

**多對多關係**: Playlist ↔ Video
- 透過 `playlist_items` 中介表實現
- 支援同一影片在同一播放清單中出現多次
- 支援同一影片出現在多個播放清單中

**CASCADE 刪除**:
- 刪除 Playlist → 自動刪除所有 playlist_items
- 刪除 Video → 自動從所有播放清單中移除

**唯一約束**:
- `videos.video_id` 確保 YouTube 影片不重複
- `playlist_items(playlist_id, position)` 確保順序唯一

### 3.3 資料庫優化建議

#### 索引策略

**現有索引** (已規劃):
```sql
-- videos
PRIMARY KEY (id)
UNIQUE KEY (video_id)
INDEX (title)
INDEX (created_at)
FULLTEXT (title, description)

-- playlists
PRIMARY KEY (id)
INDEX (name)
INDEX (is_active)

-- playlist_items
PRIMARY KEY (id)
INDEX (playlist_id)
INDEX (video_id)
INDEX (playlist_id, position)
UNIQUE (playlist_id, position)
```

**建議新增索引**:
```sql
-- 支援按頻道篩選
CREATE INDEX idx_channel_id ON videos(channel_id);

-- 支援按時長排序
CREATE INDEX idx_duration ON videos(duration);

-- 組合索引優化常用查詢
CREATE INDEX idx_active_created ON playlists(is_active, created_at DESC);
```

#### 查詢優化範例

**N+1 問題避免**:
```php
// ❌ 不佳：N+1 查詢
$playlists = $playlistModel->findAll();
foreach ($playlists as $playlist) {
    $playlist->videos = $playlistItemModel->getPlaylistVideos($playlist->id);
}

// ✅ 良好：單一查詢
$sql = "
    SELECT p.*, v.*, pi.position
    FROM playlists p
    LEFT JOIN playlist_items pi ON p.id = pi.playlist_id
    LEFT JOIN videos v ON pi.video_id = v.id
    WHERE p.is_active = 1
    ORDER BY p.id, pi.position
";
```

---

## 4. API 設計評估

### 4.1 RESTful API 端點

| 端點 | 方法 | 功能 | 評估 |
|------|------|------|------|
| `/api/videos` | GET | 列出所有影片 | ✅ 標準 |
| `/api/videos/{id}` | GET | 取得單一影片 | ✅ 標準 |
| `/api/videos` | POST | 建立影片 | ✅ 標準 |
| `/api/videos/{id}` | PUT | 更新影片 | ✅ 標準 |
| `/api/videos/{id}` | DELETE | 刪除影片 | ✅ 標準 |
| `/api/videos/check/{video_id}` | GET | 檢查影片存在 | ⚠️ 非標準，建議改用查詢參數 |
| `/api/playlists` | GET | 列出所有播放清單 | ✅ 標準 |
| `/api/playlists/{id}` | GET | 取得播放清單（含影片） | ✅ 標準 |
| `/api/playlists` | POST | 建立播放清單 | ✅ 標準 |
| `/api/playlists/{id}` | PUT | 更新播放清單 | ✅ 標準 |
| `/api/playlists/{id}` | DELETE | 刪除播放清單 | ✅ 標準 |
| `/api/playlists/{id}/items` | GET | 取得播放清單項目 | ✅ 良好嵌套 |
| `/api/playlists/{id}/items` | POST | 新增項目 | ✅ 良好嵌套 |
| `/api/playlists/{id}/items/{item_id}` | DELETE | 移除項目 | ✅ 良好嵌套 |
| `/api/playlists/{id}/items/reorder` | POST | 批次重排 | ✅ 實用 |

### 4.2 API 回應格式

**建議統一格式**:
```json
{
  "status": "success|error",
  "message": "操作結果描述",
  "data": {
    // 實際資料
  },
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 100
    }
  },
  "errors": [
    // 錯誤詳情（僅在 status=error 時）
  ]
}
```

### 4.3 安全性建議

**必須實作**:
1. ✅ CORS 設定（已規劃）
2. ✅ 輸入驗證（已規劃）
3. ⚠️ 速率限制（建議新增）
4. ⚠️ API 版本控制（建議 `/api/v1/...`）
5. ⚠️ 請求大小限制

**範例 - 速率限制**:
```php
// backend/app/Filters/ThrottleFilter.php
public function before(RequestInterface $request, $arguments = null)
{
    $key = $request->getIPAddress();
    $limit = 100; // 每分鐘 100 次請求
    
    if ($this->isRateLimited($key, $limit)) {
        return Services::response()
            ->setStatusCode(429)
            ->setJSON([
                'status' => 'error',
                'message' => 'Too Many Requests'
            ]);
    }
}
```

---

## 5. 實作計畫分析

### 5.1 任務分解評估

**總任務數**: 124 個任務  
**MVP 範圍**: 53 個任務（Phase 1-3）

**階段分布**:
```
Phase 1 (Setup):              20 tasks  ████████░░ 16.1%
Phase 2 (Foundational):       12 tasks  █████░░░░░ 9.7%
Phase 3 (User Story 1 - MVP): 17 tasks  ███████░░░ 13.7%
Phase 4 (User Story 2):       23 tasks  █████████░ 18.5%
Phase 5 (User Story 3):       10 tasks  ████░░░░░░ 8.1%
Phase 6 (User Story 4):       10 tasks  ████░░░░░░ 8.1%
Phase 7 (User Story 6):        8 tasks  ███░░░░░░░ 6.5%
Phase 8 (User Story 5):       10 tasks  ████░░░░░░ 8.1%
Phase 9 (Polish):             14 tasks  █████░░░░░ 11.3%
```

### 5.2 關鍵路徑 (Critical Path)

```
T001-T020 (Setup) 
    → BLOCKING
        T021-T032 (Foundational)
            → BLOCKING
                ┌─ T033-T049 (US1) MVP ✓
                ├─ T050-T072 (US2)
                ├─ T073-T082 (US3)
                └─ [其他 User Stories...]
```

**關鍵發現**:
- Phase 1-2 為**阻塞性階段**，必須完整完成
- User Stories 之間相對獨立，可平行開發
- MVP 包含 53 個任務，預估需 **2-3 週**

### 5.3 風險評估

| 風險項目 | 機率 | 影響 | 緩解策略 |
|---------|------|------|---------|
| YouTube API 配額限制 | 高 | 中 | 快取 metadata、考慮備用方案 |
| 專案結構遷移問題 | 中 | 高 | 先建立 Git 分支、逐步測試 |
| CORS 跨域問題 | 中 | 中 | 詳細測試、提供配置文件 |
| 資料庫效能瓶頸 | 低 | 高 | 適當索引、查詢優化 |
| 前端狀態管理複雜度 | 中 | 中 | 使用 Pinia、清晰的狀態設計 |
| Docker 環境問題 | 低 | 中 | 提供詳細文件、健康檢查 |

### 5.4 時程預估

**基於 124 個任務**:

| 階段 | 任務數 | 預估時間 | 累計時間 |
|------|--------|---------|---------|
| Phase 1: Setup | 20 | 3-4 天 | 4 天 |
| Phase 2: Foundational | 12 | 2-3 天 | 7 天 |
| Phase 3: US1 (MVP) | 17 | 4-5 天 | 12 天 |
| Phase 4: US2 | 23 | 5-6 天 | 18 天 |
| Phase 5: US3 | 10 | 3-4 天 | 22 天 |
| Phase 6: US4 | 10 | 3-4 天 | 26 天 |
| Phase 7: US6 | 8 | 2-3 天 | 29 天 |
| Phase 8: US5 | 10 | 3-4 天 | 33 天 |
| Phase 9: Polish | 14 | 4-5 天 | 38 天 |

**總計**: 約 **6-8 週**（包含測試與除錯時間）

**MVP 交付**: 約 **2 週**

---

## 6. 使用者故事完整度分析

### 6.1 使用者故事概覽

| ID | 標題 | 優先級 | 驗收情境數 | 獨立性 | 可測試性 |
|----|------|--------|-----------|--------|---------|
| US1 | 儲存喜愛的影片 | P1 | 5 | ✅ 高 | ✅ 高 |
| US2 | 建立自訂播放清單 | P1 | 6 | ⚠️ 中（依賴 US1） | ✅ 高 |
| US3 | 依播放清單順序播放 | P1 | 6 | ⚠️ 低（依賴 US2） | ✅ 高 |
| US4 | 管理影片順序 | P2 | 4 | ⚠️ 低（依賴 US2,3） | ✅ 高 |
| US5 | 搜尋與篩選 | P3 | 3 | ✅ 高（僅依賴 US1） | ✅ 高 |
| US6 | 顯示影片資訊 | P2 | 3 | ✅ 高（僅依賴 US1） | ✅ 高 |

### 6.2 驗收標準品質評估

**優勢**:
- ✅ 所有故事都使用 Given-When-Then 格式
- ✅ 驗收標準明確且可測試
- ✅ 包含獨立測試描述
- ✅ 優先級理由充分

**改進建議**:
- ⚠️ US3-US4 相依性高，建議考慮合併或調整順序
- ⚠️ 缺少非功能性需求（效能、可用性）
- ⚠️ 建議增加錯誤情境處理

### 6.3 INVEST 原則檢驗

**Independent（獨立性）**:
- ✅ US1, US5, US6 高度獨立
- ⚠️ US2-US4 相互依賴

**Negotiable（可協商）**:
- ✅ 各故事都有清晰的目標，實作細節可調整

**Valuable（有價值）**:
- ✅ 每個故事都為使用者提供明確價值

**Estimable（可估算）**:
- ✅ 任務已分解，可準確估算

**Small（小）**:
- ✅ 大部分故事大小適中
- ⚠️ US2 可能需要 5-6 天，考慮拆分

**Testable（可測試）**:
- ✅ 所有故事都有明確驗收標準

---

## 7. 技術債務與改進建議

### 7.1 立即建議（Phase 1-3）

**優先級：高**

1. **環境變數管理**
   ```bash
   # frontend/.env
   VITE_API_URL=http://localhost:8080/api
   VITE_YOUTUBE_API_KEY=your_api_key_here
   
   # backend/.env
   YOUTUBE_API_KEY=your_api_key_here
   CACHE_DRIVER=file
   CACHE_TTL=3600
   ```

2. **錯誤處理標準化**
   ```javascript
   // frontend/src/utils/errorHandler.js
   export const handleApiError = (error) => {
     if (error.response) {
       // 伺服器回應錯誤
       const status = error.response.status;
       const message = error.response.data.message || '操作失敗';
       
       switch(status) {
         case 400: return { type: 'warning', message };
         case 404: return { type: 'error', message: '資源不存在' };
         case 500: return { type: 'error', message: '伺服器錯誤' };
         default: return { type: 'error', message };
       }
     } else if (error.request) {
       // 請求發送但無回應
       return { type: 'error', message: '無法連接到伺服器' };
     } else {
       // 其他錯誤
       return { type: 'error', message: error.message };
     }
   };
   ```

3. **API 回應統一包裝**
   ```php
   // backend/app/Helpers/response_helper.php
   function api_response($data = null, $message = '', $status = 200) {
       return response()->setJSON([
           'status' => $status >= 200 && $status < 300 ? 'success' : 'error',
           'message' => $message,
           'data' => $data,
           'timestamp' => date('c')
       ])->setStatusCode($status);
   }
   ```

### 7.2 中期改進（Phase 4-6）

**優先級：中**

1. **快取層實作**
   ```php
   // backend/app/Libraries/CacheManager.php
   class CacheManager {
       public function remember($key, $ttl, $callback) {
           $cached = cache()->get($key);
           if ($cached !== null) {
               return $cached;
           }
           
           $value = $callback();
           cache()->save($key, $value, $ttl);
           return $value;
       }
   }
   ```

2. **前端狀態持久化**
   ```javascript
   // frontend/src/plugins/persistedState.js
   import { createPersistedState } from 'pinia-plugin-persistedstate'
   
   export default createPersistedState({
     key: 'free-youtube',
     storage: localStorage,
     paths: ['video.favorites', 'playlist.recentlyPlayed']
   })
   ```

3. **日誌系統**
   ```php
   // backend/app/Config/Logger.php
   log_message('error', 'Video fetch failed: ' . $e->getMessage());
   log_message('info', 'User saved video: ' . $videoId);
   ```

### 7.3 長期優化（Phase 7-9）

**優先級：低**

1. **效能監控**
   - 整合 New Relic 或 Datadog
   - 前端 Lighthouse 分數優化
   - 資料庫查詢分析

2. **自動化測試**
   ```javascript
   // frontend/tests/unit/videoStore.spec.js
   describe('Video Store', () => {
     it('should save video to favorites', async () => {
       const store = useVideoStore()
       await store.saveVideo(mockVideo)
       expect(store.favorites).toContain(mockVideo)
     })
   })
   ```

3. **CI/CD 管線**
   ```yaml
   # .github/workflows/ci.yml
   name: CI/CD Pipeline
   on: [push, pull_request]
   jobs:
     test:
       runs-on: ubuntu-latest
       steps:
         - uses: actions/checkout@v2
         - name: Run Backend Tests
           run: cd backend && composer test
         - name: Run Frontend Tests
           run: cd frontend && npm test
   ```

---

## 8. 安全性分析

### 8.1 OWASP Top 10 檢查清單

| 風險 | 狀態 | 緩解措施 |
|------|------|---------|
| A01: Broken Access Control | ⚠️ 未實作 | 建議：新增 API Key 驗證 |
| A02: Cryptographic Failures | ✅ 無敏感資料 | N/A |
| A03: Injection | ✅ 已規劃 | 使用 CI4 Query Builder |
| A04: Insecure Design | ✅ 良好 | 清晰的架構設計 |
| A05: Security Misconfiguration | ⚠️ 待檢查 | 確保生產環境配置 |
| A06: Vulnerable Components | ⚠️ 待檢查 | 定期更新依賴 |
| A07: Authentication Failures | ✅ 已排除 | 無認證系統 |
| A08: Software and Data Integrity | ⚠️ 待實作 | 建議新增資料備份 |
| A09: Security Logging | ⚠️ 待強化 | 增加詳細日誌 |
| A10: SSRF | ⚠️ 待檢查 | 驗證 YouTube URL |

### 8.2 具體安全建議

**1. 輸入驗證強化**
```php
// backend/app/Validation/VideoRules.php
public function validateYoutubeUrl(string $url): bool
{
    $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/';
    if (!preg_match($pattern, $url)) {
        return false;
    }
    
    // 防止 SSRF
    $parsed = parse_url($url);
    $allowedHosts = ['youtube.com', 'www.youtube.com', 'youtu.be'];
    return in_array($parsed['host'] ?? '', $allowedHosts);
}
```

**2. XSS 防護**
```vue
<!-- frontend/src/components/VideoCard.vue -->
<template>
  <!-- 使用 v-text 而非 v-html -->
  <h3 v-text="video.title"></h3>
  
  <!-- 或使用 DOMPurify 清理 -->
  <div v-html="sanitize(video.description)"></div>
</template>

<script setup>
import DOMPurify from 'dompurify'

const sanitize = (html) => DOMPurify.sanitize(html)
</script>
```

**3. CORS 嚴格配置**
```php
// backend/app/Config/Cors.php
public $allowedOrigins = [
    'http://localhost:5173',  // 開發環境
    'https://your-domain.com' // 生產環境
];

public $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
public $allowedHeaders = ['Content-Type', 'Authorization'];
public $maxAge = 7200;
```

---

## 9. 部署策略

### 9.1 開發環境 (已規劃)

```yaml
# docker-compose.yml
version: '3.8'
services:
  mariadb:
    image: mariadb:10.6
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: free_youtube
    ports:
      - "3306:3306"
    volumes:
      - mariadb_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  phpmyadmin:
    image: phpmyadmin:latest
    ports:
      - "8081:80"
    environment:
      PMA_HOST: mariadb
    depends_on:
      mariadb:
        condition: service_healthy

  backend:
    build: ./backend
    ports:
      - "8080:8080"
    volumes:
      - ./backend:/var/www/html
    depends_on:
      mariadb:
        condition: service_healthy

  frontend:
    build: ./frontend
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
    environment:
      - VITE_API_URL=http://localhost:8080/api
```

### 9.2 生產環境建議

**Dockerfile 優化**:
```dockerfile
# backend/Dockerfile (生產版)
FROM php:8.1-fpm-alpine

# 安裝依賴
RUN apk add --no-cache \
    mysql-client \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 複製應用程式
WORKDIR /var/www/html
COPY . .

# 安裝依賴並優化
RUN composer install --no-dev --optimize-autoloader

# 設定權限
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
CMD ["php", "spark", "serve", "--host", "0.0.0.0"]
```

**Nginx 反向代理**:
```nginx
# nginx.conf
server {
    listen 80;
    server_name your-domain.com;

    # 前端
    location / {
        root /var/www/frontend/dist;
        try_files $uri $uri/ /index.html;
    }

    # 後端 API
    location /api {
        proxy_pass http://backend:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 9.3 監控與備份

**健康檢查端點**:
```php
// backend/app/Controllers/HealthController.php
public function check()
{
    $dbStatus = $this->checkDatabase();
    $diskSpace = $this->checkDiskSpace();
    
    return $this->response->setJSON([
        'status' => 'healthy',
        'database' => $dbStatus,
        'disk_space' => $diskSpace,
        'timestamp' => time()
    ]);
}
```

**自動備份腳本**:
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="free_youtube"

# 資料庫備份
docker exec mariadb mysqldump -u root -psecret $DB_NAME > \
    $BACKUP_DIR/db_$DATE.sql

# 保留最近 7 天的備份
find $BACKUP_DIR -name "db_*.sql" -mtime +7 -delete
```

---

## 10. 效能優化建議

### 10.1 資料庫層

**1. 查詢優化**
```sql
-- 使用 EXPLAIN 分析慢查詢
EXPLAIN SELECT v.*, pi.position 
FROM videos v
JOIN playlist_items pi ON v.id = pi.video_id
WHERE pi.playlist_id = 1
ORDER BY pi.position;

-- 確保使用了索引
-- key 欄位應顯示 idx_playlist_id
```

**2. 連線池配置**
```php
// backend/app/Config/Database.php
public array $default = [
    // ... 其他設定
    'pConnect' => true,  // 持久連線
    'DBDebug'  => false, // 生產環境關閉
    'cacheOn'  => true,  // 啟用查詢快取
    'compress' => true,  // 啟用壓縮
];
```

### 10.2 應用層

**1. API 快取**
```php
// backend/app/Controllers/Api/VideoController.php
public function index()
{
    $cacheKey = 'videos_list_' . $this->request->getGet('page');
    
    return cache()->remember($cacheKey, 300, function() {
        return $this->videoModel
            ->orderBy('created_at', 'DESC')
            ->paginate(20);
    });
}
```

**2. 批次載入**
```javascript
// frontend/src/services/api/videoService.js
export const fetchVideosBatch = async (videoIds) => {
  // 一次請求多個影片，減少 HTTP 往返
  const response = await api.post('/videos/batch', { ids: videoIds })
  return response.data
}
```

### 10.3 前端層

**1. 圖片延遲載入**
```vue
<template>
  <img 
    :src="placeholder" 
    :data-src="video.thumbnail_url"
    loading="lazy"
    class="video-thumbnail"
  />
</template>
```

**2. 虛擬滾動**
```vue
<!-- 使用 vue-virtual-scroller -->
<RecycleScroller
  :items="videos"
  :item-size="120"
  key-field="id"
>
  <template #default="{ item }">
    <VideoCard :video="item" />
  </template>
</RecycleScroller>
```

**3. 代碼分割**
```javascript
// frontend/src/router/index.js
const routes = [
  {
    path: '/videos',
    component: () => import('../views/VideoLibrary.vue') // 懶載入
  },
  {
    path: '/playlists',
    component: () => import('../views/PlaylistManager.vue')
  }
]
```

### 10.4 網路層

**1. HTTP/2 與壓縮**
```nginx
server {
    listen 443 ssl http2;
    
    # Gzip 壓縮
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
    gzip_min_length 1000;
    
    # Brotli 壓縮（更好）
    brotli on;
    brotli_types text/plain text/css application/json;
}
```

**2. CDN 整合**
```javascript
// frontend/vite.config.js
export default {
  build: {
    rollupOptions: {
      output: {
        assetFileNames: 'assets/[name].[hash][extname]'
      }
    }
  }
}
```

---

## 11. 測試策略

### 11.1 測試金字塔

```
              ╱ ╲
             ╱ E2E╲          5% - 端到端測試
            ╱───────╲
           ╱Integration╲     15% - 整合測試
          ╱─────────────╲
         ╱   Unit Tests  ╲   80% - 單元測試
        ╱─────────────────╲
```

### 11.2 單元測試範例

**後端測試** (PHPUnit):
```php
// backend/tests/unit/VideoModelTest.php
class VideoModelTest extends CIUnitTestCase
{
    protected $videoModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->videoModel = new VideoModel();
    }
    
    public function testCreateVideo()
    {
        $data = [
            'video_id' => 'test123',
            'title' => '測試影片',
            'youtube_url' => 'https://youtube.com/watch?v=test123'
        ];
        
        $id = $this->videoModel->insert($data);
        $this->assertIsNumeric($id);
        
        $video = $this->videoModel->find($id);
        $this->assertEquals('test123', $video->video_id);
    }
    
    public function testUniqueVideoId()
    {
        $data = [
            'video_id' => 'duplicate123',
            'title' => '重複影片',
            'youtube_url' => 'https://youtube.com/watch?v=dup'
        ];
        
        $this->videoModel->insert($data);
        
        $this->expectException(DatabaseException::class);
        $this->videoModel->insert($data); // 應該失敗
    }
}
```

**前端測試** (Vitest):
```javascript
// frontend/tests/unit/videoStore.spec.js
import { setActivePinia, createPinia } from 'pinia'
import { useVideoStore } from '@/stores/videoStore'
import { describe, it, expect, beforeEach, vi } from 'vitest'

describe('Video Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('should add video to store', () => {
    const store = useVideoStore()
    const video = {
      id: 1,
      video_id: 'test123',
      title: '測試影片'
    }
    
    store.addVideo(video)
    expect(store.videos).toHaveLength(1)
    expect(store.videos[0].video_id).toBe('test123')
  })

  it('should handle API errors', async () => {
    const store = useVideoStore()
    const mockError = new Error('API Error')
    
    // Mock API 失敗
    vi.spyOn(videoService, 'createVideo').mockRejectedValue(mockError)
    
    await store.saveVideo({ title: 'Test' })
    expect(store.error).toBe('API Error')
  })
})
```

### 11.3 整合測試

**API 測試**:
```php
// backend/tests/integration/VideoApiTest.php
class VideoApiTest extends FeatureTestCase
{
    public function testGetVideos()
    {
        $result = $this->call('GET', '/api/videos');
        
        $this->assertResponseCode(200);
        $this->assertResponseHasKey('data');
        $this->assertResponseHasKey('pagination');
    }
    
    public function testCreateVideoRequiresAuth()
    {
        $result = $this->call('POST', '/api/videos', [
            'video_id' => 'test',
            'title' => 'Test'
        ]);
        
        $this->assertResponseCode(401); // 若有認證
    }
}
```

### 11.4 E2E 測試 (選用)

**Cypress 範例**:
```javascript
// frontend/cypress/e2e/video-management.cy.js
describe('Video Management', () => {
  beforeEach(() => {
    cy.visit('http://localhost:5173')
  })

  it('should save a video', () => {
    cy.get('[data-testid="video-url-input"]')
      .type('https://youtube.com/watch?v=test123')
    
    cy.get('[data-testid="save-video-btn"]').click()
    
    cy.get('[data-testid="success-message"]')
      .should('contain', '影片已儲存')
    
    cy.get('[data-testid="video-list"]')
      .should('contain', 'test123')
  })

  it('should create playlist and add videos', () => {
    cy.get('[data-testid="create-playlist-btn"]').click()
    cy.get('[data-testid="playlist-name-input"]').type('我的播放清單')
    cy.get('[data-testid="submit-btn"]').click()
    
    // 新增影片到播放清單
    cy.get('[data-testid="video-card"]:first')
      .find('[data-testid="add-to-playlist"]')
      .click()
    
    cy.get('[data-testid="playlist-selector"]')
      .select('我的播放清單')
    
    cy.get('[data-testid="confirm-btn"]').click()
    
    // 驗證
    cy.visit('/playlists/1')
    cy.get('[data-testid="playlist-videos"]')
      .should('have.length.greaterThan', 0)
  })
})
```

---

## 12. 關鍵決策記錄 (ADR)

### ADR-001: 選擇 CodeIgniter 4 作為後端框架

**狀態**: 已接受  
**日期**: 2025-10-27  
**決策者**: 開發團隊

**背景**:
需要選擇一個 PHP 框架來建立 RESTful API。

**選項**:
1. CodeIgniter 4
2. Laravel
3. Slim Framework

**決策**:
選擇 CodeIgniter 4

**理由**:
- ✅ 輕量級，效能優異
- ✅ 學習曲線平緩
- ✅ 內建 ORM 足夠使用
- ✅ 良好的文件
- ⚠️ 生態系統較小（可接受）

**後果**:
- 開發速度快
- 需要手動處理一些進階功能
- 未來若需要更複雜功能，可能需要自行實作

---

### ADR-002: 前後端分離架構

**狀態**: 已接受  
**日期**: 2025-10-27

**背景**:
決定專案架構模式。

**選項**:
1. 前後端分離（SPA + API）
2. 傳統 MVC（伺服器渲染）
3. SSR（如 Nuxt.js）

**決策**:
前後端分離

**理由**:
- ✅ 前端可獨立開發與部署
- ✅ API 可被其他客戶端使用
- ✅ 更好的效能（靜態資源快取）
- ✅ 技術棧靈活

**後果**:
- 需要處理 CORS
- 部署稍微複雜
- 但整體更靈活且可擴展

---

### ADR-003: 使用 MariaDB 而非 PostgreSQL

**狀態**: 已接受  
**日期**: 2025-10-27

**背景**:
選擇關聯式資料庫。

**選項**:
1. MariaDB
2. PostgreSQL
3. MySQL

**決策**:
MariaDB

**理由**:
- ✅ 與 MySQL 高度相容
- ✅ 效能優異
- ✅ 開源且免費
- ✅ 良好的 Docker 支援
- ✅ 對本專案需求足夠

**後果**:
- 不需要使用 PostgreSQL 的進階功能（JSON, Array）
- MariaDB 足以滿足需求

---

### ADR-004: 不實作使用者認證系統

**狀態**: 已接受  
**日期**: 2025-10-27

**背景**:
決定是否在 MVP 階段實作認證。

**決策**:
MVP 不實作認證

**理由**:
- ✅ 簡化初期開發
- ✅ 可先聚焦核心功能
- ✅ 使用者可在本地使用
- ⚠️ 未來可添加

**後果**:
- 資料儲存在本地
- 未來添加認證時需要資料遷移機制
- 目前適合單使用者場景

---

## 13. 文件清單與狀態

| 文件名稱 | 狀態 | 完整度 | 備註 |
|---------|------|--------|------|
| spec.md | ✅ 完成 | 100% | 6 個使用者故事，23 個驗收情境 |
| plan.md | ✅ 完成 | 100% | 包含技術架構與實作階段 |
| tasks.md | ✅ 完成 | 100% | 124 個任務，清晰分組 |
| data-model.md | ✅ 完成 | 100% | 完整的資料庫設計與模型 |
| research.md | ⚠️ 空白 | 0% | 建議補充技術選型研究 |
| quickstart.md | ⚠️ 空白 | 0% | 建議補充快速開始指南 |
| requirements.md | ⚠️ 空白 | 0% | 建議補充需求檢查清單 |

---

## 14. 建議的下一步行動

### 14.1 立即行動（本週）

1. **✅ 執行 Phase 1: 專案結構重組**
   ```bash
   # T001-T007: 移動前端到 frontend/
   mkdir frontend
   mv src public index.html package.json vite.config.js frontend/
   cd frontend && npm install
   ```

2. **✅ 建立 backend/ 目錄**
   ```bash
   # T008-T013: 初始化 CI4
   mkdir backend
   cd backend
   composer create-project codeigniter4/appstarter .
   ```

3. **✅ 設定 Docker 環境**
   ```bash
   # T014-T020: 啟動所有服務
   docker-compose up -d
   # 驗證服務
   curl http://localhost:8080/api/health
   curl http://localhost:5173
   ```

### 14.2 短期目標（1-2 週）

1. **完成 Phase 2: Foundational**
   - 建立資料庫遷移
   - 實作 Models 和 Entities
   - 設定 API 路由

2. **完成 Phase 3: User Story 1 (MVP)**
   - 實作影片 CRUD API
   - 建立前端影片庫介面
   - 測試完整流程

3. **文件補充**
   - 編寫 quickstart.md
   - 編寫 research.md
   - 補充 requirements.md

### 14.3 中期目標（3-4 週）

1. **完成 Phase 4-5: 播放清單功能**
   - 播放清單 CRUD
   - 自動播放功能
   - 循環播放

2. **測試與優化**
   - 撰寫單元測試
   - 效能測試
   - 錯誤處理強化

### 14.4 長期目標（5-8 週）

1. **完成所有 User Stories**
2. **Polish 階段**
   - 文件完善
   - 效能優化
   - 安全強化
3. **部署準備**
   - 生產環境配置
   - 監控設置
   - 備份機制

---

## 15. 風險矩陣

```
高影響 │
      │  [YouTube API]     [資料遷移]
      │
      │  [專案結構]        [CORS問題]
      │
影    │  [狀態管理]        [效能瓶頸]
響    │
      │  [Docker環境]      
低影響│
      └─────────────────────────────
         低機率          高機率
```

**圖例說明**:
- 🔴 高影響高機率：需要立即緩解
- 🟡 中等風險：需要監控
- 🟢 低風險：可接受

---

## 16. 總結與建議

### 16.1 專案健康度評分

| 面向 | 評分 | 說明 |
|------|------|------|
| 需求清晰度 | 9/10 | 使用者故事完整，驗收標準明確 |
| 技術可行性 | 8/10 | 技術棧成熟，無重大技術風險 |
| 架構設計 | 9/10 | 前後端分離，結構清晰 |
| 任務分解 | 9/10 | 124 個任務，粒度適中 |
| 時程規劃 | 8/10 | 6-8 週合理，需留緩衝時間 |
| 風險管理 | 7/10 | 主要風險已識別，需緩解計畫 |
| 文件完整度 | 7/10 | 核心文件完整，部分待補充 |

**總體評分**: **8.1/10** ⭐⭐⭐⭐

### 16.2 關鍵成功因素

1. ✅ **清晰的使用者故事**: 6 個優先級分明的故事
2. ✅ **詳細的任務分解**: 124 個可執行任務
3. ✅ **成熟的技術棧**: CI4 + MariaDB + Vue.js
4. ✅ **良好的專案結構**: 前後端分離
5. ⚠️ **需補充測試**: 建議增加自動化測試
6. ⚠️ **需強化監控**: 建議添加日誌與監控

### 16.3 最終建議

**立即執行**:
1. 開始 Phase 1 專案重組（T001-T020）
2. 補充缺失文件（quickstart.md, research.md）
3. 設置 Git 版本控制與分支策略

**短期優先**:
1. 完成 MVP（Phase 1-3）
2. 建立基本測試框架
3. 實作錯誤處理機制

**長期規劃**:
1. 考慮添加認證系統
2. 規劃資料遷移方案
3. 準備生產環境部署

**風險緩解**:
1. YouTube API 配額：實作快取機制
2. 專案重組：建立 Git 分支，逐步測試
3. 效能問題：早期進行負載測試

---

## 17. 附錄

### A. 參考資源

**官方文件**:
- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)
- [Vue.js 3 Documentation](https://vuejs.org/guide/)
- [YouTube IFrame Player API](https://developers.google.com/youtube/iframe_api_reference)

**社群資源**:
- [CodeIgniter Forum](https://forum.codeigniter.com/)
- [Vue.js Discord](https://discord.com/invite/vue)
- [Stack Overflow](https://stackoverflow.com/)

**工具與套件**:
- [Composer](https://getcomposer.org/)
- [Vite](https://vitejs.dev/)
- [Pinia](https://pinia.vuejs.org/)
- [Docker](https://www.docker.com/)

### B. 專業術語表

| 術語 | 說明 |
|------|------|
| CI4 | CodeIgniter 4 框架簡稱 |
| ORM | Object-Relational Mapping，物件關聯映射 |
| CRUD | Create, Read, Update, Delete 基本操作 |
| SPA | Single Page Application，單頁應用 |
| API | Application Programming Interface |
| CORS | Cross-Origin Resource Sharing，跨域資源共享 |
| MVP | Minimum Viable Product，最小可行產品 |
| E2E | End-to-End，端對端測試 |

### C. 版本歷史

| 版本 | 日期 | 變更內容 | 作者 |
|------|------|---------|------|
| 1.0 | 2025-10-27 | 初始版本 | AI Assistant |
| 2.0 | 2025-10-27 | 新增專案結構調整分析 | AI Assistant |

---

**文件結束**

如有任何問題或需要進一步分析，請聯繫開發團隊。

**生成時間**: 2025-10-27 07:50:04 UTC  
**分析工具**: speckit.analyze  
**專案**: free_youtube (002-playlist-database)
