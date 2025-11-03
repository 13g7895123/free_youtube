# 正式環境部署指南

## 問題修正說明

### 發現的問題

在正式環境中，日誌顯示前端呼叫 `http://localhost:8080/api`，導致 httpOnly Cookie 無法正確傳遞。

**根本原因：**
1. `frontend/src/views/LineLoginLogs.vue` 中使用了錯誤的環境變數名稱 `VITE_API_BASE_URL`
2. 預設值被硬編碼為 `http://localhost:8080/api`
3. 缺少必要的後端環境變數（`COOKIE_DOMAIN`、`JWT_SECRET_KEY`）

### 修正內容

#### 1. 前端修正
- ✅ 修正 `LineLoginLogs.vue` 使用正確的環境變數 `VITE_API_URL`
- ✅ 預設值改為 `/api`（相對路徑）

#### 2. 後端環境變數補充
- ✅ 在 `.env.prod.example` 中加入 `JWT_SECRET_KEY`、`COOKIE_DOMAIN` 配置
- ✅ 在 `docker-compose.prod.yml` 中傳遞 `COOKIE_DOMAIN` 環境變數

#### 3. Cookie 安全性強化
- ✅ 更新 `backend/app/Config/Cookie.php`，從環境變數讀取配置
- ✅ 正式環境強制啟用 `secure=true`（僅 HTTPS）

---

## 部署前準備

### 1. 生成 JWT Secret Key

```bash
# 生成安全的 JWT secret key
openssl rand -base64 64
```

將生成的金鑰複製並保存，稍後會用到。

### 2. 準備 .env.prod 檔案

```bash
# 從範例檔案複製
cp .env.prod.example .env.prod

# 編輯配置
nano .env.prod
```

### 3. 必須修改的配置項目

#### 3.1 資料庫配置（必須修改）
```bash
MYSQL_ROOT_PASSWORD=請_設置_強密碼
MYSQL_PASSWORD=請_設置_強密碼
```

#### 3.2 JWT 配置（必須設置）
```bash
# 使用步驟 1 生成的 secret key
JWT_SECRET_KEY=你生成的_secret_key
```

#### 3.3 LINE Login 配置（必須設置）
```bash
LINE_LOGIN_CHANNEL_ID=你的_LINE_Channel_ID
LINE_LOGIN_CHANNEL_SECRET=你的_LINE_Channel_Secret
LINE_LOGIN_CALLBACK_URL=https://free.youtube.mercylife.cc/api/auth/line/callback
FRONTEND_URL=https://free.youtube.mercylife.cc
```

#### 3.4 Cookie Domain 配置（建議設置）
```bash
# 方案 1：設置為主域名（支援子域名共享 Cookie）
COOKIE_DOMAIN=.mercylife.cc

# 方案 2：留空（Cookie 僅綁定當前域名）
COOKIE_DOMAIN=
```

**說明：**
- 如果設為 `.mercylife.cc`，Cookie 可在所有子域名（如 `free.youtube.mercylife.cc`、`api.mercylife.cc`）共享
- 如果留空，Cookie 僅在當前域名有效

---

## 部署步驟

### 步驟 1：清理舊的構建

```bash
# 停止並移除舊容器
docker-compose -f docker-compose.prod.yml --env-file .env.prod down

# 清理前端構建緩存（可選）
rm -rf frontend/dist frontend/node_modules/.vite
```

### 步驟 2：重新構建前端

```bash
# 重新構建 frontend 映像（使用 --no-cache 確保環境變數正確傳遞）
docker-compose -f docker-compose.prod.yml --env-file .env.prod build --no-cache frontend
```

### 步驟 3：重新構建後端

```bash
# 重新構建 backend 映像
docker-compose -f docker-compose.prod.yml --env-file .env.prod build backend
```

### 步驟 4：啟動服務

```bash
# 啟動所有服務
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d

# 查看服務狀態
docker-compose -f docker-compose.prod.yml ps

# 查看日誌
docker-compose -f docker-compose.prod.yml logs -f
```

### 步驟 5：驗證部署

