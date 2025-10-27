# Tasks: 播放清單與資料庫整合 (002-playlist-database)

**輸入**: Design documents from `/specs/002-playlist-database/`  
**Prerequisites**: plan.md, spec.md, data-model.md  
**技術棧**: CodeIgniter 4 + MariaDB + Vue.js 3 + Vite

**組織方式**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Backend**: `backend/app/` (CodeIgniter 4 structure)
- **Frontend**: `frontend/src/` (Vue.js application)
- **Database**: `backend/app/Database/Migrations/`
- **Tests**: `backend/tests/`, `frontend/tests/`

---

## Phase 1: Setup (共享基礎建設)

**目的**: 專案初始化與基本結構建立

### 專案結構重組

- [ ] T001 建立 frontend/ 目錄
- [ ] T002 移動前端檔案到 frontend/ 目錄 (src/, public/, index.html, package.json, vite.config.js, vitest.config.js, .eslintrc.cjs, .prettierrc)
- [ ] T003 更新 vite.config.js 中的路徑設定
- [ ] T004 更新 package.json 中的 scripts 路徑（如果需要）
- [ ] T005 更新 .gitignore 以反映新的目錄結構
- [ ] T006 在 frontend/ 目錄執行 npm install 驗證前端專案正常運作
- [ ] T007 更新 README.md 說明新的專案結構

### CodeIgniter 4 後端建置

- [ ] T008 建立 backend/ 目錄
- [ ] T009 建立 CodeIgniter 4 專案結構於 backend/ 目錄
- [ ] T010 初始化 Composer 並安裝 CI4 相依套件於 backend/composer.json
- [ ] T011 [P] 建立 .env 檔案並設定 MariaDB 連線資訊於 backend/.env
- [ ] T012 [P] 設定 CORS 過濾器於 backend/app/Filters/CorsFilter.php
- [ ] T013 [P] 建立 API 回應格式輔助函數於 backend/app/Helpers/response_helper.php

### Docker 環境設定

