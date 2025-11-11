# Research: LINE Login 會員認證系統

**Feature**: 003-line-login-auth
**Date**: 2025-11-01
**Spec**: [spec.md](./spec.md)

## 目的

本研究文件記錄 LINE Login 會員認證系統的技術決策、最佳實踐和替代方案評估。目標是在不修改現有套件的前提下,設計出符合規格需求的技術方案。

## 技術環境約束

根據專案憲章和使用者指示,本功能**必須**:
- ✅ 不新增或升級任何 npm/composer 套件
- ✅ 使用現有技術堆疊:Vue 3 + Vite + Pinia (前端) 和 CodeIgniter 4 (後端)
- ✅ 遵循最小化變更原則
- ✅ 保留現有架構模式

## 決策記錄

### 決策 1: LINE Login OAuth 2.0 整合方案

**選擇**: 使用 LINE Login Web 版 OAuth 2.0 流程,不依賴第三方 SDK

**理由**:
1. **無需新套件**: LINE Login OAuth 2.0 可透過標準 HTTP 請求實現,使用現有的 `axios` (前端) 和 CodeIgniter 的 HTTP client (後端)
2. **官方支援**: LINE 提供完整的 REST API 文件和測試環境
3. **框架無關**: 不綁定特定 LINE SDK,未來更容易維護和遷移
4. **安全性**: 使用標準 OAuth 2.0 Authorization Code flow with PKCE,符合業界最佳實踐

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| LINE Login SDK for JavaScript | 官方封裝,文件完整 | **需要新增套件** `@line/bot-sdk` | ❌ 違反憲章 |
| Passport.js LINE Strategy | 成熟的認證中介軟體 | **需要新增套件** `passport`,且主要用於 Node.js 後端 | ❌ 違反憲章且不適用 PHP |
| 手動實作 OAuth 2.0 | 完全控制,無額外依賴 | 需自行處理所有細節 | ✅ 可行且符合要求 |

**實作細節**:
- 前端:使用 `axios` 發送 LINE OAuth authorize 請求和 token exchange 請求
- 後端:使用 CodeIgniter 4 內建的 `CURLRequest` 處理與 LINE API 的通訊
- Token 儲存:使用 CodeIgniter 的 Session library 和 Database (現有資料庫連線)