#### 5.1 檢查服務健康狀態
```bash
# 檢查所有容器是否正常運行
docker-compose -f docker-compose.prod.yml ps

# 預期輸出：所有服務的 STATE 應為 Up (healthy) 或 Up
```

#### 5.2 檢查前端 API 配置
```bash
# 進入前端容器
docker exec -it free_youtube_frontend_prod sh

# 檢查構建後的環境變數（應該看到 /api）
grep -r "VITE_API_URL" /usr/share/nginx/html/assets/*.js 2>/dev/null || echo "環境變數已正確注入"

# 退出容器
exit
```

#### 5.3 檢查後端環境變數
```bash
# 檢查 COOKIE_DOMAIN 和 JWT_SECRET_KEY 是否正確傳遞
docker exec free_youtube_backend_prod printenv | grep -E "COOKIE_DOMAIN|JWT_SECRET_KEY|CI_ENVIRONMENT"
```

預期輸出：
```
CI_ENVIRONMENT=production
JWT_SECRET_KEY=你的_secret_key
COOKIE_DOMAIN=.mercylife.cc（或為空）
```

#### 5.4 測試 API 連接
```bash
# 測試 API 健康檢查
curl -i https://free.youtube.mercylife.cc/api/health

# 測試登入流程（檢查 Set-Cookie 標頭）
curl -i https://free.youtube.mercylife.cc/api/auth/line/login
```

**檢查重點：**
- ✅ 回應標頭應包含 `Set-Cookie: access_token=...; Secure; HttpOnly`
- ✅ `Secure` 屬性應該存在（僅 HTTPS）
- ✅ `Domain` 屬性應該正確（如果設置了 `COOKIE_DOMAIN`）

#### 5.5 檢查日誌
```bash
# 查看後端日誌，確認 URI 使用 HTTPS
docker-compose -f docker-compose.prod.yml logs backend | grep "AuthFilter"
```

**預期日誌格式：**
```json
{
  "uri": "https://free.youtube.mercylife.cc/api/auth/user",
  "origin": "https://free.youtube.mercylife.cc",
  "cookie_header": "access_token=xxx; refresh_token=xxx"
}
```

**⚠️ 錯誤日誌（已修正）：**
```json
{
  "uri": "http://localhost:8080/api/auth/user",  // ❌ 錯誤
  "cookie_header": "(none)"  // ❌ Cookie 無法傳遞
}
```

---

## 疑難排解

### 問題 1：Cookie 仍無法傳遞

**症狀：**
```json
"cookie_header": "(none)"
```

**檢查項目：**
1. 確認前端使用 HTTPS 訪問
2. 確認 `CI_ENVIRONMENT=production`
3. 確認 Nginx 正確代理 `/api` 到後端
4. 檢查瀏覽器開發者工具 > Application > Cookies

**解決方案：**
```bash
# 重新構建前端（清除緩存）
docker-compose -f docker-compose.prod.yml build --no-cache frontend

# 重啟所有服務
docker-compose -f docker-compose.prod.yml restart
```

### 問題 2：JWT Token 無效

**症狀：**
```
Token signature verification failed
```

**原因：**
- `JWT_SECRET_KEY` 環境變數未設置或已更改

**解決方案：**
```bash
# 檢查環境變數
docker exec free_youtube_backend_prod printenv JWT_SECRET_KEY

# 如果為空，更新 .env.prod 並重啟
docker-compose -f docker-compose.prod.yml restart backend
```

### 問題 3：CORS 錯誤

**症狀：**
```
Access to XMLHttpRequest has been blocked by CORS policy
```

**檢查項目：**
1. 確認 `FRONTEND_URL` 環境變數正確設置
2. 確認後端 CorsFilter 包含正確的域名

**解決方案：**
```bash
# 檢查 FRONTEND_URL
docker exec free_youtube_backend_prod printenv FRONTEND_URL

# 應該輸出：https://free.youtube.mercylife.cc
```

### 問題 4：前端仍然呼叫 localhost

**症狀：**
日誌顯示 `uri: "http://localhost:8080/api"`

**原因：**
- 前端使用了舊的構建緩存
- 環境變數未正確傳遞

