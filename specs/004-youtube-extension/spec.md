# Feature Specification: YouTube 瀏覽器擴充程式

**Feature Branch**: `004-youtube-extension`
**Created**: 2025-11-08
**Status**: Draft
**Input**: User description: "核心目標為為firefix與chrome各建立一個extension，一樣用line登入，access token與refresh token的規則一樣，登入後，可以在youtube頁面使用該extension，會自動分析當前網址，並把該影片加入影片庫中"

## User Scenarios & Testing

### User Story 1 - LINE 登入驗證 (Priority: P1)

使用者首次安裝擴充程式後，點擊擴充程式圖示，會看到一個 LINE 登入按鈕。點擊後透過 LINE OAuth 2.0 進行身份驗證，驗證成功後取得 access token 與 refresh token，並確認系統中是否存在該會員資料。

**Why this priority**: 登入是所有功能的基礎，沒有登入就無法使用任何影片管理功能。

**Independent Test**: 可透過安裝擴充程式、點擊 LINE 登入按鈕並完成 OAuth 流程來獨立測試，驗證是否成功取得 token 並顯示登入狀態。

**Acceptance Scenarios**:

1. **Given** 使用者已安裝擴充程式但尚未登入，**When** 點擊擴充程式圖示，**Then** 顯示 LINE 登入按鈕
2. **Given** 使用者點擊 LINE 登入按鈕，**When** 完成 LINE OAuth 驗證流程，**Then** 擴充程式取得 access token 與 refresh token
3. **Given** 使用者已通過 LINE 驗證，**When** 系統檢查會員資料，**Then** 若該 LINE 帳號已在系統註冊則顯示「加入播放庫」與「加入播放清單」按鈕
4. **Given** 使用者已登入且 token 有效，**When** 重新開啟擴充程式，**Then** 自動使用儲存的 token 維持登入狀態
5. **Given** 使用者的 access token 過期，**When** 擴充程式偵測到 token 過期，**Then** 使用 refresh token 自動更新 access token

---

### User Story 2 - 加入播放庫（單一影片） (Priority: P1)

使用者在 YouTube 頁面觀看影片時，開啟擴充程式並點擊「加入播放庫」按鈕，系統會分析當前網址並提取該單一影片的 ID，將影片資訊加入使用者的播放庫中。

**Why this priority**: 這是核心功能之一，讓使用者能快速收藏單一影片到播放庫，滿足基本的影片管理需求。

**Independent Test**: 在任何 YouTube 影片頁面點擊「加入播放庫」按鈕，驗證是否成功將該影片加入播放庫，且不會誤加入播放清單中的其他影片。

**Acceptance Scenarios**:

1. **Given** 使用者已登入且在 YouTube 單一影片頁面（例如 `youtube.com/watch?v=VIDEO_ID`），**When** 點擊「加入播放庫」按鈕，**Then** 系統解析網址並提取 VIDEO_ID，將該影片加入播放庫
2. **Given** 使用者在 YouTube 播放清單頁面（例如 `youtube.com/watch?v=VIDEO_ID&list=PLAYLIST_ID`），**When** 點擊「加入播放庫」按鈕，**Then** 系統僅提取當前播放的 VIDEO_ID，忽略 list 參數
3. **Given** 影片已存在於播放庫中，**When** 使用者再次點擊「加入播放庫」，**Then** 系統提示該影片已在播放庫中，避免重複加入
4. **Given** 使用者點擊「加入播放庫」按鈕，**When** 加入成功，**Then** 顯示成功提示訊息

---

### User Story 3 - 加入播放清單（預設模式） (Priority: P2)

使用者在 YouTube 頁面開啟擴充程式，點擊「加入播放清單」按鈕，系統會將當前影片加入使用者預設的播放清單中。若使用者尚未設定預設播放清單，則自動使用第一個播放清單。

**Why this priority**: 提供快速加入播放清單的便利性，讓使用者無需每次選擇，適合有固定收藏習慣的使用者。

**Independent Test**: 在 YouTube 影片頁面點擊「加入播放清單」按鈕，驗證影片是否成功加入預設播放清單。

**Acceptance Scenarios**:

