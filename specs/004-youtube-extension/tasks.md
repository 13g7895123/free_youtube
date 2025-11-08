# Tasks: YouTube 瀏覽器擴充程式

**Feature**: 004-youtube-extension
**Generated**: 2025-11-08
**Total User Stories**: 5 (2 P1, 2 P2, 1 P3)

---

## Phase 1: Setup (專案初始化)

- [X] [T001] [--] [--] 建立專案目錄結構 `browser-extension/` 並初始化 npm 專案 (`package.json`)
- [X] [T002] [--] [--] 安裝核心依賴：`webextension-polyfill` 與開發依賴（jest, @types/chrome, @types/firefox-webext-browser, web-ext, webpack, webpack-cli, copy-webpack-plugin）於 `browser-extension/package.json`
- [X] [T003] [--] [--] 建立目錄結構：`src/{popup,services,utils,background}`, `icons/`, `tests/{unit,integration}/` 於 `browser-extension/`
- [X] [T004] [--] [--] 設定 `.gitignore` 將 `.env`, `node_modules/`, `dist/` 加入忽略清單於 `browser-extension/.gitignore`
- [X] [T005] [--] [--] 建立環境變數範本 `.env.example` 包含 `YOUTUBE_API_KEY`, `LINE_CHANNEL_ID`, `LINE_REDIRECT_URI` 於 `browser-extension/.env.example`
- [X] [T006] [--] [--] 設定 Jest 測試環境：建立 `jest.config.js` 與 `tests/setup.js` 於 `browser-extension/`
- [X] [T007] [--] [--] 建立構建腳本於 `package.json`：`build:chrome`, `build:firefox`, `test`, `dev:chrome`, `dev:firefox`

---

## Phase 2: Foundational (阻塞性前置任務)

- [X] [T008] [--] [--] 建立 Chrome Manifest V3 檔案 `manifest-chrome.json` 包含 permissions (storage, tabs, identity), host_permissions (youtube.com, googleapis.com), action, background service_worker
- [X] [T009] [--] [--] 建立 Firefox Manifest V2 檔案 `manifest-firefox.json` 包含相容設定
- [X] [T010] [--] [--] 建立 Popup HTML 骨架 `src/popup/popup.html` 包含登入區塊與主功能區塊
- [X] [T011] [--] [--] 建立 Popup CSS 基本樣式 `src/popup/popup.css` 包含按鈕、訊息顯示樣式
- [X] [T012] [--] [--] 建立 Background Service Worker 骨架 `src/background/background.js` 監聽安裝與執行時事件
- [X] [T013] [--] [--] 實作 Token 管理工具 `src/utils/token-manager.js` 包含 AES-GCM 加密/解密、Token 儲存/讀取/驗證/更新邏輯
- [X] [T014] [--] [--] 實作 YouTube URL 解析器 `src/utils/url-parser.js` 支援 `youtube.com/watch`, `youtu.be`, `m.youtube.com` 格式
- [X] [T015] [--] [--] 實作錯誤處理與重試機制 `src/utils/retry.js` 使用指數退避策略（最多 3 次）
- [X] [T016] [--] [--] 建立配置檔 `src/utils/config.js` 載入環境變數 (YOUTUBE_API_KEY, LINE_CHANNEL_ID, LINE_REDIRECT_URI, BACKEND_API_URL)
- [X] [T017] [--] [--] 撰寫單元測試：URL 解析器測試 `tests/unit/url-parser.test.js` 涵蓋所有 URL 格式與邊緣案例
- [X] [T018] [--] [--] 撰寫單元測試：Token 管理測試 `tests/unit/token-manager.test.js` 涵蓋加密/解密/過期檢查

---

## Phase 3: User Story 1 - LINE 登入驗證 (P1) - MVP

**User Story**: 作為一個使用者，我希望能透過 LINE 登入，以便使用擴充程式的功能

