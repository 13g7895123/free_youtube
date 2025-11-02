# LINE Login Debug API - 快速開始

## ✅ 已完成部署

所有 Debug API 已經部署完成並正常運作！

## 🎯 關鍵資訊

**本地端口：** `http://localhost:9204`
**正式站（請提供）：** `https://free.youtube.mercylife.cc`

**API 基礎路徑：** `/api/debug/line-login`

---

## 🚀 8 個可用的 API 端點

### 1. 系統狀態總覽 ⭐
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/status"
```
**用途：** 一鍵查看所有統計、配置和最近錯誤

### 2. 最近的日誌
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/recent?limit=20&status=all"
```
**用途：** 查看最近的所有日誌（成功+失敗）

### 3. 錯誤日誌（詳細） ⭐
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/errors?limit=20&hours=24"
```
**用途：** 查看詳細錯誤，包含分類統計

### 4. Session 完整流程 ⭐
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/session/{sessionId}"
```
**用途：** 追蹤特定登入的完整流程

### 5. 所有 Sessions 列表
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/sessions?limit=20"
```
**用途：** 查看最近的所有登入嘗試

### 6. 系統診斷資訊 ⭐
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/diagnostic"
```
**用途：** 檢查 PHP、資料庫、環境變數等配置

### 7. 錯誤摘要統計
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/error-summary?days=7"
```
**用途：** 查看最常見的錯誤類型

### 8. 測試連接配置 ⭐
```bash
curl "https://free.youtube.mercylife.cc/api/debug/line-login/test-connection"
```
**用途：** 快速檢查所有必要配置是否正確

---

## 💡 診斷流程（給 Claude）

當你收到正式站 domain 後，請按以下順序診斷：

### 步驟 1：檢查系統狀態
```bash
curl "https://{domain}/api/debug/line-login/status"
```
**查看：**
- today.errors（今日錯誤數）
- config（配置是否正確）
- recent_errors（最近錯誤）

### 步驟 2：測試連接配置
```bash
curl "https://{domain}/api/debug/line-login/test-connection"
```
**確認：**
- Channel ID 和 Secret 已設定
- Callback URL 使用 HTTPS
- Frontend URL 正確

### 步驟 3：查看系統診斷
```bash
curl "https://{domain}/api/debug/line-login/diagnostic"
```
**檢查：**
- PHP 擴展是否完整
- 資料庫連接是否正常
- 環境變數是否正確
- JWT 配置是否完整

### 步驟 4：查看最近錯誤
```bash
curl "https://{domain}/api/debug/line-login/errors?limit=10&hours=1"
```
**分析：**
- 錯誤發生在哪個步驟
- 錯誤訊息內容
- 是否有規律（IP、時間等）

### 步驟 5：追蹤特定 Session
```bash
# 從步驟 4 取得 session_id
curl "https://{domain}/api/debug/line-login/session/{session_id}"
```
**了解：**
- 完整的登入流程
- 在哪一步失敗
- 失敗原因
- 持續時間

### 步驟 6：查看錯誤統計
```bash
curl "https://{domain}/api/debug/line-login/error-summary?days=7"
```
**識別：**
- 最常見的錯誤
- 是否為系統性問題
- 錯誤趨勢

---

## 📊 測試結果範例

### ✅ 本地測試已通過

```bash
# 1. 系統狀態
$ curl "http://localhost:9204/api/debug/line-login/status"
{
  "success": true,
  "data": {
    "stats": {
      "today": {"total_attempts": 4, "errors": 1},
      ...
    },
    "config": {
      "line_login_callback_url": "https://free.youtube.mercylife.cc/api/auth/line/callback",
      "has_channel_id": true,
      "has_channel_secret": true
    },
    ...
  }
}

# 2. 連接測試
$ curl "http://localhost:9204/api/debug/line-login/test-connection"
{
  "success": true,
  "data": {
    "channel_id": {"status": true, "message": "Channel ID 已設定"},
    "channel_secret": {"status": true, "message": "Channel Secret 已設定"},
    "callback_url": {
      "status": true,
      "message": "https://free.youtube.mercylife.cc/api/auth/line/callback",
      "is_https": true
    },
    "frontend_url": {"status": true, "message": "https://free.youtube.mercylife.cc"}
  },
  "summary": "所有配置正確"
}

# 3. 診斷資訊
$ curl "http://localhost:9204/api/debug/line-login/diagnostic"
{
  "success": true,
  "data": {
    "php": {"version": "8.1.33", "extensions": {"curl": true, ...}},
    "database": {"connected": true, "database": "free_youtube"},
    "environment": {
      "CI_ENVIRONMENT": "production",
      "LINE_LOGIN_CALLBACK_URL": "https://free.youtube.mercylife.cc/api/auth/line/callback",
      ...
    },
    ...
  }
}
```

---

## 🔧 常見問題對應

### 問題類型 1：302 重定向
**查詢：**
```bash
curl "https://{domain}/api/debug/line-login/sessions?limit=10"
```
**檢查：** 是否有 session 記錄，如果沒有表示 callback 沒被調用

### 問題類型 2：JSON parse error
**查詢：**
```bash
curl "https://{domain}/api/debug/line-login/recent?limit=10"
```
**檢查：** response_data 欄位，看實際返回的內容

### 問題類型 3：環境變數問題
**查詢：**
```bash
curl "https://{domain}/api/debug/line-login/diagnostic"
curl "https://{domain}/api/debug/line-login/test-connection"
```
**檢查：** 所有環境變數是否正確設定

### 問題類型 4：資料庫問題
**查詢：**
```bash
curl "https://{domain}/api/debug/line-login/diagnostic"
```
**檢查：** database.connected, tables 是否都為 true

---

## 📝 給用戶的使用說明

部署完成後，請提供正式站的 domain 給 Claude：

```
正式站 domain: https://free.youtube.mercylife.cc
```

Claude 會自動執行以下操作：
1. 查詢系統狀態
2. 檢查配置
3. 查看最近錯誤
4. 分析問題根源
5. 提供修復建議

**完全自動化，無需手動提供日誌！**

---

## ⚙️ API 特點

✅ **不需要認證** - 快速診斷
✅ **不含敏感資訊** - 安全
✅ **完整診斷資訊** - 一站式
✅ **即時資料** - 最新狀態
✅ **結構化輸出** - 易於分析

---

## 📦 已部署檔案

- ✅ `LineLoginDebug.php` - Controller
- ✅ `Routes.php` - 路由配置
- ✅ 所有檔案已部署到容器
- ✅ OPcache 已清除
- ✅ 所有 API 測試通過

---

**準備就緒！請提供正式站 domain 開始診斷 🚀**
