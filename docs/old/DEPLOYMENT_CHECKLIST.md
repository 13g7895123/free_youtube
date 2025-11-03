# 部署檢查清單

## 📋 部署前檢查

### 環境變數配置

- [ ] 已複製 `.env.prod.example` 為 `.env.prod`
- [ ] 已生成並設置 `JWT_SECRET_KEY`（使用 `openssl rand -base64 64`）
- [ ] 已設置強密碼：`MYSQL_ROOT_PASSWORD`
- [ ] 已設置強密碼：`MYSQL_PASSWORD`
- [ ] 已設置 LINE Login 配置：
  - [ ] `LINE_LOGIN_CHANNEL_ID`
  - [ ] `LINE_LOGIN_CHANNEL_SECRET`
  - [ ] `LINE_LOGIN_CALLBACK_URL`（使用 HTTPS）
  - [ ] `FRONTEND_URL`（使用 HTTPS）
- [ ] 已設置 `COOKIE_DOMAIN`（`.mercylife.cc` 或留空）
- [ ] 已確認 `VITE_API_URL=/api`（相對路徑）
- [ ] 已確認 `AUTH_MODE=line`（正式環境）
- [ ] 已確認 `APP_ENV=production`

### 代碼檢查

- [ ] 前端沒有硬編碼的 `localhost` URL
- [ ] 所有 API 呼叫使用 `import.meta.env.VITE_API_URL`
- [ ] 後端 Cookie 配置正確（`httpOnly=true`, `secure=true` in production）
- [ ] CORS 配置包含正式域名

---

## 🚀 部署步驟

### 1. 清理舊環境

```bash
# 停止並移除舊容器
docker-compose -f docker-compose.prod.yml --env-file .env.prod down

# 清理前端構建緩存（可選）
rm -rf frontend/dist frontend/node_modules/.vite
```

- [ ] 舊容器已停止
- [ ] 舊緩存已清理（如需要）

### 2. 構建映像

```bash
# 構建前端（使用 --no-cache 確保環境變數正確）
docker-compose -f docker-compose.prod.yml --env-file .env.prod build --no-cache frontend

# 構建後端
docker-compose -f docker-compose.prod.yml --env-file .env.prod build backend
```

- [ ] Frontend 構建成功
- [ ] Backend 構建成功
- [ ] 無構建錯誤

### 3. 啟動服務

```bash
# 啟動所有服務
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d

# 查看服務狀態
docker-compose -f docker-compose.prod.yml ps
```

- [ ] MariaDB 服務啟動（健康狀態：healthy）
- [ ] Backend 服務啟動
- [ ] Frontend 服務啟動
- [ ] 所有服務 STATE 為 `Up`

---

## ✅ 部署後驗證

### 1. 服務健康檢查

```bash
# 檢查容器狀態
docker-compose -f docker-compose.prod.yml ps
```

- [ ] 所有容器狀態為 `Up (healthy)` 或 `Up`
- [ ] 無容器重啟循環（Restarting）

### 2. 環境變數驗證

```bash
# 檢查後端環境變數
docker exec free_youtube_backend_prod printenv | grep -E "CI_ENVIRONMENT|JWT_SECRET_KEY|COOKIE_DOMAIN"
```

- [ ] `CI_ENVIRONMENT=production`
- [ ] `JWT_SECRET_KEY` 不為空
- [ ] `COOKIE_DOMAIN` 已設置（或明確為空）

### 3. 前端配置驗證

```bash
# 檢查前端構建是否使用正確的 API URL
docker exec free_youtube_frontend_prod sh -c "grep -r 'localhost:8080' /usr/share/nginx/html/assets/*.js 2>/dev/null || echo 'PASS: 無硬編碼 localhost'"
```

- [ ] 輸出 `PASS: 無硬編碼 localhost`
- [ ] 前端靜態文件正確生成

### 4. API 連接測試

```bash
# 測試健康檢查 API
curl -i https://free.youtube.mercylife.cc/api/health

# 測試 LINE 登入 API（檢查 Set-Cookie）
curl -i https://free.youtube.mercylife.cc/api/auth/line/login
```

- [ ] API 可訪問（HTTP 200）
- [ ] 回應標頭包含 `Set-Cookie`
- [ ] Cookie 包含 `Secure` 屬性
- [ ] Cookie 包含 `HttpOnly` 屬性
- [ ] Cookie `Domain` 正確（如果設置了 `COOKIE_DOMAIN`）

### 5. 日誌檢查

```bash
# 查看後端日誌
docker-compose -f docker-compose.prod.yml logs --tail=50 backend | grep "AuthFilter"
```

