# Tasks: LINE Login 會員認證系統

**Input**: Design documents from `/specs/003-line-login-auth/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/openapi.yaml

**Tests**: 測試任務標記為 OPTIONAL - 僅在明確需求時實作

**Organization**: 任務按 User Story 組織,每個 Story 可獨立實作與測試

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 可平行執行 (不同檔案,無相依性)
- **[Story]**: 所屬 User Story (US1, US2, US3)
- 包含精確的檔案路徑

## 專案路徑規範

根據 plan.md,本專案為前後端分離架構:
- **後端**: `backend/app/`
- **前端**: `frontend/src/`
- **測試**: `backend/tests/`, `frontend/tests/`

---

## Phase 1: Setup (專案初始化)

**Purpose**: 環境設定與基礎架構準備

- [ ] T001 驗證 LINE Developers Channel 設定 (Channel ID, Secret, Callback URL)
- [ ] T002 [P] 設定後端環境變數檔 backend/.env (LINE_LOGIN_CHANNEL_ID, LINE_LOGIN_CHANNEL_SECRET, LINE_LOGIN_CALLBACK_URL, TOKEN_EXPIRE_SECONDS=2592000)
- [ ] T003 [P] 設定前端環境變數檔 frontend/.env (VITE_API_URL)
- [ ] T004 驗證資料庫連線設定 backend/app/Config/Database.php

---

## Phase 2: Foundational (阻塞性前置條件)

**Purpose**: 所有 User Story 都依賴的核心基礎設施

**⚠️ CRITICAL**: 此階段完成前,任何 User Story 都無法開始

- [ ] T005 建立資料庫遷移檔 backend/app/Database/Migrations/2025110100_create_line_login_tables.php (建立 users, user_tokens, video_library, playlists, playlist_items, guest_sessions 六個表)
- [ ] T006 執行資料庫遷移 `php spark migrate`
- [ ] T007 [P] 建立 User Model in backend/app/Models/UserModel.php (包含 findByLineUserId(), restoreUser() 方法)
- [ ] T008 [P] 建立 UserToken Model in backend/app/Models/UserTokenModel.php (包含 findByAccessToken(), cleanupExpired(), revokeAllUserTokens() 方法,token 記錄需包含 user_agent 與 ip_address 欄位以追蹤裝置)
- [ ] T009 [P] 建立 VideoLibrary Model in backend/app/Models/VideoLibraryModel.php (包含 getUserLibrary(), isVideoInLibrary() 方法)
- [ ] T010 [P] 建立 Playlist Model in backend/app/Models/PlaylistModel.php (包含 getUserPlaylists() 方法)
- [ ] T011 [P] 建立 PlaylistItem Model in backend/app/Models/PlaylistItemModel.php (包含 getPlaylistItems(), reorderItems() 方法)
- [ ] T012 [P] 建立 GuestSession Model in backend/app/Models/GuestSessionModel.php (包含 findBySessionId(), cleanupExpired(), saveHistory() 方法)
- [ ] T013 建立 AuthFilter in backend/app/Filters/AuthFilter.php (驗證 HTTP-only cookie 中的 access_token)
- [ ] T014 註冊 AuthFilter 到 backend/app/Config/Filters.php
- [ ] T015 [P] 設定前端 Axios 全域配置 frontend/src/services/axios.js (withCredentials: true, response interceptor 處理 401)
- [ ] T016 [P] 建立前端 Auth Store in frontend/src/stores/auth.js (state: user, isAuthenticated, isLoading; actions: checkAuth, login, logout; getters: isGuest, userDisplayName, userAvatar)

**Checkpoint**: 基礎設施就緒 - User Story 實作現在可以平行開始

---

## Phase 3: User Story 1 - 訪客使用播放器 (Priority: P1) 🎯 MVP

**Goal**: 未登入訪客可直接使用播放器基本功能,無需會員系統

**Independent Test**: 訪問首頁不登入,貼上 YouTube 網址,驗證可正常播放且循環

**Story Dependencies**: 無 (完全獨立)

### Implementation for User Story 1

**注意**: 此 User Story 主要為權限控制層面的調整,確保訪客不需登入即可使用播放器

- [ ] T017 [US1] 驗證現有播放器元件 frontend/src/components/Player.vue 不需認證即可存取
- [ ] T018 [US1] 驗證首頁路由 frontend/src/router/index.js 設置 meta: { requiresAuth: false }
- [ ] T019 [US1] 確保訪客狀態下導航選單隱藏「影片庫」和「播放清單」選項 (修改 frontend/src/components/Navigation.vue 或類似元件)
- [ ] T020 [US1] 實作訪客播放歷史本地儲存功能 (LocalStorage) 於 frontend/src/services/guestHistory.js
- [ ] T021 [US1] 整合播放器與訪客歷史記錄服務 (播放時自動記錄到 LocalStorage)

**Checkpoint**: 訪客可完整使用播放器,登入前後 UI 權限正確控制

---

## Phase 4: User Story 2 - 會員透過 LINE 登入 (Priority: P2)

**Goal**: 訪客可透過 LINE Login 按鈕完成認證,成為會員

**Independent Test**: 點擊登入按鈕,完成 LINE OAuth,驗證登入後右上角顯示使用者資訊且導航選單顯示進階功能

**Story Dependencies**: 依賴 Phase 2 (Foundational) 完成,US1 為邏輯前置但不阻塞實作

### Implementation for User Story 2

- [x] T022 [P] [US2] 建立 Auth Controller in backend/app/Controllers/Auth.php (實作 lineLogin(), lineCallback(), getCurrentUser(), logout(), refreshToken() 方法)
- [x] T023 [P] [US2] 實作 LINE OAuth authorize 流程 in Auth::lineLogin() (產生 state, 重定向到 LINE)
- [x] T024 [US2] 實作 LINE OAuth callback 處理 in Auth::lineCallback() (驗證 state, 交換 code 換 token, 建立/更新會員, 產生新 token 並記錄裝置資訊 User-Agent/IP, 設置 HTTP-only cookie)
- [x] T025 [US2] 實作取得當前會員資訊 API in Auth::getCurrentUser()
- [x] T026 [US2] 實作登出功能 in Auth::logout() (刪除 token, 清除 cookie)
- [x] T027 [US2] 實作 Token 更新功能 in Auth::refreshToken() (使用 refresh token 更新 access token)
- [x] T028 [US2] 設定認證相關路由 in backend/app/Config/Routes.php (GET /api/auth/line/login, GET /api/auth/line/callback, GET /api/auth/user, POST /api/auth/logout, POST /api/auth/refresh)
- [x] T029 [P] [US2] 建立前端登入按鈕元件 frontend/src/components/auth/LoginButton.vue
- [x] T030 [P] [US2] 建立前端使用者選單元件 frontend/src/components/auth/UserMenu.vue (顯示頭像、名稱、登出按鈕)
- [ ] T031 [P] [US2] 建立 Toast 提示元件 frontend/src/components/common/Toast.vue (用於錯誤訊息和狀態提示)
- [x] T032 [US2] 整合登入按鈕到導航列 (未登入時顯示)
- [x] T033 [US2] 整合使用者選單到導航列 (已登入時顯示)
- [x] T034 [US2] 實作路由守衛 in frontend/src/router/index.js (beforeEach: 檢查 requiresAuth, 自動重定向)
- [x] T035 [US2] 在 App.vue onMounted 中呼叫 authStore.checkAuth() 初始化認證狀態
- [x] T036 [US2] 實作訪客資料遷移 API in backend/app/Controllers/Auth.php::migrateGuestData() (接收 LocalStorage 資料,批次寫入 video_library)
- [x] T037 [US2] 實作前端登入成功後自動觸發資料遷移 (檢查 localStorage['guest_history'], 呼叫 POST /api/guest-data/migrate)
- [x] T038 [US2] 處理登入錯誤情境 (使用者取消授權、OAuth 失敗、網路錯誤,顯示友善錯誤訊息)
- [x] T039 [US2] 實作會話逾時處理 (Token 過期時自動登出,顯示提示訊息)

**Checkpoint**: 會員可完整登入登出,訪客資料成功遷移,錯誤處理完善

---

## Phase 5: User Story 3 - 會員管理影片庫與播放清單 (Priority: P3)

**Goal**: 已登入會員可建立和管理個人影片庫與播放清單,不同會員資料完全隔離

**Independent Test**: 登入後訪問影片庫和播放清單頁面,執行新增/編輯/刪除操作,登入另一個帳號驗證資料隔離

**Story Dependencies**: 依賴 US2 (登入功能) 完成

### Implementation for User Story 3

#### 影片庫功能

- [x] T040 [P] [US3] 建立 VideoLibrary Controller in backend/app/Controllers/VideoLibrary.php (實作 index(), add(), remove() 方法)
- [x] T041 [US3] 實作取得影片庫 API in VideoLibrary::index() (支援分頁, 驗證 user_id)
- [x] T042 [US3] 實作新增影片到影片庫 API in VideoLibrary::add() (驗證 user_id, 檢查 10000 影片上限, 檢查重複, 抓取影片資訊)
- [x] T043 [US3] 實作移除影片 API in VideoLibrary::remove() (驗證 user_id, 權限檢查)
- [x] T044 [US3] 設定影片庫路由 in backend/app/Config/Routes.php (GET /api/video-library, POST /api/video-library, DELETE /api/video-library/:videoId, filter: 'auth')
- [x] T045 [P] [US3] 建立前端影片庫頁面 frontend/src/views/VideoLibrary.vue (顯示影片列表, 支援分頁)
- [x] T046 [P] [US3] 建立影片卡片元件 frontend/src/components/library/VideoCard.vue (顯示縮圖、標題、移除按鈕)
- [x] T047 [US3] 實作新增影片到影片庫功能 (從播放器或手動輸入)
- [x] T048 [US3] 實作影片庫路由 in frontend/src/router/index.js (path: '/library', meta: { requiresAuth: true })

#### 播放清單功能

- [x] T049 [P] [US3] 建立 Playlists Controller in backend/app/Controllers/Playlists.php (實作 index(), create(), show(), update(), delete(), addItem(), removeItem(), reorder() 方法)
- [x] T050 [US3] 實作取得所有播放清單 API in Playlists::index() (驗證 user_id)
- [x] T051 [US3] 實作建立播放清單 API in Playlists::create() (驗證 user_id, 檢查名稱重複)
- [x] T052 [US3] 實作取得播放清單詳情 API in Playlists::show() (驗證 user_id, 包含項目)
- [x] T053 [US3] 實作更新播放清單 API in Playlists::update() (驗證 user_id, 權限檢查)
- [x] T054 [US3] 實作刪除播放清單 API in Playlists::delete() (驗證 user_id, CASCADE 刪除項目)
- [x] T055 [US3] 實作新增影片到播放清單 API in Playlists::addItem() (驗證 user_id, 檢查會員總影片數上限 10000, 檢查重複, 自動設置 position)
- [x] T056 [US3] 實作移除播放清單項目 API in Playlists::removeItem() (驗證 user_id, 調整 position)
- [x] T057 [US3] 實作重新排序播放清單 API in Playlists::reorder() (驗證 user_id, 批次更新 position)
- [x] T058 [US3] 設定播放清單路由 in backend/app/Config/Routes.php (GET /api/playlists, POST /api/playlists, GET /api/playlists/:id, PUT /api/playlists/:id, DELETE /api/playlists/:id, POST /api/playlists/:id/items, DELETE /api/playlists/:id/items/:itemId, PUT /api/playlists/:id/reorder, filter: 'auth')
- [x] T059 [P] [US3] 建立前端播放清單頁面 frontend/src/views/Playlists.vue (顯示所有播放清單)
- [x] T060 [P] [US3] 建立播放清單詳情頁面 frontend/src/views/PlaylistDetail.vue (顯示項目, 支援拖曳排序)
- [x] T061 [P] [US3] 建立播放清單卡片元件 frontend/src/components/playlists/PlaylistCard.vue
- [x] T062 [P] [US3] 建立播放清單項目元件 frontend/src/components/playlists/PlaylistItem.vue
- [x] T063 [P] [US3] 建立建立播放清單對話框元件 frontend/src/components/playlists/CreatePlaylistDialog.vue
- [x] T064 [US3] 實作播放清單路由 in frontend/src/router/index.js (path: '/playlists', '/playlists/:id', meta: { requiresAuth: true })
- [x] T065 [US3] 整合播放清單拖曳排序功能 (使用 HTML5 Drag & Drop API)
- [x] T066 [US3] 實作從播放器新增到播放清單功能 (快捷操作)

#### 資料隔離驗證

- [x] T067 [US3] 驗證所有 API 端點都強制檢查 user_id 匹配 (AuthFilter 提供的 userId)
- [x] T068 [US3] 測試不同會員間無法存取彼此的影片庫和播放清單 (手動測試或自動化測試)

**Checkpoint**: 所有 User Story 獨立運作,資料隔離完善,功能完整

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: 跨 User Story 的改進與最佳化

- [x] T069 [P] 實作軟刪除定時清理任務 backend/app/Commands/CleanupDeletedUsers.php (刪除 deleted_at < NOW() - 30 days 的記錄)
- [x] T070 [P] 設定 Cron job 或 Task Scheduler 執行清理任務 (每日執行)
- [x] T071-A 實作軟刪除檢測邏輯 in Auth::lineCallback() (登入時檢查 LINE User ID 是否存在於 users 表且 deleted_at IS NOT NULL 且未超過 30 天)
- [x] T071-B 實作帳號恢復方法 in UserModel::restoreUser() (將 deleted_at 設為 NULL, 更新 updated_at, 記錄恢復日誌)
- [x] T071-C 實作前端帳號恢復提示 UI (登入成功後檢查回應中的 restored 標記,顯示 Toast 訊息「歡迎回來!您的帳號資料已完全恢復」)
- [x] T072 [P] 前端 Loading 狀態優化 (所有非同步操作顯示 spinner 或 skeleton)
- [x] T073 [P] 錯誤訊息國際化 (zh-TW) 與友善化
- [x] T074 [P] 前端響應式設計調整 (確保行動裝置可用性)
- [x] T075 [P] 安全性檢查 (CSRF token, XSS 防護, SQL Injection 防護)
- [x] T076 [P] 效能優化 (資料庫查詢索引, N+1 query 問題排查)
- [x] T077 [P] 日誌記錄完善 (登入/登出事件, API 錯誤)
- [x] T078 執行 quickstart.md 驗證流程 (手動測試所有功能)
- [x] T079 文件更新 (README.md, API 文件)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 無相依性 - 立即開始
- **Foundational (Phase 2)**: 依賴 Setup 完成 - **阻塞所有 User Story**
- **User Stories (Phase 3-5)**: 全部依賴 Foundational 完成
  - US1, US2, US3 可平行執行 (若有足夠人力)
  - 或依優先順序執行 (P1 → P2 → P3)
- **Polish (Phase 6)**: 依賴所有 User Story 完成

### User Story Dependencies

- **User Story 1 (P1)**: Foundational 完成後可開始 - 無其他 Story 相依
- **User Story 2 (P2)**: Foundational 完成後可開始 - 邏輯上建議在 US1 後,但可平行
- **User Story 3 (P3)**: **依賴 US2 完成** (需要會員登入功能) - 建議順序執行

### Within Each User Story

- Models 先於 Services
- Services 先於 Controllers
- Backend API 先於 Frontend UI
- 核心實作先於整合
- Story 完成後再移至下一個優先級

### Parallel Opportunities

**Phase 2 (Foundational)**:
- T007-T012 (6 個 Models) 可平行
- T015, T016 (前端設定) 可平行

**User Story 2**:
- T022, T023 可平行 (不同功能方法)
- T029, T030, T031 (前端元件) 可平行

**User Story 3**:
- T040, T049 (兩個 Controllers) 可平行
- T045, T046 (影片庫前端) 可平行
- T059-T063 (播放清單前端元件) 可平行

**Phase 6 (Polish)**:
- T069-T077 (除 T078 外) 大部分可平行

---

## Parallel Example: User Story 3 (影片庫與播放清單)

```bash
# 平行建立兩個 Controller:
Task: "建立 VideoLibrary Controller in backend/app/Controllers/VideoLibrary.php"
Task: "建立 Playlists Controller in backend/app/Controllers/Playlists.php"

