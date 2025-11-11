# Token 機制說明

> 更新日期：2025-11-06

本文檔彙整系統中 Access Token 與 Refresh Token 的產生、儲存、驗證與刷新流程，並補充前端協作方式與現有限制。以下說明同時適用於 LINE Login 與開發用的 Mock 登入；兩者最終都會呼叫 `Auth::generateUserToken()` 生成相同型態的 Token。

## 1. 環境變數與預設值
- 檔案：`backend/.env`
  - `JWT_SECRET_KEY`：簽署 JWT 用的密鑰。
  - `JWT_ACCESS_TOKEN_EXPIRE`：Access Token 有效秒數，預設 `900` 秒（15 分鐘）。
  - `JWT_REFRESH_TOKEN_EXPIRE`：Refresh Token 有效秒數，預設 `2592000` 秒（30 天）。
  - `AUTH_MODE`：`line`（預設生產環境）或 `mock`（開發用）。
  - `COOKIE_DOMAIN`：若設定，`setAuthCookie()` 會以此為 cookie domain；否則僅套用同網域。
  - `CI_ENVIRONMENT=production` 時，cookie 會套用 `secure=true`。

## 2. 登入流程概覽

### 2.1 LINE Login（實際使用者）
1. 使用者透過 LINE OAuth 完成授權，後端在 `Auth::lineLoginCallback()`（`backend/app/Controllers/Auth.php`）取得 LINE Profile。
2. `createOrUpdateUser()` 建立或更新使用者資料後，呼叫 `generateUserToken($userId)` 產生一組新的 Access/Refresh Token。
3. `setAuthCookie()` 以 HTTP-only cookie 寫入 `access_token`、`refresh_token`。
4. 成功後重新導向前端（`FRONTEND_URL`），前端會呼叫 `/api/auth/user` 確認登入狀態。

### 2.2 Mock 登入（開發模式）
- 端點：`POST /api/auth/mock/login`
- 路徑：`Auth::mockLogin()`。
- 成功條件：`AUTH_MODE=mock` 且環境非 production。
- 流程：直接使用 `MOCK_USER_ID`，同樣呼叫 `generateUserToken()` 生成 token，並回傳模擬用戶資訊。
- 若資料庫不可用，會 fallback 生成短期 JWT，但不寫入 `user_tokens` 表。

## 3. Access Token
- 產生：`JwtHelper::generateAccessToken()`（`backend/app/Helpers/JwtHelper.php`）
  - 使用 HS256，payload 含 `sub`（user id）、`type=access`、`exp`。
  - 有效時間取自 `JWT_ACCESS_TOKEN_EXPIRE`（預設 900 秒）。
- 寫入 Cookie：`setAuthCookie()`
  - 名稱：`access_token`
  - 屬性：`HttpOnly`、`SameSite=Lax`、`Secure`（僅 prod）
  - 到期：與 JWT 同步（秒數轉成 cookie 失效時間）。
- 資料庫：`generateUserToken()` 會將 `hash('sha256', $accessToken)` 存進 `user_tokens.access_token`，僅作為相容性備份；實際驗證以 JWT 為主。

## 4. Refresh Token
- 產生：`JwtHelper::generateRefreshToken()`
  - 有效時間：`JWT_REFRESH_TOKEN_EXPIRE`（預設 30 天）。
  - Payload 包含 `type=refresh`、唯一 `jti`，以及 `device_id`（以 UA+IP 雜湊）。
- 儲存：`generateUserToken()` 會將 `jti` 或 refresh token 的雜湊存入 `user_tokens.refresh_token`，並記錄 `device_id`、`ip_address`、`user_agent`、`expires_at`。
- Cookie：`setAuthCookie()` 以 HTTP-only cookie 寫入 `refresh_token`，與 Access Token 相同屬性但有較長到期時間。