**檢查項目：**
- [ ] `uri` 使用 HTTPS（`https://free.youtube.mercylife.cc/api/*`）
- [ ] **不是** `http://localhost:8080/api`
- [ ] `origin` 為 `https://free.youtube.mercylife.cc`
- [ ] `cookie_header` 不為 `(none)`（登入後）
- [ ] `has_access_token: true`（登入後）

**預期正確日誌：**
```json
{
  "uri": "https://free.youtube.mercylife.cc/api/auth/user",
  "origin": "https://free.youtube.mercylife.cc",
  "cookie_header": "access_token=xxx; refresh_token=xxx",
  "has_access_token": true
}
```

### 6. 功能測試

- [ ] 前端頁面可正常訪問（https://free.youtube.mercylife.cc）
- [ ] LINE Login 按鈕可點擊
- [ ] LINE 登入流程完整（重定向 → 授權 → 回調）
- [ ] 登入成功後可獲取使用者資訊
- [ ] 重新整理頁面後仍保持登入狀態（Cookie 有效）
- [ ] Token 自動刷新功能正常
- [ ] 登出功能正常

### 7. 安全檢查

```bash
# 檢查 SSL/TLS 配置（如果使用 Nginx SSL）
curl -I https://free.youtube.mercylife.cc

# 檢查安全標頭
curl -I https://free.youtube.mercylife.cc | grep -E "X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security"
```

- [ ] 使用 HTTPS（HTTP 301/302 重定向到 HTTPS）
- [ ] SSL 憑證有效
- [ ] 安全標頭正確設置（X-Frame-Options, X-Content-Type-Options）

---

## 🔧 疑難排解

### Cookie 無法傳遞

**症狀：**
```json
"cookie_header": "(none)"
```

**檢查清單：**
- [ ] 前端使用 HTTPS 訪問
- [ ] `CI_ENVIRONMENT=production`
- [ ] `withCredentials: true` 已設置（`frontend/src/services/api.js`）
- [ ] CORS 允許 credentials（`Access-Control-Allow-Credentials: true`）
- [ ] Cookie Domain 正確設置

**解決方案：**
```bash
# 重新構建前端（清除緩存）
docker-compose -f docker-compose.prod.yml build --no-cache frontend
docker-compose -f docker-compose.prod.yml restart
```

### 前端仍呼叫 localhost

**症狀：**
日誌顯示 `"uri": "http://localhost:8080/api"`

**檢查清單：**
- [ ] `.env.prod` 中 `VITE_API_URL=/api`
- [ ] 前端代碼無硬編碼 localhost
- [ ] 前端已重新構建（使用 `--no-cache`）

**解決方案：**
```bash
# 檢查前端代碼
grep -r "localhost:8080" frontend/src/

# 完全清理並重新構建
docker-compose -f docker-compose.prod.yml down -v
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d
```

### JWT Token 無效

**症狀：**
```
Token signature verification failed
```

**檢查清單：**
- [ ] `JWT_SECRET_KEY` 環境變數已設置
- [ ] `JWT_SECRET_KEY` 與生成 Token 時使用的一致

**解決方案：**
```bash
# 檢查環境變數
docker exec free_youtube_backend_prod printenv JWT_SECRET_KEY

# 如果為空或錯誤，更新 .env.prod 並重啟
docker-compose -f docker-compose.prod.yml restart backend
```

---

## 📊 監控指標

### 服務運行時間

```bash
docker-compose -f docker-compose.prod.yml ps
```

- [ ] 所有服務 Uptime > 5 分鐘（無重啟循環）

### 資源使用

```bash
docker stats --no-stream
```

- [ ] CPU 使用率 < 80%
- [ ] 記憶體使用率 < 80%
- [ ] 無 OOM (Out of Memory) 錯誤

### 日誌檢查

```bash
# 檢查錯誤日誌
docker-compose -f docker-compose.prod.yml logs --tail=100 | grep -i error
```

- [ ] 無重複錯誤訊息
- [ ] 無 CORS 錯誤
- [ ] 無資料庫連接錯誤

---

## ✅ 最終確認

- [ ] 所有部署前檢查項目已完成
- [ ] 所有部署步驟已執行
- [ ] 所有驗證測試已通過
- [ ] 已創建資料庫備份
- [ ] 已記錄部署時間和版本號
- [ ] 團隊成員已通知部署完成

---

## 📝 部署記錄

**部署日期：** _____________

**部署人員：** _____________

**版本號：** _____________

**Git Commit：** _____________

**環境變數備份位置：** _____________

**資料庫備份位置：** _____________

**備註：**
```
（記錄任何特殊配置或問題）
```

---

## 🔄 下次部署改進

**本次部署遇到的問題：**
```
（記錄遇到的問題和解決方案）
```

**需要改進的地方：**
```
（記錄可以優化的流程或配置）
```
