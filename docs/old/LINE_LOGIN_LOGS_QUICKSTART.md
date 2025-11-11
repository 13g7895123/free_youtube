# LINE Login 日誌系統 - 快速開始

## ✅ 系統已完成部署

LINE Login 日誌系統已經完成部署並開始記錄所有登入流程。

## 🚀 快速查看日誌

### 方法 1: 使用便利腳本（推薦）

```bash
# 查看說明
./check_line_logs.sh help

# 查看最近的錯誤
./check_line_logs.sh errors 10

# 查看特定 session 的完整流程
./check_line_logs.sh session line_login_673569a4e2d7f8.12345678

# 查看特定用戶的登入歷史
./check_line_logs.sh user U1234567890abcdef 20

# 查看今日統計
./check_line_logs.sh db-stats

# 查看最近的所有記錄
./check_line_logs.sh db-recent 20
```

### 方法 2: 使用 API

```bash
# 查詢錯誤日誌
curl "http://localhost:8080/api/auth/line/logs/errors?limit=10"

# 查詢特定 session
curl "http://localhost:8080/api/auth/line/logs/session/line_login_673569a4e2d7f8.12345678"

# 查詢特定用戶
curl "http://localhost:8080/api/auth/line/logs/user/U1234567890abcdef?limit=20"
```

### 方法 3: 直接查詢資料庫

```bash
# 查看最近的錯誤
docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e \
  "SELECT * FROM line_login_logs WHERE status='error' ORDER BY id DESC LIMIT 10;"

# 查看今天的所有記錄
docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -e \
  "SELECT * FROM line_login_logs WHERE DATE(created_at)=CURDATE() ORDER BY id DESC;"
```

## 📊 現在會記錄什麼？

每次用戶點擊 LINE 登入並進入 `/api/auth/line/callback` 時，系統會自動記錄：

1. **callback_start** - 收到 callback 請求
   - 記錄所有查詢參數（code, state）
   - 記錄 IP 和 User Agent

2. **validate_state** - 驗證 CSRF token（如果失敗）

3. **get_token** - 用授權碼換取 access token
   - 記錄 LINE API 的回應

4. **get_profile** - 取得用戶資料
   - 記錄 LINE User ID
   - 記錄用戶資料

5. **create_user** - 建立或更新用戶（如果失敗）

6. **create_token** - 生成應用 JWT token（如果失敗）

7. **complete** - 登入流程完成
   - 記錄最終的 user_id
   - 記錄是否為恢復的帳號

## 🔍 排查問題流程

當用戶回報登入問題時：

### 步驟 1: 記下時間和大概狀況

請用戶提供：
- 發生問題的時間（例如：11:30 AM）
- 是否看到任何錯誤訊息
- 是在哪一步出錯（點擊登入後？LINE 授權後？）

### 步驟 2: 查詢錯誤日誌

```bash
# 查看最近一小時的錯誤
./check_line_logs.sh errors 50
```

找出該時間點附近的錯誤記錄。

### 步驟 3: 追蹤完整流程

從錯誤日誌中找到 `session_id`，然後查看完整流程：

```bash
./check_line_logs.sh session <session_id>
```

這會顯示該登入嘗試的每個步驟。

### 步驟 4: 分析錯誤原因

檢查以下常見問題：

#### 錯誤：`State mismatch - CSRF validation failed`
**原因：** Session 過期或 Cookie 被清除
**解決：** 請用戶重新點擊登入按鈕

#### 錯誤：`User cancelled: access_denied`
**原因：** 用戶在 LINE 授權頁面點了取消
**解決：** 這是正常的，用戶可以重新登入

#### 錯誤：`Failed to exchange code for access token`
**原因：** LINE API 錯誤或網路問題
**解決：** 檢查 LINE_LOGIN_CHANNEL_ID 和 SECRET 是否正確

#### 錯誤：`Failed to get user profile from LINE API`
**原因：** LINE API 無法返回用戶資料
**解決：** 檢查 LINE API 狀態，或 access_token 是否有效

#### 錯誤：`Failed to create or update user in database`
**原因：** 資料庫寫入失敗
**解決：** 檢查資料庫連線和 users 表結構

#### 錯誤：`Failed to generate authentication token`
**原因：** JWT 生成失敗
**解決：** 檢查 JWT_SECRET_KEY 是否已設定

## 📈 監控和統計

### 查看今日登入統計

```bash
./check_line_logs.sh db-stats
```

這會顯示：
- 總共嘗試次數
- 成功次數
- 錯誤次數
- 完成登入的次數
- 最常見的錯誤

### 定期清理舊日誌

建議每月清理一次舊日誌：

```bash
docker exec free_youtube_backend_prod php -r "
require '/var/www/html/vendor/autoload.php';
\$model = new \App\Models\LineLoginLogModel();
\$deleted = \$model->cleanOldLogs(30);
echo \"Cleaned \$deleted old logs\\n\";
"
```

## 🎯 在正式站使用

### 查看正式站的錯誤

正式站使用相同的系統，但需要連接到正式站的資料庫。

在正式站伺服器上執行：

```bash
# 查看最近的錯誤
./check_line_logs.sh errors 20

# 查看今日統計
./check_line_logs.sh db-stats
```

或者透過 API（確保有防火牆保護）：

```bash
curl "https://your-domain.com/api/auth/line/logs/errors?limit=20"
```

## 📝 欄位說明

| 欄位 | 說明 | 範例 |
|------|------|------|
| session_id | 每次登入流程的唯一 ID | line_login_673569a4e2d7f8.12345678 |
| step | 當前步驟名稱 | callback_start, get_token, complete |
| status | 狀態 | success, error, warning |
| line_user_id | LINE 用戶 ID（如果已取得） | U1234567890abcdef |
| request_data | 請求資料（JSON） | {"code": "abc123"} |
| response_data | 回應資料（JSON） | {"has_access_token": true} |
| error_message | 錯誤訊息 | Failed to get LINE access token |
| ip_address | 用戶 IP | 192.168.1.100 |
| user_agent | 瀏覽器資訊 | Mozilla/5.0... |
| created_at | 記錄時間 | 2025-11-02 14:30:45 |

## ⚙️ 自動化建議

### 設定每日統計郵件

可以設定 cron job 每天發送統計報告：

```bash
# 編輯 crontab
crontab -e

# 添加：每天早上 9 點發送統計
0 9 * * * /path/to/check_line_logs.sh db-stats | mail -s "LINE Login Daily Stats" admin@example.com
```

### 監控錯誤率

設定錯誤率警報：

```bash
# 如果一小時內錯誤超過 10 次，發送警報
*/30 * * * * [ $(docker exec free_youtube_db_prod mariadb -u root -psecret free_youtube -se "SELECT COUNT(*) FROM line_login_logs WHERE status='error' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)") -gt 10 ] && echo "LINE Login error rate high" | mail -s "Alert" admin@example.com
```

## 🔗 相關文件

- [完整文件](./LINE_LOGIN_LOGS.md) - 詳細的 API 說明和 SQL 查詢範例
- [JWT 驗證報告](./JWT_VERIFICATION_REPORT.md) - JWT 實作驗證報告

---

**系統版本：** 1.0.0
**部署日期：** 2025-11-02
**下次更新：** 視需求而定