## 5. Token 驗證
- 過濾器：`App\Filters\AuthFilter`（`backend/app/Filters/AuthFilter.php`）
  1. 從 cookie 讀取 `access_token`。
  2. 呼叫 `JwtHelper::verifyToken($token, 'access')` 驗證簽章與有效期限。
  3. 於 request 附加 `$request->userId` 供控制器使用。
  4. 若為 `AUTH_MODE=mock`，失敗時會 fallback 使用 `MOCK_USER_ID`，方便開發。
- 補充：當前程式僅在日誌中記錄 access token 是否存在於 `user_tokens`，未強制檢查撤銷狀態。

## 6. Refresh 流程
- 端點：`POST /api/auth/refresh`
- 控制器：`Auth::refresh()`
  1. 讀取 `refresh_token` cookie 並驗證為有效 JWT（`JwtHelper::verifyToken(...,'refresh')`）。
  2. 取出 `sub`（user id）與 `jti`，確認資料庫 `user_tokens` 存在對應記錄。
  3. 找不到紀錄時回傳 401，代表 token 已被撤銷或過期。
  4. 找到紀錄後，刪除舊條目（Refresh Token 具備輪替機制）。
  5. 再次呼叫 `generateUserToken()` 產生新 pair，更新 cookie 並回傳剩餘時間。

### 前端自動刷新
- 檔案：`frontend/src/services/api.js`
  - Axios response interceptor 在遇到 401 時（排除 `/auth/refresh` 本身），會向 `/auth/refresh` 送出 POST 请求。
  - 若刷新成功，重播原請求；失敗則 dispatch `auth:unauthorized` 事件。
- 檔案：`frontend/src/stores/auth.js`
  - 監聽 `auth:unauthorized`，重置登入狀態並導回首頁顯示 session 過期訊息。
 - **刷新觸發時機**：前端不會主動計時刷新。Access Token 預設 15 分鐘過期，只有在過期後第一次 API 回傳 401 時才會向 `/auth/refresh` 取得新 token。

## 7. 登出流程與限制
- 端點：`POST /api/auth/logout`
- 控制器：`Auth::logout()`
  - 會呼叫 `UserTokenModel::revokeAllUserTokens($userId)`，刪除 `user_tokens` 中該使用者的所有紀錄。
  - 僅 `delete_cookie('access_token')`；**目前未主動刪除 `refresh_token` cookie**。

> ⚠️ 限制（依使用者要求列出）：登出成功後瀏覽器仍保留舊的 `refresh_token` cookie。雖然資料庫已撤銷對應 JTI，再次使用會因查不到紀錄而失敗，但建議後續補強同步刪除 cookie 或改以 `set_cookie(..., expire=0)` 覆蓋。

## 8. 資料庫結構
- 表：`user_tokens`
  - 欄位（節錄）：`user_id`, `access_token`, `refresh_token`, `expires_at`, `device_id`, `ip_address`, `user_agent`, `created_at`, `updated_at`
  - 建立位置：`backend/app/Database/Migrations/2025_11_01_000001_CreateLineLoginTables.php`
  - 刪除策略：`user_id` 採 `ON DELETE CASCADE`，使用者刪除時會自動清除 token。

## 9. 建議測試與調整
- 重要操作測試流程：
  1. `POST /api/auth/mock/login` 或 LINE Login → 確認寫入 cookie。
  2. 呼叫保護路由 → 取得 200，表示 Access Token 正常。
  3. 手動調整 `JWT_ACCESS_TOKEN_EXPIRE` 為小值，等候過期 → 前端應自動刷新成功並續播原請求。
  4. `POST /api/auth/logout` → 確認 `user_tokens` 清空（刷新應回傳 401）。
- 若需延長登入維持時間，可在 `.env` 中調整 `JWT_ACCESS_TOKEN_EXPIRE`，並同步更新文件。

---
如需延伸說明（例如：LINE Login 流程圖、Token 旋轉例外處理等），歡迎於此文件後續章節補充。