- [X] [T019] [P1] [US1] 實作 LINE OAuth 認證服務 `src/services/auth.js`：使用 `browser.identity.launchWebAuthFlow` 開啟 LINE 授權頁面
- [X] [T020] [P1] [US1] 實作 Authorization Code 擷取邏輯於 `src/services/auth.js`：從 redirect URL 解析 code 參數
- [X] [T021] [P1] [US1] 實作後端 API 通訊層 `src/services/api.js`：POST `/auth/line/callback` 交換 code 換取 access token 與 refresh token
- [X] [T022] [P1] [US1] 實作 Token 儲存邏輯於 `src/services/auth.js`：將 access token 與 refresh token 加密後儲存至 `browser.storage.local` (key: `auth_data`)
- [X] [T023] [P1] [US1] 實作登入狀態檢查於 `src/popup/popup.js`：初始化時讀取 `auth_data` 判斷是否已登入
- [X] [T024] [P1] [US1] 實作登入按鈕事件處理於 `src/popup/popup.js`：點擊「LINE 登入」觸發 OAuth 流程
- [X] [T025] [P1] [US1] 實作使用者資訊顯示於 `src/popup/popup.js`：登入成功後顯示 displayName 與 profilePictureUrl
- [X] [T026] [P1] [US1] 實作自動建立新會員邏輯於 `src/services/auth.js`：當後端回傳新使用者標記時，顯示歡迎訊息
- [X] [T027] [P1] [US1] 實作 Access Token 自動更新機制於 `src/utils/token-manager.js`：檢查過期時間，自動呼叫 POST `/auth/refresh` 更新 token
- [X] [T028] [P1] [US1] 實作登出功能於 `src/popup/popup.js`：呼叫 POST `/auth/logout` 清除 refresh token 並移除本地 `auth_data`
- [X] [T029] [P1] [US1] 撰寫整合測試：LINE OAuth 流程測試 `tests/integration/auth.test.js` 模擬完整登入流程
- [ ] [T030] [P1] [US1] 撰寫單元測試：Token 更新邏輯測試 `tests/unit/token-refresh.test.js` 涵蓋過期與更新場景
- [ ] [T031] [P1] [US1] 手動測試：在 Chrome 與 Firefox 上測試 LINE OAuth 登入與登出流程

**Acceptance Criteria**:
- [ ] 使用者點擊「LINE 登入」可成功完成 OAuth 流程
- [ ] 登入成功後顯示使用者名稱與頭像
- [ ] 新使用者自動建立帳號並顯示歡迎訊息
- [ ] Access token 過期時自動更新
- [ ] 登出後清除本地認證資料

---

## Phase 4: User Story 2 - 加入播放庫 (P1)

**User Story**: 作為一個已登入的使用者，當我在 YouTube 頁面上點擊擴充程式圖示並選擇「加入播放庫」，系統應能正確解析當前影片 URL，取得影片資訊（標題、縮圖、時長），並呼叫後端 API 將影片加入我的播放庫

- [X] [T032] [P1] [US2] 實作當前分頁 URL 讀取於 `src/popup/popup.js`：使用 `browser.tabs.query` 取得 activeTab 的 URL
- [X] [T033] [P1] [US2] 實作影片 ID 解析於 `src/popup/popup.js`：呼叫 `url-parser.js` 解析 YouTube URL
- [X] [T034] [P1] [US2] 實作 YouTube API 通訊層 `src/services/youtube.js`：GET `https://www.googleapis.com/youtube/v3/videos` 取得影片資訊 (snippet, contentDetails)
- [X] [T035] [P1] [US2] 實作影片資訊擷取邏輯於 `src/services/youtube.js`：解析 API 回應，提取 title, thumbnailUrl (medium), duration (ISO 8601)
- [X] [T036] [P1] [US2] 實作 YouTube API 配額不足降級策略於 `src/services/youtube.js`：HTTP 403 時僅儲存影片 ID，使用標準縮圖 URL 格式
- [X] [T037] [P1] [US2] 實作加入播放庫 API 呼叫於 `src/services/api.js`：POST `/library/videos` 傳送 youtubeVideoId, title, thumbnailUrl, duration
- [X] [T038] [P1] [US2] 實作「加入播放庫」按鈕事件處理於 `src/popup/popup.js`：點擊後執行完整加入流程
- [X] [T039] [P1] [US2] 實作成功/失敗訊息顯示於 `src/popup/popup.js`：顯示「影片已加入播放庫」或錯誤訊息（如「影片已存在」）
- [X] [T040] [P1] [US2] 實作影片已存在檢測於 `src/services/api.js`：處理 HTTP 409 Conflict 回應
- [X] [T041] [P1] [US2] 實作非 YouTube 頁面處理於 `src/popup/popup.js`：當前頁面非 YouTube 時禁用「加入播放庫」按鈕並顯示提示
- [X] [T042] [P1] [US2] 撰寫單元測試：YouTube API 服務測試 `tests/unit/youtube-service.test.js` 涵蓋成功與配額不足場景
- [ ] [T043] [P1] [US2] 撰寫整合測試：加入播放庫流程測試 `tests/integration/add-to-library.test.js` 模擬完整流程
- [ ] [T044] [P1] [US2] 手動測試：在實際 YouTube 頁面測試加入播放庫功能（包含重複加入、非 YouTube 頁面場景）

