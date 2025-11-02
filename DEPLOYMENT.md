# 部署指南

## 快速開始

### 開發環境（使用 Mock 認證）

```bash
# 1. 複製開發環境配置
cp .env.example .env

# 2. 啟動服務
docker compose -f docker-compose.prod.yml up -d

# 3. 訪問應用
# 前端: http://localhost:5173
# 後端: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

開發環境會自動使用 Mock 認證模式，無需設定 LINE Login。

---

### 正式環境（使用 LINE Login）

#### 步驟 1️⃣：準備 LINE Login Channel

1. 前往 [LINE Developers Console](https://developers.line.biz/console/)
2. 建立新的 **Provider**（如果還沒有）
3. 在 Provider 下建立 **LINE Login Channel**
4. 記錄以下資訊：
   - **Channel ID**（在 Basic settings）
   - **Channel Secret**（在 Basic settings）
5. 設定 **Callback URL**（在 LINE Login 標籤）：
   ```
   https://your-domain.com/api/auth/line/callback
   ```
6. 啟用以下 **Scopes**：
   - ✅ profile
   - ✅ openid
   - ✅ email（可選）

#### 步驟 2️⃣：配置環境變數

```bash
# 1. 複製正式環境配置範本
cp .env.prod.example .env.prod

# 2. 編輯 .env.prod
nano .env.prod
```

**必須修改的項目：**

```bash
# 資料庫密碼（強密碼！）
MYSQL_ROOT_PASSWORD=your_strong_root_password_here
MYSQL_PASSWORD=your_strong_app_password_here

# LINE Login 設定（從 LINE Developers Console 取得）
LINE_LOGIN_CHANNEL_ID=your_channel_id_here
LINE_LOGIN_CHANNEL_SECRET=your_channel_secret_here
LINE_LOGIN_CALLBACK_URL=https://your-domain.com/api/auth/line/callback
FRONTEND_URL=https://your-domain.com

# 確認認證模式為 line
AUTH_MODE=line
```

#### 步驟 3️⃣：部署應用

```bash
# 1. 使用 .env.prod 啟動服務
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d

# 2. 檢查容器狀態
docker compose -f docker-compose.prod.yml ps

# 3. 查看日誌確認啟動成功
docker logs free_youtube_backend_prod

# 4. 檢查 LINE Login 設定
docker logs free_youtube_backend_prod | grep -E "AUTH_MODE|LINE_LOGIN"
```

應該看到：
```
AUTH_MODE=line
LINE_LOGIN_CHANNEL_ID=your_channel_id
```

#### 步驟 4️⃣：測試 LINE Login

1. 訪問你的網站
2. 點擊「LINE 登入」按鈕
3. 應該重定向到 LINE 授權頁面
4. 授權後應該回到網站並顯示已登入狀態

---

## 環境變數說明

### 資料庫配置

| 變數 | 說明 | 預設值 |
|------|------|--------|
| `MYSQL_ROOT_PASSWORD` | MariaDB root 密碼 | - |
| `MYSQL_DATABASE` | 資料庫名稱 | `free_youtube` |
| `MYSQL_USER` | 應用程式使用者 | `app_user` |
| `MYSQL_PASSWORD` | 應用程式密碼 | - |
| `MYSQL_PORT` | 外部端口 | `3307` |

### 應用程式端口

| 變數 | 說明 | 預設值 |
|------|------|--------|
| `FRONTEND_PORT` | 前端端口 | `9104` |
| `BACKEND_PORT` | 後端端口 | `9204` |
| `PHPMYADMIN_PORT` | phpMyAdmin 端口 | `9304` |

### 認證設定

| 變數 | 說明 | 預設值 |
|------|------|--------|
| `AUTH_MODE` | 認證模式：`line` 或 `mock` | `line` |
| `MOCK_USER_ID` | Mock 模式使用者 ID | `1` |

### LINE Login 設定

| 變數 | 說明 | 必填 |
|------|------|------|
| `LINE_LOGIN_CHANNEL_ID` | LINE Login Channel ID | ✅ |
| `LINE_LOGIN_CHANNEL_SECRET` | LINE Login Channel Secret | ✅ |
| `LINE_LOGIN_CALLBACK_URL` | 回調 URL（需 HTTPS） | ✅ |
| `TOKEN_EXPIRE_SECONDS` | Token 過期時間（秒） | 可選（預設 30 天） |
| `FRONTEND_URL` | 前端 URL | ✅ |

---

## 常見問題

### Q1: LINE Login 按鈕沒反應？

**檢查步驟：**
1. 確認 `AUTH_MODE=line`
2. 檢查 `LINE_LOGIN_CHANNEL_ID` 是否正確
3. 查看瀏覽器開發者工具的 Console

### Q2: 登入後重定向失敗？

**檢查步驟：**
1. 確認 LINE Developers Console 的 Callback URL 設定正確
2. 確認 `LINE_LOGIN_CALLBACK_URL` 與 Console 設定一致
3. 確認使用 HTTPS（正式環境必須）

### Q3: Token 驗證失敗？

**檢查步驟：**
1. 確認資料庫中有 `users` 和 `user_tokens` 表
2. 查看後端日誌：`docker logs free_youtube_backend_prod`
3. 確認 Cookie 設定正確（開發環境 secure=false，正式環境 secure=true）

### Q4: 開發環境如何測試？

開發環境使用 **Mock 認證模式**，無需 LINE Developers 帳號：

```bash
# backend/.env
AUTH_MODE = mock
MOCK_USER_ID = 1