# 平行建立前端元件:
Task: "建立影片庫頁面 frontend/src/views/VideoLibrary.vue"
Task: "建立播放清單頁面 frontend/src/views/Playlists.vue"
Task: "建立播放清單卡片元件 frontend/src/components/playlists/PlaylistCard.vue"
Task: "建立播放清單項目元件 frontend/src/components/playlists/PlaylistItem.vue"
```

---

## Implementation Strategy

### MVP First (僅 User Story 1 + 2)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (**CRITICAL**)
3. Complete Phase 3: User Story 1 (訪客播放器)
4. Complete Phase 4: User Story 2 (LINE 登入)
5. **STOP and VALIDATE**: 測試訪客和會員兩種狀態
6. 可選:部署/展示 MVP

**MVP 範圍**: 訪客可使用播放器 + 會員可透過 LINE 登入 + 資料遷移

### Incremental Delivery

1. Setup + Foundational → 基礎就緒
2. Add User Story 1 → 測試 → 部署 (訪客功能)
3. Add User Story 2 → 測試 → 部署 (會員登入)
4. Add User Story 3 → 測試 → 部署 (影片庫與播放清單)
5. 每個 Story 獨立增加價值,不破壞先前功能

### Parallel Team Strategy

若有多位開發者:

1. 團隊共同完成 Setup + Foundational
2. Foundational 完成後:
   - Developer A: User Story 1 (前端權限控制)
   - Developer B: User Story 2 (LINE Login 後端 + 前端)
   - Developer C: 可預先開始 US3 的 Model/Controller (依賴 US2 完成後整合)
3. Stories 獨立完成並整合

---

## Summary

- **Total Tasks**: 81
- **MVP Tasks (US1 + US2)**: T001-T039 (39 tasks)
- **Full Feature Tasks (US1-US3)**: T001-T068 (68 tasks)
- **Parallel Opportunities**: 約 30% 任務可平行 (標記 [P])
- **Suggested MVP Scope**: Phase 1-4 (Setup + Foundational + US1 + US2)
- **Critical Path**: Phase 2 (Foundational) 必須完整完成,阻塞所有 User Story

**Independent Test Criteria**:
- **US1**: 訪客貼上 YouTube 網址可播放,未登入不顯示進階功能
- **US2**: LINE 登入成功,顯示使用者資訊,訪客資料遷移成功
- **US3**: 會員可管理影片庫和播放清單,不同會員資料隔離

---

## Notes

- [P] 任務 = 不同檔案,無相依性,可平行執行
- [Story] 標籤映射任務到特定 User Story,確保可追溯性
- 每個 User Story 應可獨立完成與測試
- 每個任務或邏輯群組完成後提交 (commit)
- 可在任何 Checkpoint 停止以獨立驗證 Story
- 避免:模糊任務、同檔案衝突、破壞獨立性的跨 Story 相依

**憲章遵循**:
- ✅ 零新增套件 (完全使用現有依賴)
- ✅ 最小化變更 (僅新增必要檔案,修改 3 個檔案)
- ✅ 不破壞現有功能 (所有任務為新增,不重構)