**Acceptance Criteria**:
- [ ] 在 YouTube 影片頁面點擊「加入播放庫」成功加入影片
- [ ] 顯示影片標題、縮圖、時長資訊
- [ ] 重複加入時顯示「影片已存在」提示
- [ ] 非 YouTube 頁面時按鈕禁用
- [ ] YouTube API 配額不足時仍能加入影片（降級模式）

---

## Phase 5: User Story 3 - 加入播放清單（預設模式） (P2)

**User Story**: 作為一個已登入的使用者，當我在設定中選擇「預設播放清單模式」並指定一個播放清單後，點擊「加入播放清單」按鈕應直接將影片加入該預設播放清單，無需選擇

- [X] [T045] [P2] [US3] 實作播放清單列表查詢於 `src/services/api.js`：GET `/playlists` 取得使用者的所有播放清單
- [X] [T046] [P2] [US3] 實作播放清單快取機制於 `src/utils/cache.js`：將播放清單快取至 `browser.storage.local` (key: `cache_playlists`), 有效期 5 分鐘
- [X] [T047] [P2] [US3] 實作使用者設定讀取於 `src/popup/popup.js`：從 `browser.storage.local` 讀取 `user_settings`（playlistMode, defaultPlaylistId）
- [X] [T048] [P2] [US3] 實作預設模式邏輯於 `src/popup/popup.js`：當 playlistMode 為 'default' 且 defaultPlaylistId 存在時，直接加入該播放清單
- [X] [T049] [P2] [US3] 實作加入播放清單 API 呼叫於 `src/services/api.js`：POST `/playlists/{playlistId}/videos` 傳送影片資訊
- [X] [T050] [P2] [US3] 實作「加入播放清單」按鈕事件處理於 `src/popup/popup.js`：根據 playlistMode 決定執行預設或自訂模式
- [ ] [T051] [P2] [US3] 實作播放清單不存在處理於 `src/popup/popup.js`：當 defaultPlaylistId 指向的播放清單已刪除時，提示使用者重新設定
- [ ] [T052] [P2] [US3] 實作成功訊息顯示於 `src/popup/popup.js`：顯示「影片已加入 [播放清單名稱]」
- [ ] [T053] [P2] [US3] 撰寫單元測試：播放清單服務測試 `tests/unit/playlist-service.test.js` 涵蓋查詢與快取邏輯
- [ ] [T054] [P2] [US3] 撰寫整合測試：預設模式加入播放清單測試 `tests/integration/add-to-default-playlist.test.js`
- [ ] [T055] [P2] [US3] 手動測試：設定預設播放清單後測試加入功能（包含播放清單刪除場景）

**Acceptance Criteria**:
- [ ] 設定預設播放清單後，點擊「加入播放清單」直接加入該清單
- [ ] 顯示成功訊息包含播放清單名稱
- [ ] 預設播放清單已刪除時提示使用者重新設定
- [ ] 播放清單快取正常運作，5 分鐘內不重複查詢

---

## Phase 6: User Story 4 - 加入播放清單（自訂模式） (P2)

**User Story**: 作為一個已登入的使用者，當我在設定中選擇「自訂播放清單模式」，點擊「加入播放清單」按鈕應顯示我的所有播放清單列表，讓我選擇要加入的目標播放清單

