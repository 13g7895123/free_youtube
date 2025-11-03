# LINE Login 故障排除指南

## 問題：登入後顯示未登入

### 已修復的問題

**症狀**：用戶透過 LINE 登入後，被重定向回網站，但前端仍然顯示未登入狀態。

**根本原因**：
1. 登入成功後，後端正確設置了 `access_token` cookie
2. 但前端在接收到 `?login=success` 後，沒有重新檢查認證狀態
3. 導致前端的 `authStore` 仍然保持未登入狀態

**修復內容**：

1. **前端修改** (`frontend/src/views/Home.vue`):
   ```javascript
   if (loginStatus === 'success') {
     console.log('登入成功！重新檢查認證狀態...')

     // ✅ 新增：重新檢查認證狀態以更新 UI
     await authStore.checkAuth()

     if (restored === '1') {
       alert('歡迎回來！您的帳號資料已完全恢復')
     }
   }
   ```

2. **後端改進** (`backend/app/Controllers/Auth.php`):
   - 添加了 Cookie 設置的詳細日誌
   - 支援可選的 `COOKIE_DOMAIN` 環境變數（用於跨子域名場景）

---

## 測試 LINE Login

### 環境配置

確保以下環境變數已正確設定：

```bash
# .env 或 .env.prod
AUTH_MODE=line
LINE_LOGIN_CHANNEL_ID=your_channel_id
LINE_LOGIN_CHANNEL_SECRET=your_channel_secret
LINE_LOGIN_CALLBACK_URL=https://your-domain.com/api/auth/line/callback
FRONTEND_URL=https://your-domain.com
TOKEN_EXPIRE_SECONDS=2592000
```

### 測試步驟

1. **清除瀏覽器 Cookies**
   - 開啟開發者工具 → Application → Cookies
   - 刪除所有與你的網站相關的 cookies

2. **點擊 LINE 登入按鈕**
   - 應該會重定向到 LINE 登入頁面
   - URL 應為 `https://access.line.me/oauth2/v2.1/authorize?...`

3. **完成 LINE 授權**
   - 登入 LINE 帳號
   - 授權應用程式

4. **檢查重定向**
   - 應該重定向回 `https://your-domain.com/?login=success`
   - 瀏覽器開發者工具的 Console 應該顯示：
     ```
     登入成功！重新檢查認證狀態...
     ```

5. **檢查 Cookie**
   - 開發者工具 → Application → Cookies
   - 應該看到 `access_token` cookie
   - 屬性：
     - HttpOnly: ✓
     - Secure: ✓ (HTTPS 環境)
     - SameSite: Lax
     - Expires: 30 days from now

6. **檢查認證狀態**
   - 頁面右上角應顯示用戶頭像和名稱
   - 導航欄應顯示「影片庫」和「播放清單」連結
   - 播放器頁面應顯示「加入」按鈕

---

## 常見問題

### 1. Cookie 沒有被設置

**可能原因**：
- ✗ HTTPS 網站但 `CI_ENVIRONMENT` 不是 `production`
- ✗ HTTP 網站但 `secure=true`（production 環境）
- ✗ Cookie domain 設置錯誤

**解決方案**：
```bash
# 正式環境（HTTPS）
APP_ENV=production

# 開發環境（HTTP）
APP_ENV=development
```

**檢查日誌**：
```bash
docker logs free_youtube_backend_prod | grep "Auth cookie set"
```

應該看到：
```
Auth cookie set: expires=2592000s, secure=true, domain=default
```

### 2. Cookie 被設置但前端收不到

**可能原因**：
- ✗ 前端和後端在不同域名
- ✗ CORS 設置問題
- ✗ `withCredentials` 未啟用

**檢查**：
1. 前端 `api.js` 中是否有：
   ```javascript
   withCredentials: true
   ```

2. 後端是否允許來源的 credentials：
   ```php
   // 檢查 CORS 設置
   header('Access-Control-Allow-Credentials: true');
   ```

### 3. 認證狀態沒有更新

**可能原因**：
- ✗ 登入成功後沒有呼叫 `checkAuth()`
- ✗ API 請求沒有攜帶 cookie

**解決方案**：
已在本次修復中解決（`Home.vue` 會在登入成功後呼叫 `authStore.checkAuth()`）

### 4. 跨子域名的 Cookie 問題

**場景**：
- 前端：`www.example.com`
- 後端：`api.example.com`

**解決方案**：
```bash
# .env.prod
COOKIE_DOMAIN=.example.com  # 注意開頭的點
```

這樣 cookie 會在所有 `*.example.com` 子域名下共享。

---

## 調試技巧

### 1. 檢查 Cookie 流程

在瀏覽器開發者工具 → Network 中：

1. **LINE Login 重定向**
   - 找到 `/api/auth/line/callback?code=...` 請求
   - 查看 Response Headers
   - 應該有 `Set-Cookie: access_token=...`

2. **後續 API 請求**
   - 找到 `/api/auth/user` 請求
   - 查看 Request Headers
   - 應該有 `Cookie: access_token=...`

### 2. 檢查後端日誌

```bash
# 即時查看日誌
docker logs -f free_youtube_backend_prod

# 搜尋 LINE Login 相關日誌
docker logs free_youtube_backend_prod | grep -E "LINE|login|cookie"
```

### 3. 檢查前端 Console

F12 → Console，應該看到：
```
登入成功！重新檢查認證狀態...
```

如果沒有，表示登入回調處理邏輯沒有執行。

---

## 環境檢查清單

部署前確認：

- [ ] `AUTH_MODE=line`
- [ ] `LINE_LOGIN_CHANNEL_ID` 已設定
- [ ] `LINE_LOGIN_CHANNEL_SECRET` 已設定
- [ ] `LINE_LOGIN_CALLBACK_URL` 正確（HTTPS）
- [ ] `FRONTEND_URL` 正確（HTTPS）
- [ ] LINE Developers Console 中的 Callback URL 與 `LINE_LOGIN_CALLBACK_URL` 一致
- [ ] 使用 HTTPS（正式環境必須）
- [ ] `APP_ENV=production`（HTTPS 環境）
- [ ] 前端已重新建置（包含最新的 `Home.vue` 修改）
- [ ] 後端已重新部署（包含最新的 `Auth.php` 修改）

---

## 支援資訊

如果問題仍然存在，請提供以下資訊：

1. 瀏覽器開發者工具的 Network 截圖（包含 `/api/auth/line/callback` 請求）
2. 後端日誌（最近 50 行）：
   ```bash
   docker logs free_youtube_backend_prod --tail 50
   ```
3. 前端 Console 日誌
4. Cookie 狀態截圖（開發者工具 → Application → Cookies）
5. 環境變數設定（隱藏敏感資訊）