- [ ] T014 設定 Docker Compose 服務 (MariaDB + phpMyAdmin + CI4 Backend + Vue Frontend) 於 docker-compose.yml
- [ ] T015 建立 backend/Dockerfile 用於 CI4 容器
- [ ] T016 更新 frontend/Dockerfile 路徑設定
- [ ] T017 在 frontend/.env 或 vite.config.js 設定 API 基礎網址 (VITE_API_URL=http://localhost:8080)
- [ ] T018 啟動 Docker 容器並驗證所有服務正常運作
- [ ] T019 驗證 MariaDB (port 3306) 與 phpMyAdmin (http://localhost:8081) 連線正常
- [ ] T020 驗證前端 (http://localhost:5173) 與後端 API (http://localhost:8080) 可正常通訊

---

## Phase 2: Foundational (阻塞性基礎建設)

**目的**: 核心基礎建設必須在任何使用者故事前完成

**⚠️ 重要**: 所有使用者故事工作必須等待此階段完成

- [ ] T021 建立資料庫遷移：videos 資料表於 backend/app/Database/Migrations/2025-10-27-000001_CreateVideosTable.php
- [ ] T022 建立資料庫遷移：playlists 資料表於 backend/app/Database/Migrations/2025-10-27-000002_CreatePlaylistsTable.php
- [ ] T023 建立資料庫遷移：playlist_items 資料表於 backend/app/Database/Migrations/2025-10-27-000003_CreatePlaylistItemsTable.php
- [ ] T024 執行 migrations 並使用 phpMyAdmin 驗證資料表結構
- [ ] T025 [P] 建立 Video Entity 於 backend/app/Entities/Video.php
- [ ] T026 [P] 建立 Playlist Entity 於 backend/app/Entities/Playlist.php
- [ ] T027 [P] 建立 PlaylistItem Entity 於 backend/app/Entities/PlaylistItem.php
- [ ] T028 [P] 實作 VideoModel 於 backend/app/Models/VideoModel.php
- [ ] T029 [P] 實作 PlaylistModel 於 backend/app/Models/PlaylistModel.php
- [ ] T030 [P] 實作 PlaylistItemModel 於 backend/app/Models/PlaylistItemModel.php
- [ ] T031 設定 API 路由於 backend/app/Config/Routes.php
- [ ] T032 建立測試資料 Seeder 於 backend/app/Database/Seeds/VideoSeeder.php

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - 儲存喜愛的影片 (Priority: P1) 🎯 MVP

**目標**: 使用者可以將 YouTube 影片儲存到資料庫並查看已儲存的影片清單

**獨立測試**: 新增一部影片 → 關閉應用程式 → 重新開啟 → 驗證影片仍然存在

### Backend Implementation for US1

- [ ] T033 [P] [US1] 實作 VideoController::index() 取得所有影片 API 於 backend/app/Controllers/Api/VideoController.php
- [ ] T034 [P] [US1] 實作 VideoController::show() 取得單一影片 API 於 backend/app/Controllers/Api/VideoController.php
- [ ] T035 [US1] 實作 VideoController::create() 新增影片 API 於 backend/app/Controllers/Api/VideoController.php
- [ ] T036 [US1] 實作 VideoController::update() 更新影片 API 於 backend/app/Controllers/Api/VideoController.php
- [ ] T037 [US1] 實作 VideoController::delete() 刪除影片 API 於 backend/app/Controllers/Api/VideoController.php
- [ ] T038 [US1] 實作 VideoController::check() 檢查影片是否存在 API 於 backend/app/Controllers/Api/VideoController.php
- [ ] T039 [US1] 新增影片驗證規則於 backend/app/Validation/VideoRules.php
- [ ] T040 [US1] 實作搜尋功能於 VideoModel::search() 於 backend/app/Models/VideoModel.php

### Frontend Implementation for US1

- [ ] T041 [P] [US1] 建立 Video API Service 於 frontend/src/services/api/videoService.js
- [ ] T042 [US1] 建立 Video Store (Pinia) 於 frontend/src/stores/videoStore.js
- [ ] T043 [P] [US1] 建立 VideoCard 元件於 frontend/src/components/VideoCard.vue
- [ ] T044 [P] [US1] 建立 VideoList 元件於 frontend/src/components/VideoList.vue
- [ ] T045 [US1] 建立 VideoLibrary 頁面於 frontend/src/views/VideoLibrary.vue
- [ ] T046 [US1] 實作「儲存影片」按鈕於現有播放器 UI 於 frontend/src/components/YoutubePlayer.vue
- [ ] T047 [US1] 實作影片刪除功能 UI 於 frontend/src/components/VideoCard.vue
- [ ] T048 [US1] 整合 VideoLibrary 到主路由於 frontend/src/router/index.js
- [ ] T049 [US1] 新增成功/錯誤訊息提示 UI (Toast/Notification)

**Checkpoint**: 使用者可以儲存、查看、刪除影片。此故事應完全可獨立運作並測試。

---

## Phase 4: User Story 2 - 建立自訂播放清單 (Priority: P1)

**目標**: 使用者可以建立多個播放清單，將影片組織分類

**獨立測試**: 建立新播放清單 → 新增 3 部影片 → 驗證播放清單包含正確影片

### Backend Implementation for US2

- [ ] T037 [P] [US2] 實作 PlaylistController::index() 取得所有播放清單 API 於 backend/app/Controllers/Api/PlaylistController.php
- [ ] T038 [P] [US2] 實作 PlaylistController::show() 取得單一播放清單(含影片) API 於 backend/app/Controllers/Api/PlaylistController.php
- [ ] T039 [US2] 實作 PlaylistController::create() 建立播放清單 API 於 backend/app/Controllers/Api/PlaylistController.php
- [ ] T040 [US2] 實作 PlaylistController::update() 更新播放清單 API 於 backend/app/Controllers/Api/PlaylistController.php
- [ ] T041 [US2] 實作 PlaylistController::delete() 刪除播放清單 API 於 backend/app/Controllers/Api/PlaylistController.php
- [ ] T042 [US2] 新增播放清單驗證規則於 backend/app/Validation/PlaylistRules.php
- [ ] T043 [US2] 實作 PlaylistModel::getWithVideoCount() 於 backend/app/Models/PlaylistModel.php

### Playlist Items API for US2

- [ ] T044 [P] [US2] 實作 PlaylistItemController::index() 取得播放清單項目 API 於 backend/app/Controllers/Api/PlaylistItemController.php
- [ ] T045 [US2] 實作 PlaylistItemController::create() 新增影片到播放清單 API 於 backend/app/Controllers/Api/PlaylistItemController.php
- [ ] T046 [US2] 實作 PlaylistItemController::delete() 從播放清單移除影片 API 於 backend/app/Controllers/Api/PlaylistItemController.php
- [ ] T047 [US2] 實作自動取得下一個 position 邏輯於 PlaylistItemModel::getNextPosition() 於 backend/app/Models/PlaylistItemModel.php
- [ ] T048 [US2] 實作取得播放清單影片於 PlaylistItemModel::getPlaylistVideos() 於 backend/app/Models/PlaylistItemModel.php

### Frontend Implementation for US2

- [ ] T049 [P] [US2] 建立 Playlist API Service 於 frontend/src/services/api/playlistService.js
- [ ] T050 [US2] 建立 Playlist Store (Pinia) 於 frontend/src/stores/playlistStore.js
- [ ] T051 [P] [US2] 建立 PlaylistCard 元件於 frontend/src/components/PlaylistCard.vue
- [ ] T052 [P] [US2] 建立 PlaylistList 元件於 frontend/src/components/PlaylistList.vue
- [ ] T053 [US2] 建立 CreatePlaylistModal 元件於 frontend/src/components/modals/CreatePlaylistModal.vue
- [ ] T054 [US2] 建立 PlaylistDetail 頁面於 frontend/src/views/PlaylistDetail.vue
- [ ] T055 [US2] 建立 PlaylistManager 頁面於 frontend/src/views/PlaylistManager.vue
- [ ] T056 [US2] 實作「新增到播放清單」功能於 VideoCard 元件於 frontend/src/components/VideoCard.vue
- [ ] T057 [US2] 實作播放清單編輯功能 (名稱、描述) UI
- [ ] T058 [US2] 實作播放清單刪除功能 (含確認對話框) UI
- [ ] T059 [US2] 整合 PlaylistManager 到主路由於 frontend/src/router/index.js

**Checkpoint**: 使用者可以建立、查看、編輯、刪除播放清單，並新增/移除影片。使用者故事 1 和 2 應該都能獨立運作。

---

## Phase 5: User Story 3 - 依播放清單順序播放影片 (Priority: P1)

**目標**: 系統按照播放清單順序自動播放所有影片，支援循環播放

**獨立測試**: 建立包含 3 部影片的播放清單 → 點擊播放 → 驗證影片按順序自動播放

### Backend Support for US3

- [ ] T060 [US3] 確保 PlaylistItemController::index() 回傳依 position 排序的影片清單
- [ ] T061 [US3] 新增取得下一首/上一首影片的輔助方法於 PlaylistItemModel

### Frontend Implementation for US3

- [ ] T062 [US3] 擴充 YouTube Player 支援播放清單模式於 frontend/src/composables/useYoutubePlayer.js
- [ ] T063 [US3] 實作播放清單播放邏輯 (自動切換下一首) 於 frontend/src/composables/usePlaylistPlayer.js
- [ ] T064 [US3] 實作循環播放邏輯 (最後一首回到第一首)
- [ ] T065 [P] [US3] 建立播放清單控制 UI (上一首、下一首按鈕) 於 frontend/src/components/PlaylistControls.vue
- [ ] T066 [US3] 實作當前播放狀態顯示 (正在播放: 3/10) 於 frontend/src/components/PlaylistControls.vue
- [ ] T067 [US3] 實作點擊播放清單項目直接跳播功能
- [ ] T068 [US3] 整合播放清單播放功能到 PlaylistDetail 頁面
- [ ] T069 [US3] 新增視覺化標示當前播放中的影片

**Checkpoint**: 使用者可以播放整個播放清單，影片自動依序播放。所有 P1 功能現在都應該完全可運作。

---

## Phase 6: User Story 4 - 管理播放清單中的影片順序 (Priority: P2)

**目標**: 使用者可以調整播放清單中影片的播放順序

**獨立測試**: 拖曳影片改變順序 → 播放播放清單 → 驗證播放順序已更新

### Backend Implementation for US4

- [ ] T070 [US4] 實作 PlaylistItemController::updatePosition() 更新單一項目位置 API 於 backend/app/Controllers/Api/PlaylistItemController.php
- [ ] T071 [US4] 實作 PlaylistItemController::reorder() 批次重新排序 API 於 backend/app/Controllers/Api/PlaylistItemController.php
- [ ] T072 [US4] 實作位置重排邏輯於 PlaylistItemModel::reorderItems() 於 backend/app/Models/PlaylistItemModel.php
- [ ] T073 [US4] 實作刪除項目後自動調整順序於 PlaylistItemModel::deleteAndReorder() 於 backend/app/Models/PlaylistItemModel.php

### Frontend Implementation for US4

- [ ] T074 [US4] 安裝拖曳排序套件 (如 Sortable.js 或 VueDraggable) 於 frontend/package.json
- [ ] T075 [US4] 實作拖曳排序功能於 PlaylistDetail 頁面的影片清單
- [ ] T076 [P] [US4] 建立「上移」「下移」按鈕元件於 frontend/src/components/PlaylistItemActions.vue
- [ ] T077 [US4] 實作「移到最前」「移到最後」功能
- [ ] T078 [US4] 實作排序變更後自動儲存到後端
- [ ] T079 [US4] 新增排序變更的視覺回饋 (loading 狀態)

**Checkpoint**: 使用者可以透過拖曳或按鈕調整影片順序，播放時會依新順序播放。

---

## Phase 7: User Story 6 - 顯示影片資訊和縮圖 (Priority: P2)

**目標**: 在影片清單中顯示縮圖、標題、時長等詳細資訊

**獨立測試**: 儲存影片 → 驗證縮圖、標題、時長正確顯示

### Backend Implementation for US6

- [ ] T080 [US6] 實作 YouTube Data API 整合以取得影片 metadata 於 backend/app/Libraries/YoutubeApi.php
- [ ] T081 [US6] 實作影片資訊自動抓取於 VideoController::create() 時
- [ ] T082 [US6] 新增 fallback 機制處理縮圖載入失敗

### Frontend Implementation for US6

- [ ] T083 [P] [US6] 優化 VideoCard 元件顯示完整影片資訊 (縮圖、標題、時長、頻道)
- [ ] T084 [P] [US6] 實作時長格式化 (秒數轉 mm:ss) 於 frontend/src/utils/formatters.js
- [ ] T085 [US6] 實作縮圖 lazy loading 優化效能
- [ ] T086 [US6] 實作縮圖載入失敗時的預設占位圖
- [ ] T087 [US6] 新增影片資訊 tooltip 顯示完整描述

**Checkpoint**: 影片和播放清單以視覺化方式呈現，提升使用者體驗。

---

## Phase 8: User Story 5 - 搜尋與篩選已儲存的影片 (Priority: P3)

**目標**: 使用者可以快速搜尋和篩選影片

**獨立測試**: 儲存 20 部影片 → 使用搜尋功能 → 驗證搜尋結果準確

### Backend Implementation for US5

- [ ] T088 [US5] 優化 VideoModel::search() 使用 FULLTEXT 索引於 backend/app/Models/VideoModel.php
- [ ] T089 [US5] 實作進階篩選 API (依播放清單、頻道) 於 VideoController::index()
- [ ] T090 [US5] 實作分頁功能於所有列表 API

### Frontend Implementation for US5

- [ ] T091 [P] [US5] 建立 SearchBar 元件於 frontend/src/components/SearchBar.vue
- [ ] T092 [US5] 實作即時搜尋功能 (debounce) 於 VideoLibrary 頁面
- [ ] T093 [P] [US5] 建立 FilterPanel 元件於 frontend/src/components/FilterPanel.vue
- [ ] T094 [US5] 實作播放清單篩選器
- [ ] T095 [US5] 實作搜尋結果高亮顯示
- [ ] T096 [US5] 實作清除搜尋/篩選功能
- [ ] T097 [US5] 實作分頁或無限滾動載入

**Checkpoint**: 使用者可以輕鬆在大量影片中找到想要的內容。

---

## Phase 9: Polish & Cross-Cutting Concerns (優化與跨功能改進)

**目的**: 改善影響多個使用者故事的功能

- [ ] T098 [P] 實作 API 錯誤處理中介軟體於 backend/app/Filters/ErrorFilter.php
- [ ] T099 [P] 實作請求速率限制於 backend/app/Filters/ThrottleFilter.php
- [ ] T100 [P] 新增 API 文件 (Swagger/OpenAPI) 於 backend/public/api-docs/
- [ ] T101 [P] 實作前端全域錯誤處理於 frontend/src/utils/errorHandler.js
- [ ] T102 [P] 實作 loading 狀態管理於各個頁面
- [ ] T103 優化資料庫查詢效能 (使用 EXPLAIN 分析)
- [ ] T104 [P] 實作響應式設計優化 (手機、平板適配)
- [ ] T105 [P] 實作鍵盤快捷鍵 (空白鍵播放/暫停等)
- [ ] T106 實作資料匯出/匯入功能 (JSON 格式)
- [ ] T107 [P] 撰寫使用者文件於 docs/user-guide.md
- [ ] T108 [P] 撰寫開發者文件於 docs/developer-guide.md
- [ ] T109 程式碼重構與最佳化
- [ ] T110 執行 quickstart.md 驗證測試

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 無相依性 - 可立即開始
- **Foundational (Phase 2)**: 相依於 Setup 完成 - 阻塞所有使用者故事
- **User Stories (Phase 3-8)**: 全部相依於 Foundational phase 完成
  - 使用者故事之間可以平行開發 (如果有足夠人力)
  - 或依優先順序循序開發 (P1 → P2 → P3)
- **Polish (Phase 9)**: 相依於所有欲包含的使用者故事完成

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完成後可開始 - 無其他故事相依性 ✅ MVP
- **User Story 2 (P1)**: Foundational 完成後可開始 - 建議在 US1 後進行 (需要影片資料)
- **User Story 3 (P1)**: 相依於 US2 (需要播放清單功能)
- **User Story 4 (P2)**: 相依於 US2 和 US3 (需要播放清單和播放功能)
- **User Story 5 (P3)**: 可在 US1 後獨立進行
- **User Story 6 (P2)**: 可在 US1 後獨立進行

### Within Each User Story

- Backend API 應在 Frontend UI 之前或同時開發
- Models 應在 Controllers 之前完成
- 核心實作應在整合之前完成
- 完成該故事後再移往下一個優先級

### Parallel Opportunities

- Phase 1 專案重組: T001-T007 必須依序執行
- Phase 1 CI4 建置: T011-T013 可平行執行
- Phase 1 Docker: T015-T016 可平行執行
- Phase 2: T025-T030 (Entities 和 Models) 可平行執行
- 每個使用者故事中標記 [P] 的任務可平行執行
- 不同使用者故事可由不同團隊成員平行開發 (在 Foundational 完成後)

---

## Parallel Example: User Story 1

```bash
# 同時啟動 Backend API 開發:
Task T033: "實作 VideoController::index()"
Task T034: "實作 VideoController::show()"

# 同時啟動 Frontend 元件開發:
Task T041: "建立 Video API Service"
Task T043: "建立 VideoCard 元件"
Task T044: "建立 VideoList 元件"
```

---

## Implementation Strategy

### MVP First (僅 User Story 1)

1. 完成 Phase 1: 專案結構重組 + Setup
2. 完成 Phase 2: Foundational (重要 - 阻塞所有故事)
3. 完成 Phase 3: User Story 1
4. **停止並驗證**: 獨立測試 User Story 1
5. 如果就緒則部署/展示

### Incremental Delivery (增量交付)

1. 完成 Setup + Foundational → 基礎就緒
2. 新增 User Story 1 → 獨立測試 → 部署/展示 (MVP!)
3. 新增 User Story 2 → 獨立測試 → 部署/展示
4. 新增 User Story 3 → 獨立測試 → 部署/展示
5. 新增 User Story 4 → 獨立測試 → 部署/展示
6. 每個故事新增價值而不破壞先前故事

### Parallel Team Strategy (平行團隊策略)

如有多位開發者:

1. 團隊一起完成 Setup + Foundational
2. Foundational 完成後:
   - 開發者 A: User Story 1 (影片儲存)
   - 開發者 B: User Story 2 (播放清單)
   - 開發者 C: User Story 6 (影片資訊顯示)
3. 故事獨立完成並整合

---

## Summary

- **總任務數**: 123 個任務 (移除 E2E 測試任務)
- **MVP 範圍**: Phase 1 (專案重組 + Setup) + Phase 2 (Foundational) + Phase 3 (User Story 1) = ~53 個任務
- **平行機會**: 每個 phase 內標記 [P] 的任務可同時執行
- **獨立測試**: 每個使用者故事都有明確的獨立測試標準
- **建議開發順序**: 專案重組 → Setup → Foundational → US1 (MVP) → US2 → US3 → US4 → US6 → US5 → Polish

### 新增專案結構調整

**Phase 1 現在包含**:
1. **專案結構重組** (T001-T007): 將前端移至 frontend/ 目錄
2. **CI4 後端建置** (T008-T013): 建立 backend/ 目錄與基礎設定
3. **Docker 環境設定** (T014-T020): 更新容器設定以反映新結構

### 測試策略

本專案將專注於單元測試與整合測試：
- **單元測試**: 使用 PHPUnit (後端) 和 Vitest (前端)
- **整合測試**: API 端點測試
- **手動測試**: 使用者驗收測試依照 spec.md 驗收情境
- **不包含**: E2E 自動化測試（簡化開發流程）

---

## Notes

- [P] 任務 = 不同檔案，無相依性，可平行執行
- [Story] 標籤將任務映射到特定使用者故事以便追蹤
- 每個使用者故事應該可獨立完成並測試
- 在任何 checkpoint 停止以獨立驗證故事
- 避免: 模糊任務、相同檔案衝突、破壞獨立性的跨故事相依性
- 每完成一個任務或邏輯群組就 commit
- 使用 phpMyAdmin (http://localhost:8081) 驗證資料庫結構
