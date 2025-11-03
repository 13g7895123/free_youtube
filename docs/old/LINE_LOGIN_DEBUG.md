# LINE 登入除錯系統

## 概述

已建立完整的 LINE 登入日誌系統，記錄每個步驟的詳細資訊，方便除錯。

## 資料庫結構

### line_login_logs 表

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | BIGINT | 主鍵 |
| session_id | VARCHAR(100) | 本次登入的 session ID |
| step | VARCHAR(50) | 登入步驟 |
| status | ENUM | success, error, warning |
| line_user_id | VARCHAR(100) | LINE User ID |
| request_data | TEXT | 請求資料 (JSON) |
| response_data | TEXT | 回應資料 (JSON) |
| error_message | TEXT | 錯誤訊息 |
| ip_address | VARCHAR(45) | 使用者 IP |
| user_agent | VARCHAR(500) | 使用者瀏覽器 |
| created_at | DATETIME | 建立時間 |

### 登入步驟 (step)

1. **callback_start** - 開始處理 callback
2. **validate_state** - 驗證 CSRF state
3. **get_code** - 取得授權碼
4. **get_token** - 換取 access token
5. **get_profile** - 取得用戶資料
6. **create_user** - 建立或更新用戶
7. **create_token** - 生成應用 token
8. **complete** - 完成登入

## 部署步驟

### 1. 執行資料庫 Migration

```bash
# 進入後端容器
docker exec -it free_youtube-backend-1 bash

# 執行 SQL
mysql -h your_db_host -u your_db_user -p your_db_name < /var/www/html/database/migrations/2025-11-01-000001_create_line_login_logs.sql
```

或直接在資料庫中執行：

```sql
CREATE TABLE IF NOT EXISTS `line_login_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(100) NULL COMMENT '本次登入的 session ID',
  `step` VARCHAR(50) NOT NULL COMMENT '登入步驟',
  `status` ENUM('success', 'error', 'warning') NOT NULL DEFAULT 'success',
  `line_user_id` VARCHAR(100) NULL COMMENT 'LINE User ID',
  `request_data` TEXT NULL COMMENT '請求資料 (JSON)',
  `response_data` TEXT NULL COMMENT '回應資料 (JSON)',
  `error_message` TEXT NULL COMMENT '錯誤訊息',
  `ip_address` VARCHAR(45) NULL COMMENT '使用者 IP',
  `user_agent` VARCHAR(500) NULL COMMENT '使用者瀏覽器資訊',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_session_id` (`session_id`),
  INDEX `idx_line_user_id` (`line_user_id`),
  INDEX `idx_step` (`step`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. 設定環境變數

在 `.env` 或 `.env.prod` 中添加：

```env
# Debug API Key（用於保護除錯 API）
DEBUG_API_KEY=your-secret-debug-key-here
```

### 3. 重新部署應用

```bash
# 重新啟動後端容器以載入新程式碼
docker-compose -f docker-compose.prod.yml restart backend

# 或重新建置
docker-compose -f docker-compose.prod.yml up -d --build backend
```

## API 使用方式

### 1. 查詢所有 LINE 登入 logs

```bash
curl -X GET "https://your-domain.com/api/auth/line/logs?limit=50" \
  -H "X-Debug-Key: your-secret-debug-key-here"
```

**查詢參數：**
- `session_id` - 查詢特定 session 的所有步驟
- `line_user_id` - 查詢特定 LINE 用戶的登入記錄
- `status` - 過濾狀態 (success, error, warning)
- `limit` - 限制回傳筆數 (預設 50)

**範例：**

```bash
# 查詢特定 session 的所有步驟
curl -X GET "https://your-domain.com/api/auth/line/logs?session_id=line_login_12345" \
  -H "X-Debug-Key: your-secret-debug-key-here"

# 查詢特定用戶的登入記錄
curl -X GET "https://your-domain.com/api/auth/line/logs?line_user_id=U1234567890abcdef" \
  -H "X-Debug-Key: your-secret-debug-key-here"

# 只查詢錯誤
curl -X GET "https://your-domain.com/api/auth/line/logs?status=error&limit=20" \
  -H "X-Debug-Key: your-secret-debug-key-here"
```

### 2. 查詢最近的錯誤

```bash
curl -X GET "https://your-domain.com/api/auth/line/errors?limit=50" \
  -H "X-Debug-Key: your-secret-debug-key-here"
```

## 回應格式

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "session_id": "line_login_672456f1e8c8e9.12345678",
      "step": "create_user",
      "status": "error",
      "line_user_id": "U1234567890abcdef",
      "request_data": {
        "line_user_id": "U1234567890abcdef",
        "display_name": "測試用戶",
        "avatar_url": "https://...",
        "email": null
      },
      "response_data": {
        "last_query": "INSERT INTO users ..."
      },
      "error_message": "Insert user failed: {\"line_user_id\":\"...\"}",
      "ip_address": "123.456.789.0",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-11-01 12:34:56"
    }
  ],
  "count": 1
}
```

## 常見問題排查

### 問題 1: 看不到任何 log

**可能原因：**
1. 資料表未建立
2. 程式碼未更新

**解決方式：**
```bash
# 檢查資料表是否存在
mysql -e "SHOW TABLES LIKE 'line_login_logs';"

# 檢查容器是否使用最新程式碼
docker-compose -f docker-compose.prod.yml logs backend | tail -n 50
```

### 問題 2: API 回傳 403 Forbidden

**可能原因：**
- `X-Debug-Key` header 不正確

**解決方式：**
- 確認 `.env` 中的 `DEBUG_API_KEY` 設定
- 確認 curl 命令中的 header 值一致

### 問題 3: 某些步驟沒有記錄

**可能原因：**
- 在該步驟之前就已經失敗並回傳
- 資料庫寫入失敗

**解決方式：**
- 查看 CodeIgniter 的一般 log: `backend/writable/logs/log-*.log`
- 檢查資料庫連線狀態

## 定期清理

建議定期清理舊的 log 資料：

```bash
# 使用 PHP Spark 命令清理 30 天前的 log
php spark db:clean-logs 30
```

或使用 SQL：

```sql
DELETE FROM line_login_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## 安全注意事項

1. **保護 Debug API Key**
   - 不要在前端程式碼中使用
   - 定期更換
   - 僅在需要除錯時啟用

2. **敏感資訊**
   - access_token 已被隱藏，只記錄是否存在
   - 建議在生產環境穩定後，調整記錄的詳細程度

3. **定期清理**
   - 設定 cron job 定期清理舊資料
   - 避免資料表無限增長

## 測試流程

1. 在生產環境觸發一次 LINE 登入
2. 使用 API 查詢該次登入的所有步驟：
   ```bash
   # 查詢最近的 logs
   curl -X GET "https://your-domain.com/api/auth/line/logs?limit=100" \
     -H "X-Debug-Key: your-secret-debug-key-here" | jq .
   ```
3. 找到失敗的步驟，檢查 `error_message` 和 `response_data`
4. 根據錯誤訊息進行修正
