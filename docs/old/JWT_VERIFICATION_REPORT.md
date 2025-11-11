# JWT Token 驗證報告

**日期：** 2025-11-02
**專案：** Free YouTube Player
**驗證目的：** 確認所有 JWT 操作使用相同的 Secret Key

---

## ✅ 驗證結果摘要

**結論：所有 JWT 生成和驗證處使用相同的 Secret Key 和算法**

---

## 📋 檢查項目

### 1. 後端 JWT Secret Key 統一性

#### 1.1 Secret Key 來源

所有 JWT 操作都通過 `JwtHelper` 類別進行，統一從環境變數讀取：

```php
// 檔案：/backend/app/Helpers/JwtHelper.php
private static function init(): void
{
    if (!isset(self::$secretKey)) {
        self::$secretKey = env('JWT_SECRET_KEY');

        if (empty(self::$secretKey)) {
            throw new Exception('JWT_SECRET_KEY 未設置，請在 .env 檔案中設置');
        }
    }
}
```

**✅ 驗證通過**：所有 JWT 操作使用同一個 Secret Key 來源

#### 1.2 JWT 生成處

以下所有地方生成 JWT 都使用相同的 `JwtHelper`：

1. **登入時生成 Token**
   - 檔案：`/backend/app/Controllers/Auth.php:944`
   - 方法：`generateUserToken()` → `JwtHelper::generateAccessToken()`

2. **刷新 Token**
   - 檔案：`/backend/app/Controllers/Auth.php:948`
   - 方法：`generateUserToken()` → `JwtHelper::generateRefreshToken()`

3. **Mock 登入**
   - 檔案：`/backend/app/Controllers/Auth.php:1113-1114`
   - 方法：`mockLogin()` → `JwtHelper::generateAccessToken()` + `generateRefreshToken()`

**✅ 驗證通過**：所有 Token 生成都使用同一個 Helper 和 Secret Key

#### 1.3 JWT 驗證處

以下所有地方驗證 JWT 都使用相同的 `JwtHelper`：

1. **AuthFilter 驗證 Access Token**
   - 檔案：`/backend/app/Filters/AuthFilter.php:39, 57`
   - 方法：`JwtHelper::verifyToken($accessToken, 'access')`

2. **刷新 API 驗證 Refresh Token**
   - 檔案：`/backend/app/Controllers/Auth.php:517`
   - 方法：`JwtHelper::verifyToken($refreshToken, 'refresh')`

**✅ 驗證通過**：所有 Token 驗證都使用同一個 Helper 和 Secret Key

---

### 2. Secret Key 配置

#### 環境變數配置

```bash
# 檔案：/backend/.env
JWT_SECRET_KEY = 'eAsHZgi+F4qyWN84MuoZiPRKdgEkUMSB7cX4PGSRnkaXj4xVEQ28rBF8O8UTddAOnvAYjgoPQ2kQ6nkp/sE3fQ=='
JWT_ACCESS_TOKEN_EXPIRE = 900        # 15 分鐘
JWT_REFRESH_TOKEN_EXPIRE = 2592000   # 30 天
```

**✅ 驗證通過**：Secret Key 長度 88 字元，使用 Base64 編碼的強隨機字串

#### 加密算法

所有 JWT 操作使用相同的算法：

```php
// 檔案：/backend/app/Helpers/JwtHelper.php:14
private static string $algorithm = 'HS256';
```

**✅ 驗證通過**：統一使用 HS256 (HMAC-SHA256) 算法

---

### 3. 前端 JWT 處理

#### 前端是否解碼 JWT？

經過檢查，前端**沒有任何 JWT 解碼或驗證的程式碼**。

**原因：**
- 使用 HTTP-only Cookie 儲存 Token
- 瀏覽器自動在請求中攜帶 Cookie
- 前端無法通過 JavaScript 讀取 HTTP-only Cookie
- 所有 JWT 驗證都在後端進行

**✅ 驗證通過**：前端不需要也無法解碼 JWT，符合安全最佳實踐

---

### 4. Token 流轉過程

#### Access Token 流程

```
生成（後端）：JwtHelper::generateAccessToken(userId)
    ↓ 使用 JWT_SECRET_KEY 簽名
設置 Cookie（後端）：setAuthCookie($accessToken, $refreshToken)
    ↓ HTTP-only Cookie
請求攜帶（瀏覽器）：自動在每個 API 請求中攜帶
    ↓
驗證（後端）：JwtHelper::verifyToken($accessToken, 'access')
    ↓ 使用相同的 JWT_SECRET_KEY 驗證簽名
返回用戶 ID：$decoded->sub
```

#### Refresh Token 流程

```
生成（後端）：JwtHelper::generateRefreshToken(userId, deviceId)
    ↓ 使用 JWT_SECRET_KEY 簽名，包含 JTI
設置 Cookie（後端）：setAuthCookie($accessToken, $refreshToken)
    ↓ HTTP-only Cookie，有效期 30 天
Access Token 過期時（前端）：自動調用 /auth/refresh
    ↓ 瀏覽器自動攜帶 refresh_token Cookie
驗證（後端）：JwtHelper::verifyToken($refreshToken, 'refresh')
    ↓ 使用相同的 JWT_SECRET_KEY 驗證簽名
檢查 JTI（後端）：資料庫查詢 jti 是否被撤銷
    ↓
生成新 Token Pair（後端）：重新調用 generateUserToken()
```

**✅ 驗證通過**：Token 在整個生命週期中都使用相同的 Secret Key