**解決方案：**
```bash
# 完全清理並重新構建
docker-compose -f docker-compose.prod.yml down -v
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d
```

---

## 部署後檢查清單

- [ ] 所有 Docker 容器正常運行（`docker-compose ps`）
- [ ] 資料庫連接成功（檢查後端日誌）
- [ ] JWT_SECRET_KEY 已設置且不為空
- [ ] COOKIE_DOMAIN 已設置（或明確留空）
- [ ] 前端使用 HTTPS 訪問
- [ ] API 請求使用 HTTPS（檢查後端日誌中的 URI）
- [ ] Cookie 正確傳遞（`cookie_header` 不為 `(none)`）
- [ ] Set-Cookie 標頭包含 `Secure; HttpOnly`
- [ ] LINE Login 回調 URL 使用 HTTPS
- [ ] 使用者登入功能正常
- [ ] Token 自動刷新功能正常

---

## 安全建議

### 1. SSL/TLS 配置

如果使用 Nginx 作為反向代理，請確保：
```nginx
# 強制使用 HTTPS
server {
    listen 80;
    server_name free.youtube.mercylife.cc;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name free.youtube.mercylife.cc;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # 其他配置...
}
```

### 2. 環境變數保護

```bash
# .env.prod 應該：
# 1. 被 .gitignore 排除
# 2. 僅開發者和部署伺服器有訪問權限
chmod 600 .env.prod
```

### 3. 定期更新密鑰

```bash
# 定期（每 3-6 個月）輪換 JWT Secret Key
openssl rand -base64 64

# 更新 .env.prod
# 重啟服務
docker-compose -f docker-compose.prod.yml restart backend
```

**注意：** 更新 JWT Secret Key 會使所有現有 Token 失效，使用者需要重新登入。

---

## 回滾計畫

如果部署後發現問題，可以快速回滾：

```bash
# 1. 停止當前服務
docker-compose -f docker-compose.prod.yml down

# 2. 恢復到之前的映像版本（如果有打 tag）
docker pull your-registry/free_youtube_frontend:previous-version
docker pull your-registry/free_youtube_backend:previous-version

# 3. 啟動舊版本
docker-compose -f docker-compose.prod.yml up -d
```

**建議：** 每次部署前，先打標籤保存當前映像：
```bash
docker tag free_youtube_frontend_prod:latest free_youtube_frontend:backup-$(date +%Y%m%d)
docker tag free_youtube_backend_prod:latest free_youtube_backend:backup-$(date +%Y%m%d)
```

---

## 監控與維護

### 日誌管理

```bash
# 查看即時日誌
docker-compose -f docker-compose.prod.yml logs -f

# 查看特定服務日誌
docker-compose -f docker-compose.prod.yml logs -f backend

# 查看最近 100 行日誌
docker-compose -f docker-compose.prod.yml logs --tail=100 backend
```

### 效能監控

```bash
# 查看容器資源使用情況
docker stats

# 查看特定容器資源
docker stats free_youtube_backend_prod
```

### 資料庫備份

```bash
# 定期備份資料庫
docker exec free_youtube_db_prod mysqldump \
  -u root -p${MYSQL_ROOT_PASSWORD} \
  free_youtube > backup_$(date +%Y%m%d).sql

# 恢復資料庫
docker exec -i free_youtube_db_prod mysql \
  -u root -p${MYSQL_ROOT_PASSWORD} \
  free_youtube < backup_20250103.sql
```

---

## 相關文件

- [.env.prod.example](.env.prod.example) - 環境變數範例
- [docker-compose.prod.yml](docker-compose.prod.yml) - 正式環境 Docker 配置
- [nginx.prod.conf](nginx.prod.conf) - Nginx 配置
- [backend/app/Config/Cookie.php](backend/app/Config/Cookie.php) - Cookie 配置

---

## 技術支援

如遇問題，請檢查：
1. Docker 容器日誌：`docker-compose logs`
2. Nginx 日誌：`docker exec free_youtube_frontend_prod cat /var/log/nginx/error.log`
3. 後端應用日誌：`backend/writable/logs/log-*.log`