1. **Given** 使用者已登入且設定了預設播放清單，**When** 點擊「加入播放清單」按鈕，**Then** 影片自動加入預設播放清單
2. **Given** 使用者已登入但未設定預設播放清單，**When** 點擊「加入播放清單」按鈕，**Then** 影片自動加入使用者的第一個播放清單
3. **Given** 使用者已登入但沒有任何播放清單，**When** 開啟擴充程式，**Then** 不顯示「加入播放清單」按鈕
4. **Given** 影片已存在於目標播放清單中，**When** 使用者點擊「加入播放清單」，**Then** 系統提示該影片已在播放清單中

---

### User Story 4 - 加入播放清單（自訂模式） (Priority: P2)

使用者在擴充程式中點擊「加入播放清單」按鈕旁的設定圖示，可切換為「自訂模式」。在自訂模式下，每次點擊「加入播放清單」時，會彈出選單讓使用者選擇要加入的播放清單。

**Why this priority**: 提供彈性的播放清單管理，讓使用者能根據不同影片類型或主題分類收藏。

**Independent Test**: 設定為自訂模式後，點擊「加入播放清單」按鈕，驗證是否彈出播放清單選單並能成功加入選定的播放清單。

**Acceptance Scenarios**:

1. **Given** 使用者點擊「加入播放清單」按鈕旁的設定圖示，**When** 進入設定頁面，**Then** 顯示「預設模式」與「自訂模式」兩個選項
2. **Given** 使用者選擇「自訂模式」，**When** 點擊「加入播放清單」按鈕，**Then** 彈出使用者的所有播放清單供選擇
3. **Given** 使用者在彈出的清單中選擇一個播放清單，**When** 確認選擇，**Then** 影片成功加入該播放清單並顯示成功訊息
4. **Given** 使用者在自訂模式下選擇播放清單，**When** 取消選擇或關閉彈出視窗，**Then** 不執行任何操作

---

### User Story 5 - 設定預設播放清單 (Priority: P3)

使用者在擴充程式的設定頁面中，若選擇「預設模式」，可進一步指定預設播放清單。未來點擊「加入播放清單」時，影片會直接加入該預設播放清單。

**Why this priority**: 提升使用者體驗，讓使用者能自行控制預設行為，而非系統自動選擇第一個播放清單。

**Independent Test**: 在設定頁面選擇預設播放清單後，點擊「加入播放清單」按鈕，驗證影片是否加入所選的預設播放清單。

**Acceptance Scenarios**:

1. **Given** 使用者在設定頁面選擇「預設模式」，**When** 顯示播放清單下拉選單，**Then** 列出使用者的所有播放清單
2. **Given** 使用者選擇一個播放清單作為預設，**When** 儲存設定，**Then** 該播放清單成為預設播放清單
3. **Given** 使用者已設定預設播放清單，**When** 點擊「加入播放清單」按鈕，**Then** 影片加入該預設播放清單
4. **Given** 使用者刪除了預設播放清單，**When** 下次點擊「加入播放清單」，**Then** 系統自動使用第一個播放清單

---

### Edge Cases

- 使用者在非 YouTube 網站開啟擴充程式時，如何處理？
  - 系統應偵測當前網址，若非 YouTube 頁面則顯示提示訊息「請在 YouTube 頁面使用此功能」
- 使用者的 refresh token 過期時，如何處理？
  - 系統應引導使用者重新登入，並清除舊的 token
- 使用者在 YouTube 首頁或搜尋頁面（非影片頁面）開啟擴充程式時，如何處理？
  - 系統應偵測網址格式，若無法提取影片 ID 則顯示提示訊息「請在影片頁面使用此功能」
- 網路連線失敗導致無法加入影片時，如何處理？
  - 系統應顯示錯誤訊息，並提供重試選項
- 使用者同時在多個瀏覽器（Chrome 與 Firefox）登入，token 如何同步？
  - 每個瀏覽器的擴充程式獨立管理 token，使用相同的後端 API，token 不需跨瀏覽器同步
- 使用者刪除了所有播放清單後，介面如何反應？
  - 「加入播放清單」按鈕應隱藏，僅顯示「加入播放庫」按鈕

## Requirements

### Functional Requirements