- [ ] [T056] [P2] [US4] 實作播放清單選擇器 UI 於 `src/popup/popup.html`：新增 modal 或 dropdown 顯示播放清單列表
- [ ] [T057] [P2] [US4] 實作播放清單選擇器 CSS 於 `src/popup/popup.css`：設計清單顯示樣式
- [ ] [T058] [P2] [US4] 實作自訂模式邏輯於 `src/popup/popup.js`：當 playlistMode 為 'custom' 時，顯示播放清單選擇器
- [ ] [T059] [P2] [US4] 實作播放清單渲染於 `src/popup/popup.js`：從快取或 API 取得播放清單，動態生成選項列表
- [ ] [T060] [P2] [US4] 實作播放清單選擇事件處理於 `src/popup/popup.js`：使用者選擇播放清單後，呼叫 POST `/playlists/{playlistId}/videos`
- [ ] [T061] [P2] [US4] 實作空播放清單處理於 `src/popup/popup.js`：若使用者無任何播放清單，顯示「請先在後端建立播放清單」提示
- [ ] [T062] [P2] [US4] 實作取消選擇功能於 `src/popup/popup.js`：提供關閉選擇器的按鈕
- [ ] [T063] [P2] [US4] 撰寫單元測試：播放清單選擇器測試 `tests/unit/playlist-selector.test.js` 涵蓋渲染與選擇邏輯
- [ ] [T064] [P2] [US4] 撰寫整合測試：自訂模式加入播放清單測試 `tests/integration/add-to-custom-playlist.test.js`
- [ ] [T065] [P2] [US4] 手動測試：測試自訂模式選擇不同播放清單加入影片（包含空播放清單場景）

**Acceptance Criteria**:
- [ ] 自訂模式下點擊「加入播放清單」顯示播放清單選擇器
- [ ] 選擇器顯示所有播放清單（名稱、影片數量）
- [ ] 選擇播放清單後成功加入影片
- [ ] 無播放清單時顯示提示訊息
- [ ] 可取消選擇並關閉選擇器

---

## Phase 7: User Story 5 - 設定預設播放清單 (P3)

**User Story**: 作為一個已登入的使用者，我希望能在擴充程式的設定頁面中選擇播放清單模式（預設/自訂），並在預設模式下指定一個預設播放清單

- [X] [T066] [P3] [US5] 建立設定頁面 HTML `src/settings/settings.html`：包含模式選擇（radio buttons）與播放清單下拉選單
- [X] [T067] [P3] [US5] 建立設定頁面 CSS `src/settings/settings.css`：設計設定介面樣式
- [X] [T068] [P3] [US5] 實作設定頁面 JavaScript `src/settings/settings.js`：初始化時讀取當前設定並渲染
- [X] [T069] [P3] [US5] 實作模式切換邏輯於 `src/settings/settings.js`：選擇「預設模式」時顯示播放清單選擇器，「自訂模式」時隱藏
- [X] [T070] [P3] [US5] 實作播放清單下拉選單渲染於 `src/settings/settings.js`：從快取或 API 取得播放清單並填充 `<select>`
- [X] [T071] [P3] [US5] 實作設定儲存邏輯於 `src/settings/settings.js`：將 playlistMode 與 defaultPlaylistId 儲存至 `browser.storage.local` (key: `user_settings`)
- [X] [T072] [P3] [US5] 實作設定驗證於 `src/settings/settings.js`：預設模式下必須選擇播放清單，否則顯示錯誤
- [ ] [T073] [P3] [US5] 實作設定頁面入口於 `src/popup/popup.html`：新增「設定」按鈕開啟 settings.html
- [ ] [T074] [P3] [US5] 實作設定頁面導航於 `src/popup/popup.js`：使用 `browser.runtime.openOptionsPage` 或 `browser.tabs.create` 開啟設定頁
- [ ] [T075] [P3] [US5] 在 manifest 中註冊設定頁面：新增 `options_ui` 或 `options_page` 指向 `src/settings/settings.html`
- [ ] [T076] [P3] [US5] 撰寫單元測試：設定頁面邏輯測試 `tests/unit/settings.test.js` 涵蓋模式切換與儲存
- [ ] [T077] [P3] [US5] 手動測試：測試設定頁面切換模式、選擇播放清單、儲存設定後於 Popup 驗證生效