**參考文件**:
- [LINE Login v2.1 API Reference](https://developers.line.biz/en/reference/line-login/)
- [OAuth 2.0 RFC 6749](https://datatracker.ietf.org/doc/html/rfc6749)

---

### 決策 2: 會話管理與認證狀態

**選擇**: Token-based authentication 搭配 HTTP-only cookies

**理由**:
1. **安全性**: HTTP-only cookies 防止 XSS 攻擊竊取 token
2. **無狀態**: Access token 可包含使用者身份資訊,減少資料庫查詢
3. **符合 OAuth 標準**: LINE Login 返回 access_token 和 refresh_token,直接使用標準流程
4. **多裝置支援**: 每個裝置獨立的 token,符合 FR-016 要求

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| Session-based (PHP Sessions) | CodeIgniter 內建支援,實作簡單 | 多裝置登入需額外管理 session ID,擴展性較差 | ⚠️ 可行但擴展性不佳 |
| JWT in localStorage | 前端易於存取 | **安全風險**: 容易受 XSS 攻擊 | ❌ 安全性不足 |
| Token in HTTP-only cookies | 安全,自動隨請求發送 | 需處理 CSRF (可用 SameSite 屬性緩解) | ✅ 最佳選擇 |

**實作細節**:
- Access token 有效期:30 天 (可配置,符合 FR-004)
- Refresh token:用於自動更新 access token,不需使用者重新登入
- Cookie 屬性:`HttpOnly`, `Secure`, `SameSite=Strict`
- Token 儲存:後端資料庫記錄 (users_tokens 表),支援軟刪除和逾時檢查

**安全措施**:
- CSRF 保護:使用 CodeIgniter 內建的 CSRF token (已存在於專案中)
- Token rotation:每次 refresh 時輪換 refresh token
- 逾時檢查:中介軟體檢查 token 有效期 (FR-015)

---

### 決策 3: 資料儲存架構

**選擇**: 使用現有 MySQL 資料庫,新增認證相關表

**理由**:
1. **現有基礎設施**: 專案已配置 MySQL (從 CodeIgniter 配置檔推斷)
2. **零額外成本**: 不需新增資料庫或儲存服務
3. **關聯查詢**: 會員、影片庫、播放清單之間的關聯查詢效率高
4. **軟刪除支援**: MySQL 原生支援軟刪除模式 (deleted_at 欄位)

**資料表設計** (詳細結構見 data-model.md):
- `users`: 會員基本資料
- `user_tokens`: 認證 token 記錄
- `video_library`: 影片庫
- `playlists`: 播放清單
- `playlist_items`: 播放清單項目
- `guest_sessions`: 訪客暫存資料

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| MySQL (現有) | 已配置,關聯查詢強 | N/A | ✅ 使用現有資源 |
| Redis for sessions | 高速讀寫,適合 session | **需新增服務**,增加運維複雜度 | ❌ 違反簡單性原則 |
| LocalStorage (前端) | 實作簡單 | **安全性問題**,無法跨裝置 | ❌ 不符合需求 |

---

### 決策 4: 前端狀態管理

**選擇**: 使用現有的 Pinia store,新增 `authStore`

**理由**:
1. **現有依賴**: 專案已使用 Pinia 3.0.3
2. **響應式**: Vue 3 Composition API 與 Pinia 完美整合
3. **持久化**: 可結合 cookies 實現狀態持久化
4. **模組化**: 獨立的 auth store 不影響現有功能

**Store 結構**:
```javascript
// stores/auth.js
{
  state: {
    user: null,           // 當前登入會員資訊
    isAuthenticated: false,
    isLoading: false
  },
  actions: {
    login(),              // LINE Login 流程
    logout(),             // 登出
    checkAuth(),          // 檢查認證狀態
    refreshToken()        // 更新 token
  },
  getters: {
    isGuest(),            // 是否為訪客
    userDisplayName(),    // 顯示名稱
    userAvatar()          // 頭像 URL
  }
}
```

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| Pinia (現有) | 官方推薦,現代化 API | N/A | ✅ 使用現有依賴 |
| Vuex 4 | 成熟穩定 | **需新增套件**,已被 Pinia 取代 | ❌ 違反憲章 |
| Provide/Inject | Vue 內建,零依賴 | 缺少開發工具,難以除錯 | ⚠️ 不推薦用於複雜狀態 |

---

### 決策 5: 路由守衛與權限控制

**選擇**: 使用 Vue Router 的 Navigation Guards,新增全域前置守衛

**理由**:
1. **現有依賴**: 專案已使用 vue-router 4.6.3
2. **聲明式**: 在路由配置中聲明權限需求 (meta.requiresAuth)
3. **統一入口**: 全域守衛統一處理認證檢查
4. **使用者體驗**: 自動重定向到登入頁面或首頁

**實作細節**:
```javascript
// router/index.js
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // 需要認證但未登入,導向首頁
    next({ name: 'Home' })
  } else if (to.meta.guestOnly && authStore.isAuthenticated) {
    // 已登入但訪問僅訪客頁面
    next({ name: 'Home' })
  } else {
    next()
  }
})
```

**路由配置範例**:
```javascript
{
  path: '/library',
  name: 'VideoLibrary',
  component: VideoLibrary,
  meta: { requiresAuth: true }  // 需要認證
},
{
  path: '/',
  name: 'Home',
  component: Home,
  meta: { guestOnly: false }    // 訪客和會員皆可訪問
}
```

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| Vue Router Guards (現有) | 內建功能,無需新套件 | N/A | ✅ 使用現有功能 |
| 元件內守衛 | 細粒度控制 | 分散在各元件,難維護 | ⚠️ 作為補充使用 |
| 中介軟體模式 | 可組合,靈活 | **需要架構調整**,增加複雜度 | ❌ 過度設計 |

---

### 決策 6: UI 元件整合

**選擇**: 使用現有的 Heroicons,最小化新增 UI 元件

**理由**:
1. **現有依賴**: 專案已使用 @heroicons/vue 2.2.0
2. **一致性**: 保持現有 UI 風格
3. **輕量**: 不引入大型 UI 框架

**UI 元件策略**:
- 登入按鈕:使用 Heroicons 的 `UserCircleIcon`
- 錯誤提示:自行實作簡單的 toast 元件 (使用 Vue 3 Teleport)
- 使用者選單:下拉選單元件 (參考現有元件模式)
- 載入狀態:簡單的 spinner 元件

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| Heroicons (現有) | 已安裝,圖標完整 | 僅圖標,需自行設計元件 | ✅ 使用現有依賴 |
| Headless UI | 無樣式,完全可控 | **需新增套件** | ❌ 違反憲章 |
| Element Plus | 完整 UI 套件 | **需新增套件**,體積大 | ❌ 違反憲章 |

---

### 決策 7: 訪客資料遷移策略

**選擇**: LocalStorage 暫存 + 登入時批次遷移

**理由**:
1. **無需後端改動**: 訪客資料儲存在前端 LocalStorage
2. **簡單實作**: 登入時檢查 LocalStorage,若有資料則呼叫 API 遷移
3. **容錯性**: 遷移失敗不影響登入流程,可稍後重試

**實作流程**:
1. 訪客播放影片 → 儲存到 `localStorage['guest_history']`
2. 訪客點擊登入 → 完成 LINE OAuth
3. 登入成功 → 檢查 `localStorage['guest_history']`
4. 若有資料 → 呼叫 `POST /api/users/migrate-guest-data`
5. 後端將資料寫入 `video_library` 表
6. 遷移成功 → 清空 `localStorage['guest_history']`

**資料結構** (LocalStorage):
```json
{
  "guest_history": [
    {
      "videoId": "dQw4w9WgXcQ",
      "title": "Rick Astley - Never Gonna Give You Up",
      "thumbnail": "https://i.ytimg.com/...",
      "playedAt": "2025-11-01T10:30:00Z"
    }
  ]
}
```

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| LocalStorage + API 遷移 | 簡單,前端控制 | 跨裝置不同步 | ✅ 符合需求 |
| Cookie based | 自動隨請求發送 | 大小限制 (4KB) | ⚠️ 資料可能過大 |
| 匿名使用者表 | 後端統一管理 | **架構複雜**,需處理匿名 ID | ❌ 過度設計 |

---

### 決策 8: 軟刪除與資料清理

**選擇**: 資料庫軟刪除 + 定時任務清理

**理由**:
1. **可恢復性**: 30 天內資料可完全恢復 (FR-019)
2. **簡單實作**: 新增 `deleted_at` 欄位,查詢時過濾
3. **自動化**: Cron job 或 CodeIgniter CLI 命令定時清理過期資料

**實作細節**:
- 軟刪除:更新 `users.deleted_at = NOW()`, `users.status = 'soft_deleted'`
- 查詢過濾:WHERE `deleted_at IS NULL` 或使用 CodeIgniter Model 的軟刪除功能
- 定時清理:每日執行 CLI 命令 `php spark users:cleanup`,刪除 `deleted_at < NOW() - 30 days` 的記錄
- 恢復機制:檢查 LINE User ID,若在 30 天內重新登入,設置 `deleted_at = NULL`, `status = 'active'`

**CodeIgniter 軟刪除支援**:
```php
// app/Models/UserModel.php
class UserModel extends Model
{
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    public function restore($userId) {
        return $this->update($userId, [
            'deleted_at' => null,
            'status' => 'active'
        ]);
    }
}
```

**替代方案評估**:
| 方案 | 優點 | 缺點 | 結論 |
|------|------|------|------|
| 資料庫軟刪除 | CodeIgniter 內建支援 | 資料庫膨脹 | ✅ 符合需求且易實作 |
| 歸檔表 | 主表保持小巧 | 恢復需跨表操作 | ⚠️ 增加複雜度 |
| 立即刪除 + 備份 | 簡單 | **無法恢復**,違反 FR-019 | ❌ 不符需求 |

---

## 最佳實踐參考

### LINE Login OAuth 2.0 最佳實踐

1. **State 參數**: 防止 CSRF 攻擊,生成隨機字串並儲存在 session
2. **Nonce 參數**: 驗證 ID token 的真實性 (若使用 OpenID Connect)
3. **錯誤處理**: 處理所有 LINE 可能返回的錯誤碼 (400, 401, 403, 500)
4. **Token 更新**: Access token 過期前自動更新,避免使用者體驗中斷
5. **Webhook 訂閱**: (選用) 訂閱 LINE 帳號刪除通知,即時觸發軟刪除

**參考資源**:
- [LINE Login Best Practices](https://developers.line.biz/en/docs/line-login/integrate-line-login/)
- [OAuth 2.0 Security Best Current Practice](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics)

### CodeIgniter 4 安全最佳實踐

1. **CSRF 保護**: 已啟用 (檢查 `app/Config/Security.php`)
2. **SQL Injection**: 使用 Query Builder 或 Prepared Statements
3. **XSS 防護**: 輸出時使用 `esc()` helper
4. **環境變數**: 敏感資訊 (LINE Channel ID, Secret) 儲存在 `.env`
5. **HTTPS**: 生產環境強制 HTTPS (nginx 配置)

### Vue 3 + Pinia 最佳實踐

1. **Composition API**: 優先使用 Composition API,與 Pinia 整合更自然
2. **TypeScript** (選用): 若專案使用 TS,為 store 定義 interface
3. **錯誤邊界**: 使用 Vue 的 `onErrorCaptured` 捕獲全域錯誤
4. **Loading 狀態**: 所有非同步操作應有 loading indicator
5. **樂觀更新**: UI 先更新,API 失敗時回滾 (視需求)

---

## 技術風險與緩解措施

### 風險 1: LINE API 速率限制

**風險**: LINE Login API 有速率限制,高流量時可能被限制

**緩解措施**:
- 實作指數退避重試機制
- 快取 access token,避免重複驗證請求
- 監控 API 使用量,設置警報

### 風險 2: Token 洩漏

**風險**: Token 若被竊取,攻擊者可冒充使用者

**緩解措施**:
- 使用 HTTP-only cookies 儲存 token
- 短期 access token (30 分鐘) + 長期 refresh token (30 天)
- 實作 token rotation
- 提供「登出所有裝置」功能

### 風險 3: 訪客資料遷移失敗

**風險**: 網路問題或 API 錯誤導致遷移失敗,資料遺失

**緩解措施**:
- 重試機制 (最多 3 次)
- 失敗時保留 LocalStorage 資料,稍後重試
- 提供手動觸發遷移的入口 (設置頁面)
- 記錄失敗日誌,供後續排查

### 風險 4: 軟刪除資料膨脹

**風險**: 大量軟刪除資料導致資料庫效能下降

**緩解措施**:
- 定期清理過期資料 (每日執行)
- 資料庫索引優化 (`deleted_at` 欄位)
- 監控資料表大小,設置警報
- 若資料量過大,考慮歸檔表方案

---

## 效能考量

### 前端效能

- **程式碼分割**: 認證相關元件使用動態匯入 `() => import()`
- **快取策略**: Service Worker 快取靜態資源 (選用)
- **Bundle 大小**: 無新增大型依賴,影響最小

### 後端效能

- **資料庫查詢優化**:
  - 為 `users.line_user_id` 建立唯一索引
  - 為 `user_tokens.user_id` 建立索引
  - 為 `video_library.user_id` 建立索引

- **快取策略**:
  - 使用 CodeIgniter 的 Cache library 快取使用者資訊 (TTL: 5 分鐘)
  - Redis (選用,未來擴展)

### 預期負載

- **會員數**: 1000 同時在線 (SC-006)
- **每會員影片數**: 500 (SC-006)
- **資料庫記錄**: ~500,000 rows (1000 users × 500 videos)
- **查詢效能**: 索引優化後,預期 < 50ms

---

## 總結

本研究確認以下技術方案**完全符合**專案憲章要求:

✅ **零新增套件**: 所有功能使用現有依賴實現
✅ **最小化變更**: 僅新增必要的檔案和資料表
✅ **安全性**: 遵循 OAuth 2.0 和 Web 安全最佳實踐
✅ **效能**: 預期可支援 1000+ 同時在線使用者
✅ **可維護性**: 程式碼結構清晰,易於擴展

下一步將根據這些決策,生成詳細的資料模型 (data-model.md) 和 API 合約 (contracts/)。
