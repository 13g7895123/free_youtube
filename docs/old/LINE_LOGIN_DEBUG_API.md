# LINE Login Debug API 完整文件

**基礎 URL：** `https://your-domain.com/api/debug/line-login`

## 📊 API 端點總覽

### 1. 系統狀態總覽
**端點：** `GET /api/debug/line-login/status`

**說明：** 取得完整的系統狀態、統計資訊和配置檢查

**回應範例：**
```json
{
  "success": true,
  "data": {
    "stats": {
      "today": {
        "total_attempts": 45,
        "successful": 38,
        "errors": 7,
        "warnings": 0,
        "completed_logins": 35
      },
      "last_hour": {
        "total_attempts": 5,
        "errors": 1,
        "completed_logins": 4
      },
      "last_24h": {
        "total_attempts": 120,
        "errors": 15,
        "completed_logins": 95
      },
      "database": {
        "total_logs": 1523,
        "oldest_log": "2025-10-01 10:30:00",
        "newest_log": "2025-11-02 14:30:00"
      }
    },
    "config": {
      "line_login_callback_url": "https://your-domain.com/api/auth/line/callback",
      "frontend_url": "https://your-domain.com",
      "has_channel_id": true,
      "has_channel_secret": true,
      "auth_mode": "line",
      "ci_environment": "production"
    },
    "recent_errors": [
      {
        "id": 123,
        "session_id": "line_login_xxx",
        "step": "get_token",
        "error_message": "Failed to exchange code for access token",
        "created_at": "2025-11-02 14:25:00"
      }
    ],
    "timestamp": "2025-11-02 14:30:00"
  }
}
```

---

### 2. 最近的日誌
**端點：** `GET /api/debug/line-login/recent?limit=50&status=all`

**參數：**
- `limit` (可選): 筆數限制，預設 50，最大 200
- `status` (可選): 過濾狀態 (`success`, `error`, `warning`, `all`)

**說明：** 取得最近的所有日誌，包含成功和失敗的記錄