---

## 🧪 實際測試結果

### 測試命令

```bash
docker exec free_youtube_backend_prod php spark test:jwt
```

### 測試輸出

```
=== JWT Token 驗證測試 ===

測試 1: 檢查 Secret Key
✅ JWT_SECRET_KEY 已設置
   長度: 88 字元

測試 2: 生成 Access Token
✅ Access Token 生成成功
   Token 長度: 265 字元

測試 3: 驗證 Access Token（使用相同的 Secret Key）
✅ Access Token 驗證成功
   用戶 ID: 999
   Token 類型: access
   過期時間: 2025-11-02 05:43:46

測試 4: 生成 Refresh Token
✅ Refresh Token 生成成功
   Token 長度: 361 字元

測試 5: 驗證 Refresh Token（使用相同的 Secret Key）
✅ Refresh Token 驗證成功
   用戶 ID: 999
   Token 類型: refresh
   JTI: d870fbaf70b164dd874df2d773e30508

測試 6: 驗證 Token 類型檢查機制
✅ Token 類型檢查正常

測試 7: Token 有效期檢查
✅ Access Token 剩餘有效時間: 900 秒

測試 8: 解碼 Token Payload（不驗證簽名）
✅ Token 解碼成功

測試 9: 從 Token 取得用戶 ID
✅ 成功從 Token 取得用戶 ID: 999
```

### 測試結論

```
=== 所有測試通過！✅ ===

1. ✅ 所有 JWT 操作使用相同的 Secret Key
2. ✅ Access Token 和 Refresh Token 使用相同的加密算法 (HS256)
3. ✅ Token 生成和驗證流程正常
4. ✅ Token 類型檢查機制正常
5. ✅ Token 有效期檢查正常

前後端 JWT 驗證機制使用相同的 Secret Key 和算法！
```

---

## 📊 程式碼使用統計

### JwtHelper 調用位置

| 檔案 | 行數 | 方法 | 用途 |
|------|------|------|------|
| AuthFilter.php | 39 | verifyToken() | Mock 模式驗證 |
| AuthFilter.php | 57 | verifyToken() | 正常模式驗證 |
| Auth.php | 517 | verifyToken() | Refresh API 驗證 |
| Auth.php | 944 | generateAccessToken() | 生成 Access Token |
| Auth.php | 948 | generateRefreshToken() | 生成 Refresh Token |
| Auth.php | 951 | decode() | 解碼取得 JTI |
| Auth.php | 1113 | generateAccessToken() | Mock 登入生成 |
| Auth.php | 1114 | generateRefreshToken() | Mock 登入生成 |

**總計：8 個調用點，全部使用同一個 JwtHelper 類別**

---

## 🔐 安全性確認

### ✅ 已確認的安全措施

1. **Secret Key 安全**
   - 使用 88 字元的強隨機字串
   - 儲存在 `.env` 檔案中（不提交到版本控制）
   - 只在後端使用，前端無法存取

2. **HTTP-only Cookie**
   - 防止 XSS 攻擊
   - JavaScript 無法讀取 Token
   - 瀏覽器自動管理

3. **Token 類型區分**
   - Access Token 和 Refresh Token 有明確的 `type` claim
   - 驗證時強制檢查類型，防止混用

4. **有效期管理**
   - Access Token: 15 分鐘（短期）
   - Refresh Token: 30 天（長期）
   - 可撤銷機制（資料庫 JTI 檢查）

5. **SameSite 防護**
   - Cookie 設置 `SameSite: Lax`
   - 防止 CSRF 攻擊

---

## 📝 結論

### ✅ 最終確認

1. **所有後端 JWT 生成都使用相同的 Secret Key**
   - 來源：`env('JWT_SECRET_KEY')`
   - 統一由 `JwtHelper` 類別管理

2. **所有後端 JWT 驗證都使用相同的 Secret Key**
   - 驗證方法：`JwtHelper::verifyToken()`
   - 使用相同的 Secret Key 和算法

3. **前端不涉及 JWT 解碼或驗證**
   - 使用 HTTP-only Cookie
   - 所有驗證在後端進行
   - 符合安全最佳實踐

4. **Token 生成和驗證流程完整**
   - 生成 → 簽名 → 儲存 → 驗證 → 使用
   - 整個流程使用統一的 Secret Key

### ✅ 測試驗證

實際測試證明：
- Token 生成成功
- Token 驗證成功
- Token 類型檢查正常
- Token 有效期檢查正常
- 跨類型驗證正確拒絕

---

## 🎯 建議

### 現有實作已經非常完善

當前的 JWT 實作符合業界最佳實踐：

1. ✅ 統一的 Secret Key 管理
2. ✅ 標準的 JWT 格式和算法
3. ✅ 完整的雙 Token 機制
4. ✅ HTTP-only Cookie 安全儲存
5. ✅ 自動刷新機制
6. ✅ Token 撤銷支援

### 未來可選優化（非必要）

1. **Secret Key 輪替機制**
   - 定期更換 Secret Key
   - 支援多個 Key 並行（舊 Token 仍可驗證）

2. **Token 黑名單**
   - Redis 快取撤銷的 JTI
   - 加快撤銷檢查速度

3. **監控和告警**
   - 記錄 Token 驗證失敗次數
   - 異常登入行為偵測

---

**驗證完成日期：** 2025-11-02
**驗證人員：** Claude Code
**驗證工具：** `php spark test:jwt`
