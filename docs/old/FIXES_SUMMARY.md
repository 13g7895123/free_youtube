# httpOnly Cookie 問題修正摘要

## 問題描述

### 原始問題
正式環境日誌顯示：
```json
{
  "uri": "http://localhost:8080/api/auth/user",
  "origin": "https://free.youtube.mercylife.cc",
  "cookie_header": "(none)",
  "has_access_token": false
}
```

**核心問題：**
- 前端呼叫 `http://localhost:8080/api` 而非 `https://free.youtube.mercylife.cc/api`
- httpOnly Cookie 因跨域無法傳遞
- 使用者無法保持登入狀態

---

## 根本原因分析

### 1. 前端硬編碼 URL
**檔案：** `frontend/src/views/LineLoginLogs.vue:162`

**問題代碼：**
```javascript
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api'
```

**問題點：**
1. 使用了錯誤的環境變數名 `VITE_API_BASE_URL`（正確應為 `VITE_API_URL`）
2. 預設值硬編碼為 `http://localhost:8080/api`
3. 導致正式環境仍使用 localhost

### 2. 缺少後端環境變數
- `JWT_SECRET_KEY` - JWT 簽名金鑰（安全必需）
- `COOKIE_DOMAIN` - Cookie 域名配置（跨子域支援）

### 3. Cookie 安全性配置不足
**檔案：** `backend/app/Config/Cookie.php`

**問題：**
- `$secure` 和 `$domain` 屬性未從環境變數讀取
- 無法根據環境動態調整安全設置

---

## 修正內容

### 修正 1：前端 API URL 配置

**檔案：** `frontend/src/views/LineLoginLogs.vue`

**修正前：**
```javascript
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api'
```

**修正後：**
```javascript
const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
```

**改進：**
- ✅ 使用正確的環境變數 `VITE_API_URL`
- ✅ 預設值改為相對路徑 `/api`
- ✅ 透過 Nginx 代理自動解析為正確域名

---

### 修正 2：補充環境變數配置

**檔案：** `.env.prod.example`

**新增配置：**
```bash
# ========================================
# JWT 配置
# ========================================
JWT_SECRET_KEY=your-generated-secret-key-here
JWT_ACCESS_TOKEN_EXPIRE=900
JWT_REFRESH_TOKEN_EXPIRE=2592000

# ========================================
# Cookie 配置
# ========================================
COOKIE_DOMAIN=
```

**檔案：** `docker-compose.prod.yml`

**新增環境變數傳遞：**
```yaml
environment:
  # Cookie 配置
  COOKIE_DOMAIN: ${COOKIE_DOMAIN:-}
```

**改進：**
- ✅ 提供 JWT 金鑰配置範例
- ✅ 支援 Cookie 域名自定義
- ✅ 確保環境變數正確傳遞到容器

---

### 修正 3：強化 Cookie 安全性

**檔案：** `backend/app/Config/Cookie.php`

**新增 Constructor：**
```php
public function __construct()
{
    parent::__construct();

    // 從環境變數讀取 Cookie Domain
    $cookieDomain = getenv('COOKIE_DOMAIN');
    if ($cookieDomain !== false && $cookieDomain !== '') {
        $this->domain = $cookieDomain;
    }

    // 正式環境強制啟用 secure（僅 HTTPS）
    $environment = getenv('CI_ENVIRONMENT') ?: 'production';
    if ($environment === 'production') {
        $this->secure = true;
    }
}
```

**改進：**
- ✅ 自動從環境變數讀取 Cookie Domain
- ✅ 正式環境強制啟用 `secure=true`（僅 HTTPS）
- ✅ 提升 Cookie 安全性

---

## 修正後的效果

### 預期日誌
```json
{
  "uri": "https://free.youtube.mercylife.cc/api/auth/user",
  "origin": "https://free.youtube.mercylife.cc",
  "cookie_header": "access_token=xxx; refresh_token=xxx",
  "has_access_token": true
}
```

### 改善項目
- ✅ API URL 使用 HTTPS
- ✅ httpOnly Cookie 正確傳遞
- ✅ 使用者登入狀態持久化
- ✅ Token 自動刷新正常運作
- ✅ 安全性提升（強制 HTTPS）

---

## 檔案變更清單

### 修改的檔案

1. **frontend/src/views/LineLoginLogs.vue**
   - 修正 API URL 環境變數名稱
   - 變更預設值為相對路徑

2. **backend/app/Config/Cookie.php**
   - 新增 Constructor 方法
   - 從環境變數讀取 Cookie 配置
   - 正式環境強制啟用 secure

3. **.env.prod.example**
   - 新增 JWT 配置區塊
   - 新增 Cookie 配置區塊
   - 補充詳細說明

4. **docker-compose.prod.yml**
   - 新增 COOKIE_DOMAIN 環境變數傳遞

### 新增的文件

1. **docs/deployment-guide.md**
   - 完整部署指南
   - 疑難排解方案
   - 監控維護指引

2. **docs/DEPLOYMENT_CHECKLIST.md**
   - 部署前檢查清單
   - 部署步驟確認
   - 驗證測試清單

3. **docs/FIXES_SUMMARY.md**（本文件）
   - 問題分析總結
   - 修正內容說明
   - 技術細節記錄

---

## 部署步驟摘要

