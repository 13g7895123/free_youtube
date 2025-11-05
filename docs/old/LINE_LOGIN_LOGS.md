# LINE Login 日誌系統使用說明

## 📊 功能概述

LINE Login 日誌系統會自動記錄所有 `/api/auth/line/callback` 的請求和處理過程，用於：
- 排查登入問題
- 追蹤用戶登入流程
- 監控錯誤發生狀況
- 分析登入成功率

## 🗄️ 資料表結構

**表名：** `line_login_logs`

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | INT | 自動遞增主鍵 |
| session_id | VARCHAR(128) | 登入會話 ID（每次登入流程唯一） |
| step | VARCHAR(50) | 步驟名稱 |
| status | ENUM | success/error/warning |
| line_user_id | VARCHAR(255) | LINE 使用者 ID（如果可取得） |
| request_data | TEXT | 請求資料（JSON） |
| response_data | TEXT | 回應資料（JSON） |
| error_message | TEXT | 錯誤訊息 |
| ip_address | VARCHAR(45) | 使用者 IP |
| user_agent | TEXT | 使用者瀏覽器資訊 |
| created_at | DATETIME | 建立時間 |

## 📝 記錄的步驟 (Step)

系統會在以下步驟記錄日誌：

1. **callback_start** - 進入 callback 端點
2. **validate_state** - 驗證 CSRF state
3. **get_code** - 取得授權碼
4. **get_token** - 用授權碼換取 access token
5. **get_profile** - 取得 LINE 用戶資料
6. **create_user** - 建立或更新用戶
7. **create_token** - 生成應用 token
8. **complete** - 流程完成

## 🔍 查詢 API

### 1. 查詢最近的錯誤日誌

**端點：** `GET /api/auth/line/logs/errors`

**參數：**
- `limit` (可選): 筆數限制，預設 50，最大 100

**範例：**
```bash
curl "http://localhost:8080/api/auth/line/logs/errors?limit=20"
```

**回應：**
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "session_id": "test_session_002",
      "step": "callback_start",
      "status": "error",
      "line_user_id": null,
      "request_data": null,
      "response_data": null,
      "error_message": "User cancelled: access_denied - 使用者取消授權",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-11-02 01:29:53"
    }
  ],
  "count": 1
}
```

### 2. 查詢特定會話的所有日誌

**端點：** `GET /api/auth/line/logs/session/{sessionId}`

**範例：**
```bash
curl "http://localhost:8080/api/auth/line/logs/session/line_login_673569a4e2d7f8.12345678"
```

**回應：**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "session_id": "line_login_673569a4e2d7f8.12345678",
      "step": "callback_start",
      "status": "success",
      ...
    },
    {
      "id": 2,
      "session_id": "line_login_673569a4e2d7f8.12345678",
      "step": "get_token",
      "status": "success",
      ...
    }
  ],
  "count": 2,
  "session_id": "line_login_673569a4e2d7f8.12345678"
}
```

### 3. 查詢特定 LINE 使用者的登入歷史

**端點：** `GET /api/auth/line/logs/user/{lineUserId}`

**參數：**
- `limit` (可選): 筆數限制，預設 50，最大 100

**範例：**
```bash
curl "http://localhost:8080/api/auth/line/logs/user/U1234567890abcdef?limit=10"
```

**回應：**
```json
{
  "success": true,
  "data": [...],
  "count": 10,
  "line_user_id": "U1234567890abcdef"
}
```

## 🔧 直接查詢資料庫

### 查看最近的錯誤

```sql
SELECT
  id,
  session_id,
  step,
  status,
  error_message,
  ip_address,
  created_at
FROM line_login_logs
WHERE status = 'error'
ORDER BY created_at DESC
LIMIT 20;
```

### 查看特定 session 的完整流程

```sql
SELECT
  step,
  status,
  error_message,
  created_at
FROM line_login_logs
WHERE session_id = 'line_login_673569a4e2d7f8.12345678'
ORDER BY id ASC;
```

### 統計登入成功率

```sql
SELECT
  DATE(created_at) as date,
  COUNT(*) as total_attempts,
  SUM(CASE WHEN step = 'complete' AND status = 'success' THEN 1 ELSE 0 END) as successful_logins,
  ROUND(SUM(CASE WHEN step = 'complete' AND status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT session_id), 2) as success_rate
FROM line_login_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### 查看最常見的錯誤

```sql
SELECT
  step,
  LEFT(error_message, 100) as error,
  COUNT(*) as count
FROM line_login_logs
WHERE status = 'error'
GROUP BY step, error_message
ORDER BY count DESC
LIMIT 10;
```

## 🛠️ 使用 Docker 指令查詢

### 查看最近的錯誤

```bash
docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
SELECT id, session_id, step, status, LEFT(error_message, 80) as error, created_at
FROM line_login_logs
WHERE status = 'error'
ORDER BY id DESC
LIMIT 10;
"
```

### 查看特定 session

```bash
docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
SELECT step, status, error_message, created_at
FROM line_login_logs
WHERE session_id = 'YOUR_SESSION_ID'
ORDER BY id ASC;
"
```

### 查看今天的所有登入嘗試

```bash
docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e "
SELECT COUNT(*) as total,
       SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) as success,
       SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as errors
FROM line_login_logs
WHERE DATE(created_at) = CURDATE();
"
```

## 🧹 維護

### 清理 30 天前的舊日誌

可以使用 Model 的方法：

```php
$lineLoginLogModel = new \App\Models\LineLoginLogModel();
$deletedCount = $lineLoginLogModel->cleanOldLogs(30);
```

或直接執行 SQL：

```sql
DELETE FROM line_login_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## 📋 常見問題排查

### 問題：看到 JSON parse error

查詢日誌找出問題：

```bash
curl "http://localhost:8080/api/auth/line/logs/errors?limit=10"
```

查看最近的錯誤，檢查 `request_data` 和 `error_message` 欄位。

### 問題：找不到特定用戶的登入記錄

```bash
curl "http://localhost:8080/api/auth/line/logs/user/{LINE_USER_ID}"
```

### 問題：想追蹤完整的登入流程

從瀏覽器的開發者工具中找到 callback URL，例如：
```
/api/auth/line/callback?code=XXX&state=YYY
```

然後從日誌中找到對應的 session_id（日誌的第一筆記錄會包含），再查詢該 session 的所有步驟：

```bash
curl "http://localhost:8080/api/auth/line/logs/session/{SESSION_ID}"
```

## 🎯 下一步

當用戶回報登入問題時：

1. **記下時間** - 記錄發生問題的大約時間
2. **查詢錯誤日誌** - 使用 `/api/auth/line/logs/errors` 找出該時間段的錯誤
3. **追蹤完整流程** - 使用 session_id 查看完整的登入流程
4. **分析錯誤** - 檢查 `error_message` 和 `request_data` 找出原因

## ⚙️ 環境變數（無需額外設定）

日誌系統會自動運作，但確保以下環境變數已設定：

```env
# 必要
LINE_LOGIN_CHANNEL_ID=your_channel_id
LINE_LOGIN_CHANNEL_SECRET=your_channel_secret
LINE_LOGIN_CALLBACK_URL=https://your-domain.com/api/auth/line/callback

# 可選
FRONTEND_URL=https://your-domain.com
```

---

**最後更新：** 2025-11-02
**版本：** 1.0.0