**Acceptance Criteria**:
- [ ] 設定頁面可切換「預設模式」與「自訂模式」
- [ ] 預設模式下可選擇預設播放清單
- [ ] 儲存設定後，Popup 行為符合設定
- [ ] 設定驗證正常運作（預設模式未選播放清單時阻止儲存）

---

## Final Phase: Polish & Cross-cutting Concerns

- [ ] [T078] [--] [--] 實作國際化（i18n）支援於 `_locales/` 目錄：新增 zh_TW, en 語言包
- [ ] [T079] [--] [--] 實作錯誤訊息本地化於所有 UI 元件
- [ ] [T080] [--] [--] 實作圖示檔案於 `icons/`：準備 16x16, 48x48, 128x128 PNG 圖示
- [ ] [T081] [--] [--] 實作深色主題支援於 `src/popup/popup.css` 與 `src/settings/settings.css`（使用 `prefers-color-scheme` media query）
- [ ] [T082] [--] [--] 實作無障礙功能：為所有互動元素新增 ARIA 標籤與鍵盤導航支援
- [ ] [T083] [--] [--] 效能優化：檢查並優化 API 呼叫頻率、減少不必要的 DOM 操作
- [ ] [T084] [--] [--] 安全性檢查：審查所有 Token 處理邏輯，確保無明文儲存或傳輸
- [ ] [T085] [--] [--] 撰寫端到端測試：完整使用者流程測試 `tests/e2e/full-workflow.test.js`（登入 → 加入播放庫 → 加入播放清單 → 設定）
- [ ] [T086] [--] [--] 瀏覽器相容性測試：在 Chrome 與 Firefox 最新版本與前兩個版本測試所有功能
- [ ] [T087] [--] [--] 撰寫 README.md：包含安裝說明、開發指南、測試方法
- [ ] [T088] [--] [--] 建立發布流程文件：說明如何打包擴充程式並上傳至 Chrome Web Store 與 Firefox Add-ons
- [ ] [T089] [--] [--] 程式碼審查：確保所有程式碼符合 ESLint 規範，移除 console.log 與註解程式碼
- [ ] [T090] [--] [--] 最終測試：執行完整測試套件 `npm test` 確保所有測試通過

---

## Parallel Execution Opportunities

以下任務可並行執行（無依賴關係）：

**Setup 階段**:
- T001, T002, T003 可同時執行（目錄建立、npm 初始化、依賴安裝）
- T004, T005, T006, T007 可同時執行（設定檔案建立）

**Foundational 階段**:
- T008, T009 可同時執行（Manifest 檔案）
- T010, T011 可同時執行（Popup HTML/CSS）
- T013, T014, T015, T016 可同時執行（工具類別實作）
- T017, T018 可同時執行（單元測試）

**US1 階段**:
- T019, T020, T021 需依序執行（OAuth 流程）
- T029, T030, T031 可同時執行（測試）

**US2 階段**:
- T034, T035, T036 需依序執行（YouTube API）
- T042, T043 可同時執行（測試）

**US3, US4, US5 階段**:
- US3 (T045-T055), US4 (T056-T065), US5 (T066-T077) **可完全並行執行**（不同功能模組）

**Final 階段**:
- T078, T079, T080, T081, T082 可同時執行（UI 改善）
- T087, T088 可同時執行（文件撰寫）

---

## Task Statistics

- **Total Tasks**: 90
- **P1 Tasks**: 26 (US1: 13, US2: 13)
- **P2 Tasks**: 21 (US3: 11, US4: 10)
- **P3 Tasks**: 12 (US5: 12)
- **Setup/Infrastructure Tasks**: 31
- **Estimated Parallel Batches**: 8-10（可將總執行時間減少約 40%）

---

## Notes

1. **MVP 範圍**: Phase 3 (US1) 與 Phase 4 (US2) 即可達成 MVP（登入 + 加入播放庫）
2. **測試覆蓋率目標**: 所有核心邏輯需達 80% 以上單元測試覆蓋率
3. **瀏覽器支援**: Chrome 88+, Firefox 78+
4. **API 金鑰管理**: 開發階段使用 `.env` 檔案，生產環境需整合至後端服務
5. **後端依賴**: 本任務清單假設後端 API 已實作完成並可用於整合測試