# frontend/.env.development
VITE_AUTH_MODE=mock
```

點擊「登入」會自動登入為 Mock 使用者。

---

## 安全建議

### ✅ 必須做的事

1. **修改預設密碼**
   - `MYSQL_ROOT_PASSWORD` 必須使用強密碼
   - `MYSQL_PASSWORD` 必須使用強密碼

2. **使用 HTTPS**
   - LINE Login 的 Callback URL 必須使用 HTTPS
   - Cookie 在正式環境會自動啟用 `secure` flag

3. **妥善保管 .env.prod**
   - 此檔案包含敏感資訊
   - 已在 `.gitignore` 中，不會提交到 Git
   - 建議使用環境變數或 secrets 管理工具

4. **環境隔離**
   - 開發環境使用 Mock 模式
   - 正式環境使用 LINE Login 模式
   - 使用不同的 LINE Channel（開發/正式）

### ❌ 不要做的事

1. ❌ 不要將 `.env.prod` 提交到版本控制
2. ❌ 不要在正式環境使用 Mock 模式
3. ❌ 不要在開發/測試環境使用正式環境的 LINE Channel
4. ❌ 不要使用弱密碼或預設密碼

---

## 維護操作

### 查看日誌

```bash
# 後端日誌
docker logs free_youtube_backend_prod

# 資料庫日誌
docker logs free_youtube_db_prod

# 前端日誌
docker logs free_youtube_frontend_prod

# 持續查看日誌
docker logs -f free_youtube_backend_prod
```

### 重新啟動服務

```bash
# 重啟所有服務
docker compose -f docker-compose.prod.yml --env-file .env.prod restart

# 重啟單一服務
docker compose -f docker-compose.prod.yml restart backend
```

### 更新應用

```bash
# 1. 停止服務
docker compose -f docker-compose.prod.yml down

# 2. 拉取最新代碼
git pull

# 3. 重新 build
docker compose -f docker-compose.prod.yml build

# 4. 啟動服務
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d
```

### 資料庫備份

```bash
# 匯出資料庫
docker exec free_youtube_db_prod mysqldump -uroot -p'your_password' free_youtube > backup.sql

# 匯入資料庫
docker exec -i free_youtube_db_prod mysql -uroot -p'your_password' free_youtube < backup.sql
```

---

## 架構說明

```
┌─────────────────────────────────────────┐
│          使用者瀏覽器                    │
└────────────┬────────────────────────────┘
             │
             │ HTTPS
             │
┌────────────▼────────────────────────────┐
│       Frontend (Nginx + Vue.js)         │
│       Port: 9104                        │
└────────────┬────────────────────────────┘
             │
             │ /api (proxy)
             │
┌────────────▼────────────────────────────┐
│    Backend (CodeIgniter 4 + PHP 8.1)   │
│    Port: 9204                           │
│    - AuthFilter                         │
│    - LINE Login OAuth 2.0               │
└────────────┬────────────────────────────┘
             │
             │ MySQL Protocol
             │
┌────────────▼────────────────────────────┐
│       Database (MariaDB 10.6)           │
│       Port: 3307 (external)             │
│       - users                           │
│       - user_tokens                     │
│       - playlists                       │
│       - videos                          │
└─────────────────────────────────────────┘
```

---

## 支援

如有問題，請檢查：
1. [後端日誌](#查看日誌)
2. [常見問題](#常見問題)
3. LINE Developers Console 的設定

**LINE Login 相關資源：**
- [LINE Login 文件](https://developers.line.biz/en/docs/line-login/)
- [LINE Developers Console](https://developers.line.biz/console/)
