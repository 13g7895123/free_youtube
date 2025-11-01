# LINE 登入除錯 - 快速參考

## 🚀 快速開始

### 1. 部署除錯系統

```bash
./deploy-line-debug.sh
```

### 2. 測試 API

```bash
./test-line-debug-api.sh https://your-domain.com your-debug-key
```

## 📊 常用 API

### 查詢所有 logs

```bash
curl -X GET "https://your-domain.com/api/auth/line/logs?limit=50" \
  -H "X-Debug-Key: your-debug-key"
```

### 查詢錯誤

```bash
curl -X GET "https://your-domain.com/api/auth/line/errors?limit=20" \
  -H "X-Debug-Key: your-debug-key"
```

### 查詢特定 session

```bash
curl -X GET "https://your-domain.com/api/auth/line/logs?session_id=line_login_xxx" \
  -H "X-Debug-Key: your-debug-key"
```

### 查詢特定用戶

```bash
curl -X GET "https://your-domain.com/api/auth/line/logs?line_user_id=Uxxxx" \
  -H "X-Debug-Key: your-debug-key"
```

## 🔍 登入步驟流程

```
1. callback_start    → 開始處理 callback
2. validate_state    → 驗證 CSRF state
3. get_code          → 取得授權碼
4. get_token         → 換取 access token
5. get_profile       → 取得用戶資料
6. create_user       → 建立/更新用戶
7. create_token      → 生成應用 token
8. complete          → 完成登入
```

## 🐛 除錯步驟

1. **觸發一次登入** - 在正式環境使用 LINE 登入
2. **查詢最近的 logs**
   ```bash
   curl -X GET "https://your-domain.com/api/auth/line/logs?limit=100" \
     -H "X-Debug-Key: your-debug-key" | jq .
   ```
3. **找到錯誤的 session_id**
4. **查詢該 session 的所有步驟**
   ```bash
   curl -X GET "https://your-domain.com/api/auth/line/logs?session_id=xxx" \
     -H "X-Debug-Key: your-debug-key" | jq .
   ```
5. **分析 error_message 和 response_data**

## 📝 常見問題

### 問題: "無法建立用戶帳號"

**檢查步驟：**
1. 查詢 `step=create_user` 且 `status=error` 的記錄
2. 查看 `error_message` 欄位
3. 檢查 `response_data.last_query` 看 SQL 語句

**常見原因：**
- 資料庫連線問題
- 欄位驗證失敗
- 資料表不存在
- 權限不足

### 問題: "無法取得 LINE 授權"

**檢查步驟：**
1. 查詢 `step=get_token` 且 `status=error` 的記錄
2. 查看 `response_data.http_code` 和 `response_data.response_body`

**常見原因：**
- LINE_LOGIN_CHANNEL_ID 或 CHANNEL_SECRET 設定錯誤
- callback URL 不符
- 網路連線問題

### 問題: "無法取得用戶資料"

**檢查步驟：**
1. 查詢 `step=get_profile` 且 `status=error` 的記錄
2. 檢查 access_token 是否有效

**常見原因：**
- access_token 無效或過期
- LINE API 暫時無法使用

## 🔐 安全提醒

- **不要分享** DEBUG_API_KEY
- **定期更換** DEBUG_API_KEY
- **定期清理** 舊的 log 資料（建議保留 30 天）

## 📚 詳細文件

完整文件請參考：`docs/LINE_LOGIN_DEBUG.md`
