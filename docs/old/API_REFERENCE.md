# Free YouTube API 參考文件

## 目錄

- [概述](#概述)
- [認證機制](#認證機制)
- [API 端點總覽](#api-端點總覽)
  - [健康檢查](#健康檢查)
  - [認證相關](#認證相關)
  - [影片庫管理](#影片庫管理)
  - [通知系統](#通知系統)
  - [影片管理](#影片管理)
  - [播放清單](#播放清單)
  - [DEBUG 工具](#debug-工具)

---

## 概述

**Base URL**: `http://localhost:9204/api`
**預設回應格式**: JSON
**字元編碼**: UTF-8

---

## 認證機制

大部分 API 端點需要 JWT (JSON Web Token) 認證。認證 token 透過 LINE Login 或 Mock Login 取得。

### 在請求中使用 Token

```bash
curl -X GET http://localhost:9204/api/auth/user \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Token 類型

- **Access Token**: 用於 API 請求認證，有效期較短
- **Refresh Token**: 用於更新 Access Token，有效期較長

---

## API 端點總覽

### 健康檢查

#### GET /api/health

檢查 API 服務狀態。

**認證**: 不需要

**回應範例**:
```json
{
  "status": "ok",
  "timestamp": "2025-11-02 12:00:00"
}
```

---

### 認證相關

#### 1. LINE Login 登入

**端點**: `GET /api/auth/line/login`
**認證**: 不需要
**說明**: 導向 LINE OAuth 授權頁面

**回應**: 302 重導向至 LINE 授權頁面

---

#### 2. LINE Login 回調

**端點**: `GET /api/auth/line/callback`
**認證**: 不需要
**說明**: LINE OAuth 回調端點，處理授權碼並建立/更新用戶

**查詢參數**:
| 參數 | 類型 | 說明 |
|------|------|------|
| code | string | LINE 授權碼 |
| state | string | CSRF 防護 token |

**成功**: 重導向至前端並附帶 access_token 和 refresh_token
**失敗**: 重導向至前端並附帶錯誤訊息

---

#### 3. Mock Login (開發用)

**端點**: `POST /api/auth/mock/login`
**認證**: 不需要
**說明**: 開發環境使用的模擬登入

**請求參數**:
```json
{
  "email": "test@example.com",
  "name": "Test User"
}
```

**回應**:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "email": "test@example.com",
      "name": "Test User"
    }
  }
}
```

---

#### 4. 取得當前用戶資訊

**端點**: `GET /api/auth/user`
**認證**: 需要
**說明**: 取得當前登入用戶的資訊

**回應**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name",
    "line_user_id": "U1234567890abcdef",
    "created_at": "2025-11-01 10:00:00"
  }
}
```

---

#### 5. 登出

**端點**: `POST /api/auth/logout`
**認證**: 需要
**說明**: 登出當前用戶（撤銷 token）

**回應**:
```json
{
  "success": true,
  "message": "登出成功"
}
```

---

#### 6. 刷新 Token

**端點**: `POST /api/auth/refresh`
**認證**: 需要 Refresh Token
**說明**: 使用 refresh token 取得新的 access token

**請求參數**:
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**回應**:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

#### 7. 遷移訪客資料

**端點**: `POST /api/auth/migrate-guest-data`
**認證**: 需要
**說明**: 將訪客狀態下的資料遷移至已登入用戶

**請求參數**:
```json
{
  "guest_data": {
    "playlists": [],
    "videos": []
  }
}
```

**回應**:
```json
{
  "success": true,
  "message": "資料遷移成功"
}
```

---

### 影片庫管理

#### 1. 取得影片庫

**端點**: `GET /api/video-library`
**認證**: 需要
**說明**: 取得當前用戶的影片庫列表

**回應**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "video_id": "dQw4w9WgXcQ",
      "title": "Never Gonna Give You Up",
      "added_at": "2025-11-01 10:00:00"
    }
  ]
}
```

---

#### 2. 新增影片至影片庫

**端點**: `POST /api/video-library`
**認證**: 需要
**說明**: 將影片加入當前用戶的影片庫

**請求參數**:
```json
{
  "video_id": "dQw4w9WgXcQ",
  "title": "Never Gonna Give You Up"
}
```

**回應**:
```json
{
  "success": true,
  "message": "影片已加入影片庫",
  "data": {
    "id": 1,
    "video_id": "dQw4w9WgXcQ",
    "title": "Never Gonna Give You Up"
  }
}
```

---

#### 3. 從影片庫移除影片

**端點**: `DELETE /api/video-library/{id}`
**認證**: 需要
**說明**: 從影片庫移除指定影片

**路徑參數**:
| 參數 | 類型 | 說明 |
|------|------|------|
| id | integer | 影片庫項目 ID |

**回應**:
```json
{
  "success": true,
  "message": "影片已從影片庫移除"
}
```

---

### 通知系統

> 詳細說明請參考 [API_NOTIFICATIONS.md](./API_NOTIFICATIONS.md)

#### 1. 建立通知

**端點**: `POST /api/notifications`
**認證**: 不需要

#### 2. 更新通知狀態

**端點**: `PATCH /api/notifications/{id}/status`
**認證**: 不需要

#### 3. 取得通知列表

**端點**: `GET /api/notifications`
**認證**: 不需要

#### 4. 取得單一通知

**端點**: `GET /api/notifications/{id}`
**認證**: 不需要

---

### 影片管理

#### 1. 取得影片列表

**端點**: `GET /api/videos`
**認證**: 視設定而定
**說明**: 取得影片列表

**回應**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "video_id": "dQw4w9WgXcQ",
      "title": "Never Gonna Give You Up",
      "created_at": "2025-11-01 10:00:00"
    }
  ]
}
```

---

#### 2. 搜尋影片

**端點**: `GET /api/videos/search`
**認證**: 視設定而定
**說明**: 依關鍵字搜尋影片

**查詢參數**:
| 參數 | 類型 | 說明 |
|------|------|------|
| q | string | 搜尋關鍵字 |

**回應**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "video_id": "dQw4w9WgXcQ",
      "title": "Never Gonna Give You Up"
    }
  ]
}
```

---

#### 3. 建立影片

**端點**: `POST /api/videos`
**認證**: 視設定而定

**請求參數**:
```json
{
  "video_id": "dQw4w9WgXcQ",
  "title": "Never Gonna Give You Up",
  "description": "Official Music Video"
}
```

---

#### 4. 取得影片詳情

**端點**: `GET /api/videos/{id}`
**認證**: 視設定而定

---

#### 5. 更新影片

**端點**: `PUT /api/videos/{id}`
**認證**: 視設定而定

---

#### 6. 刪除影片

**端點**: `DELETE /api/videos/{id}`
**認證**: 視設定而定

---

#### 7. 檢查影片是否存在

**端點**: `POST /api/videos/check`
**認證**: 視設定而定

**請求參數**:
```json
{
  "video_id": "dQw4w9WgXcQ"
}
```

**回應**:
```json
{
  "success": true,
  "exists": true,
  "data": {
    "id": 1,
    "video_id": "dQw4w9WgXcQ"
  }
}
```

---

### 播放清單

#### 1. 取得播放清單列表

**端點**: `GET /api/playlists`
**認證**: 需要
**說明**: 取得當前用戶的所有播放清單

---

#### 2. 建立播放清單

**端點**: `POST /api/playlists`
**認證**: 需要

**請求參數**:
```json
{
  "name": "我的最愛",
  "description": "精選影片集"
}
```

---

#### 3. 取得播放清單詳情

**端點**: `GET /api/playlists/{id}`
**認證**: 需要

---

#### 4. 更新播放清單

**端點**: `PUT /api/playlists/{id}`
**認證**: 需要

---

#### 5. 刪除播放清單

**端點**: `DELETE /api/playlists/{id}`
**認證**: 需要

---

#### 6. 新增項目至播放清單

**端點**: `POST /api/playlists/{id}/items`
**認證**: 需要

**請求參數**:
```json
{
  "video_id": "dQw4w9WgXcQ",
  "position": 1
}
```

---

#### 7. 從播放清單移除項目

**端點**: `DELETE /api/playlists/{playlist_id}/items/{item_id}`
**認證**: 需要

---

#### 8. 重新排序播放清單

**端點**: `PUT /api/playlists/{id}/reorder`
**認證**: 需要

**請求參數**:
```json
{
  "items": [
    {"id": 1, "position": 1},
    {"id": 2, "position": 2}
  ]
}
```

---

### DEBUG 工具

> 詳細說明請參考 [LINE_LOGIN_DEBUG.md](./LINE_LOGIN_DEBUG.md)

#### 1. LINE Login 系統狀態

**端點**: `GET /api/debug/line-login/status`
**認證**: 不需要
**說明**: 查看 LINE Login 系統狀態總覽

---

#### 2. 最近的登入日誌

**端點**: `GET /api/debug/line-login/recent`
**認證**: 不需要
**說明**: 取得最近的 LINE Login 日誌

**查詢參數**:
| 參數 | 類型 | 說明 | 預設值 |
|------|------|------|--------|
| limit | integer | 限制筆數 | 50 |

---

#### 3. 錯誤日誌

**端點**: `GET /api/debug/line-login/errors`
**認證**: 不需要
**說明**: 取得所有錯誤日誌

**查詢參數**:
| 參數 | 類型 | 說明 | 預設值 |
|------|------|------|--------|
| limit | integer | 限制筆數 | 50 |

---

#### 4. Session 完整流程

**端點**: `GET /api/debug/line-login/session/{session_id}`
**認證**: 不需要
**說明**: 查看特定 session 的完整登入流程

---

#### 5. 所有 Sessions

**端點**: `GET /api/debug/line-login/sessions`
**認證**: 不需要
**說明**: 列出所有 LINE Login sessions

---

#### 6. 系統診斷資訊

**端點**: `GET /api/debug/line-login/diagnostic`
**認證**: 不需要
**說明**: 取得系統配置和診斷資訊

---

#### 7. 錯誤摘要統計

**端點**: `GET /api/debug/line-login/error-summary`
**認證**: 不需要
**說明**: 取得錯誤類型統計

---

#### 8. 測試連接配置

**端點**: `GET /api/debug/line-login/test-connection`
**認證**: 不需要
**說明**: 測試 LINE API 連接是否正常

---

## 錯誤處理

### 標準錯誤回應格式

```json
{
  "success": false,
  "message": "錯誤訊息描述",
  "errors": {
    "field_name": "欄位驗證錯誤訊息"
  }
}
```

### HTTP 狀態碼

| 狀態碼 | 說明 |
|--------|------|
| 200 OK | 請求成功 |
| 201 Created | 資源建立成功 |
| 400 Bad Request | 請求參數錯誤 |
| 401 Unauthorized | 未認證或 token 無效 |
| 403 Forbidden | 權限不足 |
| 404 Not Found | 資源不存在 |
| 422 Unprocessable Entity | 資料驗證失敗 |
| 500 Internal Server Error | 伺服器錯誤 |

---

## 使用範例

### 完整登入流程

```bash
# 1. 開啟 LINE Login 授權頁面
open http://localhost:9204/api/auth/line/login

# 2. 用戶授權後，會重導向回前端並附帶 token
# 前端會收到: /?access_token=xxx&refresh_token=yyy

# 3. 使用 token 取得用戶資訊
curl -X GET http://localhost:9204/api/auth/user \
  -H "Authorization: Bearer ACCESS_TOKEN"

# 4. 當 access token 過期時，使用 refresh token 更新
curl -X POST http://localhost:9204/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "REFRESH_TOKEN"}'
```

### 影片庫管理流程

```bash
# 1. 新增影片至影片庫
curl -X POST http://localhost:9204/api/video-library \
  -H "Authorization: Bearer ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "video_id": "dQw4w9WgXcQ",
    "title": "Never Gonna Give You Up"
  }'

# 2. 取得影片庫列表
curl -X GET http://localhost:9204/api/video-library \
  -H "Authorization: Bearer ACCESS_TOKEN"

# 3. 移除影片
curl -X DELETE http://localhost:9204/api/video-library/1 \
  -H "Authorization: Bearer ACCESS_TOKEN"
```

---

## 相關文件

- [LINE Login 設定指南](./LINE_LOGIN_SETUP.md)
- [LINE Login 除錯工具](./LINE_LOGIN_DEBUG.md)
- [LINE Login 故障排除](./LINE_LOGIN_TROUBLESHOOTING.md)
- [Notifications API 詳細說明](./API_NOTIFICATIONS.md)
- [資料庫遷移指南](./DATABASE_MIGRATION.md)
- [Nginx 代理設定](./NGINX_PROXY_FIX.md)

---

## 更新日誌

### 2025-11-02
- ✅ 新增 Notifications API
- ✅ 完善 LINE Login Debug API
- ✅ 修復 JWT token 生成問題
- ✅ 新增完整的 API 參考文件

### 2025-11-01
- ✅ 實作 LINE Login OAuth 2.0
- ✅ 新增影片庫管理功能
- ✅ 新增播放清單功能