- **FR-001**: 擴充程式必須支援 Chrome 與 Firefox 兩種瀏覽器
- **FR-002**: 擴充程式必須提供 LINE OAuth 2.0 登入功能
- **FR-003**: 擴充程式必須儲存並管理 access token 與 refresh token，遵循與現有系統相同的 token 管理規則
- **FR-004**: 擴充程式必須在 access token 過期時，自動使用 refresh token 更新 access token
- **FR-005**: 擴充程式必須在 refresh token 過期時，提示使用者重新登入
- **FR-006**: 擴充程式必須在使用者登入後，向後端 API 驗證該 LINE 帳號是否已註冊
- **FR-007**: 擴充程式必須在 YouTube 影片頁面自動偵測並解析當前影片的 URL，提取影片 ID
- **FR-008**: 擴充程式必須在解析 YouTube URL 時，忽略播放清單參數（list），僅提取單一影片 ID
- **FR-009**: 擴充程式必須提供「加入播放庫」按鈕，將當前影片加入使用者的播放庫
- **FR-010**: 擴充程式必須在影片已存在於播放庫或播放清單時，顯示提示訊息避免重複加入
- **FR-011**: 擴充程式必須在使用者沒有任何播放清單時，隱藏「加入播放清單」按鈕
- **FR-012**: 擴充程式必須提供「加入播放清單」按鈕，支援預設模式與自訂模式
- **FR-013**: 擴充程式在預設模式下，必須將影片加入使用者指定的預設播放清單，若未指定則使用第一個播放清單
- **FR-014**: 擴充程式在自訂模式下，必須彈出播放清單選單供使用者選擇
- **FR-015**: 擴充程式必須提供設定介面，讓使用者選擇「預設模式」或「自訂模式」
- **FR-016**: 擴充程式在預設模式下，必須提供播放清單選單讓使用者設定預設播放清單
- **FR-017**: 擴充程式必須在非 YouTube 頁面或非影片頁面時，顯示適當的提示訊息
- **FR-018**: 擴充程式必須在網路錯誤或 API 失敗時，顯示錯誤訊息並提供重試選項

### Key Entities

- **使用者帳號**: 透過 LINE OAuth 驗證的會員，擁有唯一的 LINE User ID，對應系統中的會員資料
- **Token 資訊**: 包含 access token 與 refresh token，用於身份驗證與授權
- **播放庫**: 使用者收藏的單一影片集合，每部影片以 YouTube 影片 ID 識別
- **播放清單**: 使用者建立的影片分類清單，可包含多部影片，每個播放清單有名稱與唯一 ID
- **影片資訊**: 從 YouTube URL 解析出的影片 ID，用於識別與儲存影片

## Success Criteria

### Measurable Outcomes

- **SC-001**: 使用者能在 30 秒內完成 LINE 登入流程並看到功能按鈕
- **SC-002**: 使用者在 YouTube 影片頁面點擊「加入播放庫」按鈕後，影片在 3 秒內成功加入播放庫
- **SC-003**: 擴充程式能正確解析 100% 的標準 YouTube 影片 URL 格式（包含 `watch?v=` 與 `list=` 參數的混合）
- **SC-004**: 使用者在切換播放清單模式（預設/自訂）後，下次使用時設定仍保持不變
- **SC-005**: 95% 的使用者能在首次使用時，無需查看說明文件即可成功加入影片到播放庫或播放清單
- **SC-006**: 擴充程式在 Chrome 與 Firefox 上的功能表現一致，無瀏覽器相容性問題
- **SC-007**: 擴充程式能在 token 過期前自動更新，使用者無需手動重新登入（正常使用情況下）
- **SC-008**: 使用者在網路恢復後，能透過重試選項成功完成先前失敗的操作

## Assumptions

- 假設後端 API 已提供 LINE OAuth 登入端點與 token 管理機制
- 假設後端 API 已提供查詢播放庫與播放清單、新增影片的 RESTful API
- 假設使用者已在系統中註冊 LINE 帳號，擴充程式不負責會員註冊流程
- 假設 YouTube URL 格式遵循標準格式（`youtube.com/watch?v=VIDEO_ID` 或 `youtu.be/VIDEO_ID`）
- 假設使用者的瀏覽器允許擴充程式存取 YouTube 網站與儲存 token 資訊
- 假設擴充程式的 token 儲存使用瀏覽器的安全儲存機制（如 Chrome Storage API）
- 假設播放清單的建立與管理功能已在主系統中實作，擴充程式僅負責選擇與加入