### 快速部署
```bash
# 1. 更新 .env.prod
cp .env.prod.example .env.prod
nano .env.prod  # 設置必要的環境變數

# 2. 生成 JWT Secret Key
openssl rand -base64 64  # 複製到 .env.prod

# 3. 重新構建並部署
docker-compose -f docker-compose.prod.yml --env-file .env.prod down
docker-compose -f docker-compose.prod.yml --env-file .env.prod build --no-cache
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d

# 4. 驗證部署
docker-compose -f docker-compose.prod.yml logs --tail=50 backend | grep "AuthFilter"
```

### 驗證重點
```bash
# 檢查 URI 是否使用 HTTPS
docker-compose -f docker-compose.prod.yml logs backend | grep '"uri"'

# 應該看到：
# "uri": "https://free.youtube.mercylife.cc/api/*"
# 而不是：
# "uri": "http://localhost:8080/api/*"
```

---

## 技術細節

### httpOnly Cookie 工作原理

**httpOnly Cookie 的限制：**
1. JavaScript 無法讀取（防止 XSS 攻擊）
2. 僅透過 HTTP(S) 請求自動傳送
3. 受同源政策（Same-Origin Policy）限制

**同源政策要求：**
- 協議相同（HTTP vs HTTPS）
- 域名相同（localhost vs mercylife.cc）
- 埠號相同（8080 vs 443）

**原始問題：**
```
前端域名：https://free.youtube.mercylife.cc:443
API 域名： http://localhost:8080

結果：跨域 → Cookie 無法傳遞
```

**修正後：**
```
前端域名：https://free.youtube.mercylife.cc:443
API 域名： https://free.youtube.mercylife.cc:443/api

結果：同域 → Cookie 正常傳遞
```

### Nginx 代理配置

**工作流程：**
```
使用者請求: https://free.youtube.mercylife.cc/api/auth/user
    ↓
Nginx 接收請求
    ↓
proxy_pass: http://backend:8000/api/auth/user
    ↓
後端處理並設置 Cookie
    ↓
Nginx 傳遞 Set-Cookie 標頭給前端
    ↓
瀏覽器儲存 Cookie（綁定到 free.youtube.mercylife.cc）
```

**關鍵 Nginx 配置：**
```nginx
location /api/ {
    proxy_pass http://backend:8000;
    proxy_set_header Cookie $http_cookie;        # 傳遞 Cookie
    proxy_pass_header Set-Cookie;                # 傳遞 Set-Cookie
    proxy_hide_header Access-Control-Allow-Origin;  # 保留後端 CORS
}
```

### Cookie Domain 配置說明

**COOKIE_DOMAIN 選項：**

1. **留空（推薦）**
   ```bash
   COOKIE_DOMAIN=
   ```
   - Cookie 綁定到當前域名（`free.youtube.mercylife.cc`）
   - 僅該域名可訪問
   - 安全性最高

2. **設置主域名**
   ```bash
   COOKIE_DOMAIN=.mercylife.cc
   ```
   - Cookie 可在所有子域名共享
   - `free.youtube.mercylife.cc`、`api.mercylife.cc` 都可訪問
   - 適合多子域名應用

**建議：**
- 單一域名應用：留空
- 多子域名應用：設置為 `.主域名`

---

## 安全性提升

### 修正前
- ❌ Cookie 可能在 HTTP 環境傳送（`secure=false`）
- ❌ Cookie Domain 無法自定義
- ❌ JWT Secret 未在配置範例中

### 修正後
- ✅ 正式環境強制 HTTPS（`secure=true`）
- ✅ Cookie Domain 可透過環境變數配置
- ✅ JWT Secret 有清楚的配置指引
- ✅ 所有敏感配置集中在 `.env.prod`

---

## 測試建議

### 單元測試
```bash
# 測試 Cookie 配置
docker exec free_youtube_backend_prod php spark test --filter CookieTest

# 測試 JWT
docker exec free_youtube_backend_prod php spark test --filter JWTTest
```

### 整合測試
```bash
# 1. 測試登入流程
curl -i https://free.youtube.mercylife.cc/api/auth/line/login

# 2. 檢查 Set-Cookie 標頭
# 應包含：Secure; HttpOnly; Domain=.mercylife.cc (或無 Domain)

# 3. 測試 Token 刷新
curl -i -X POST https://free.youtube.mercylife.cc/api/auth/refresh \
  -H "Cookie: refresh_token=xxx"
```

### E2E 測試
1. 開啟瀏覽器前往 `https://free.youtube.mercylife.cc`
2. 點擊 LINE Login
3. 完成授權
4. 檢查開發者工具 > Application > Cookies
5. 確認 Cookie 存在且屬性正確
6. 重新整理頁面，確認仍保持登入狀態

---

## 參考資料

### 相關文件
- [MDN - HTTP Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
- [MDN - Same-Origin Policy](https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy)
- [OWASP - Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

### 專案文件
- [部署指南](./deployment-guide.md)
- [部署檢查清單](./DEPLOYMENT_CHECKLIST.md)
- [環境變數範例](../.env.prod.example)

---

## 版本記錄

**修正版本：** v1.0.0
**修正日期：** 2025-11-03
**修正人員：** Claude Code

**主要變更：**
- 修正前端 API URL 配置錯誤
- 補充後端環境變數支援
- 強化 Cookie 安全性設定
- 新增完整部署文檔

**影響範圍：**
- Frontend: LineLoginLogs.vue
- Backend: Cookie.php
- Config: .env.prod.example, docker-compose.prod.yml
- Docs: 新增 3 份文檔

**向後兼容：**
- ✅ 完全向後兼容
- ✅ 無需資料庫遷移
- ✅ 現有使用者 Token 仍有效（除非更改 JWT_SECRET_KEY）