**回應範例：**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "session_id": "line_login_673569a4e2d7f8.12345678",
      "step": "complete",
      "status": "success",
      "line_user_id": "U1234567890abcdef",
      "request_data": null,
      "response_data": "{\"user_id\": 123}",
      "error_message": null,
      "ip_address": "203.0.113.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-11-02 14:20:00"
    }
  ],
  "count": 50,
  "filters": {
    "limit": 50,
    "status": "all"
  }
}
```

---

### 3. 錯誤日誌（詳細）
**端點：** `GET /api/debug/line-login/errors?limit=50&hours=24`

**參數：**
- `limit` (可選): 筆數限制，預設 50，最大 200
- `hours` (可選): 時間範圍（小時），預設 24

**說明：** 取得詳細的錯誤日誌，包含錯誤分類統計

**回應範例：**
```json
{
  "success": true,
  "data": [
    {
      "id": 789,
      "session_id": "line_login_xxx",
      "step": "get_token",
      "status": "error",
      "line_user_id": null,
      "request_data": "{\"code\": \"xxx\"}",
      "response_data": null,
      "error_message": "Failed to exchange code for access token",
      "ip_address": "203.0.113.2",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-11-02 14:15:00"
    }
  ],
  "count": 7,
  "error_types": {
    "get_token": 3,
    "get_profile": 2,
    "callback_start": 2
  },
  "filters": {
    "limit": 50,
    "hours": 24
  }
}
```

---

### 4. Session 完整流程
**端點：** `GET /api/debug/line-login/session/{sessionId}`

**說明：** 取得特定 session 的完整登入流程，包含分析

**回應範例：**
```json
{
  "success": true,
  "session_id": "line_login_673569a4e2d7f8.12345678",
  "data": [
    {
      "id": 100,
      "session_id": "line_login_673569a4e2d7f8.12345678",
      "step": "callback_start",
      "status": "success",
      "line_user_id": null,
      "request_data": "{\"code\":\"xxx\",\"state\":\"yyy\"}",
      "created_at": "2025-11-02 14:10:00"
    },
    {
      "id": 101,
      "step": "get_token",
      "status": "success",
      "created_at": "2025-11-02 14:10:02"
    },
    {
      "id": 102,
      "step": "complete",
      "status": "success",
      "created_at": "2025-11-02 14:10:05"
    }
  ],
  "analysis": {
    "total_steps": 3,
    "has_errors": false,
    "has_warnings": false,
    "completed": true,
    "failed_at_step": null,
    "duration_seconds": 5
  }
}
```

---

### 5. 所有 Sessions 列表
**端點：** `GET /api/debug/line-login/sessions?limit=20`

**參數：**
- `limit` (可選): 筆數限制，預設 20，最大 100

**說明：** 取得最近的所有 sessions，包含統計資訊

**回應範例：**
```json
{
  "success": true,
  "data": [
    {
      "session_id": "line_login_xxx1",
      "started_at": "2025-11-02 14:10:00",
      "ended_at": "2025-11-02 14:10:05",
      "steps_count": 8,
      "error_count": 0,
      "completed": 1,
      "line_user_id": "U1234567890abcdef",
      "ip_address": "203.0.113.1"
    },
    {
      "session_id": "line_login_xxx2",
      "started_at": "2025-11-02 14:05:00",
      "ended_at": "2025-11-02 14:05:02",
      "steps_count": 2,
      "error_count": 1,
      "completed": 0,
      "line_user_id": null,
      "ip_address": "203.0.113.2"
    }
  ],
  "count": 20
}
```

---

### 6. 系統診斷資訊
**端點：** `GET /api/debug/line-login/diagnostic`

**說明：** 取得完整的系統診斷資訊，用於排查環境問題

**回應範例：**
```json
{
  "success": true,
  "data": {
    "php": {
      "version": "8.1.33",
      "extensions": {
        "curl": true,
        "json": true,
        "mysqli": true,
        "openssl": true
      }
    },
    "database": {
      "connected": true,
      "database": "free_youtube",
      "platform": "MySQLi"
    },
    "tables": {
      "line_login_logs": true,
      "users": true,
      "user_tokens": true
    },
    "environment": {
      "CI_ENVIRONMENT": "production",
      "AUTH_MODE": "line",
      "LINE_LOGIN_CALLBACK_URL": "https://your-domain.com/api/auth/line/callback",
      "FRONTEND_URL": "https://your-domain.com",
      "has_channel_id": true,
      "has_channel_secret": true,
      "channel_id_length": 10,
      "secret_length": 32
    },
    "jwt": {
      "has_secret": true,
      "secret_length": 88,
      "access_expire": 900,
      "refresh_expire": 2592000
    },
    "server": {
      "software": "PHP 8.1.33 Development Server",
      "protocol": "HTTP/1.1",
      "time": "2025-11-02 14:30:00",
      "timezone": "Asia/Taipei"
    }
  }
}
```

---

### 7. 錯誤摘要統計
**端點：** `GET /api/debug/line-login/error-summary?days=7`

**參數：**
- `days` (可選): 天數範圍，預設 7，最大 30

**說明：** 取得最常見的錯誤類型和訊息

**回應範例：**
```json
{
  "success": true,
  "data": [
    {
      "step": "get_token",
      "error_message": "Failed to exchange code for access token",
      "count": 25,
      "last_occurred": "2025-11-02 14:15:00"
    },
    {
      "step": "get_profile",
      "error_message": "Failed to get user profile from LINE API",
      "count": 15,
      "last_occurred": "2025-11-02 13:45:00"
    }
  ],
  "count": 2,
  "period_days": 7
}
```

---

### 8. 測試連接配置
**端點：** `GET /api/debug/line-login/test-connection`

**說明：** 測試所有必要的環境變數是否正確設定

**回應範例：**
```json
{
  "success": true,
  "data": {
    "channel_id": {
      "status": true,
      "message": "Channel ID 已設定"
    },
    "channel_secret": {
      "status": true,
      "message": "Channel Secret 已設定"
    },
    "callback_url": {
      "status": true,
      "message": "https://your-domain.com/api/auth/line/callback",
      "is_https": true
    },
    "frontend_url": {
      "status": true,
      "message": "https://your-domain.com"
    }
  },
  "summary": "所有配置正確"
}
```

---

## 🔍 使用範例

### 快速診斷流程

1. **檢查系統狀態**
```bash
curl "https://your-domain.com/api/debug/line-login/status"
```

2. **如果有錯誤，查看錯誤詳情**
```bash
curl "https://your-domain.com/api/debug/line-login/errors?limit=10&hours=1"
```

3. **找出特定 session 並查看完整流程**
```bash
# 從 status 或 errors 的回應中取得 session_id
curl "https://your-domain.com/api/debug/line-login/session/line_login_xxx"
```

4. **檢查系統配置**
```bash
curl "https://your-domain.com/api/debug/line-login/diagnostic"
```

5. **測試連接配置**
```bash
curl "https://your-domain.com/api/debug/line-login/test-connection"
```

---

## 🛠️ 常見問題排查

### 問題：用戶無法登入

**步驟 1：** 檢查最近是否有錯誤
```bash
curl "https://your-domain.com/api/debug/line-login/errors?hours=1"
```

**步驟 2：** 查看錯誤摘要，找出最常見的問題
```bash
curl "https://your-domain.com/api/debug/line-login/error-summary?days=1"
```

**步驟 3：** 如果是配置問題，檢查診斷資訊
```bash
curl "https://your-domain.com/api/debug/line-login/diagnostic"
curl "https://your-domain.com/api/debug/line-login/test-connection"
```

### 問題：特定用戶回報問題

**步驟 1：** 取得最近的 sessions
```bash
curl "https://your-domain.com/api/debug/line-login/sessions?limit=50"
```

**步驟 2：** 找出該用戶的 session_id（通過 IP 或時間）

**步驟 3：** 查看完整流程
```bash
curl "https://your-domain.com/api/debug/line-login/session/{session_id}"
```

---

## 📋 回應格式

所有 API 都遵循統一的回應格式：

**成功回應：**
```json
{
  "success": true,
  "data": { ... }
}
```

**錯誤回應：**
```json
{
  "success": false,
  "message": "錯誤訊息"
}
```

---

## 🔐 安全注意事項

- 這些 API 目前**不需要認證**，方便快速診斷
- **不會返回敏感資訊**（如 Channel Secret、JWT Secret 的實際值）
- 只返回配置是否存在和長度資訊
- 如需加強安全性，可在路由中添加 `['filter' => 'auth']`

---

## 📊 資料保留政策

- 日誌會永久保留（除非手動清理）
- 建議每月清理 30 天前的舊日誌
- 可使用 `LineLoginLogModel::cleanOldLogs(30)` 方法清理

---

**最後更新：** 2025-11-02
**版本：** 1.0.0
