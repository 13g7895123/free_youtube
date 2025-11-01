# LINE Login 設定檢查清單

**Feature**: 003-line-login-auth
**Date**: 2025-11-01

## 前置條件

在開始實作前,請確認已完成以下 LINE Developers 設定:

### 1. 建立 LINE Login Channel

1. 前往 [LINE Developers Console](https://developers.line.biz/console/)
2. 建立新的 Provider (或使用現有的)
3. 在 Provider 下建立新的 Channel,選擇類型: **LINE Login**

### 2. 設定 Channel 基本資訊

- [ ] **Channel ID**: 記錄下來,稍後填入 `backend/.env`
- [ ] **Channel Secret**: 記錄下來,稍後填入 `backend/.env`
- [ ] **Channel Name**: 設定為專案名稱 (例如: YouTube Loop Player)

### 3. 設定 Callback URL

在 Channel 設定中,新增以下 Callback URL:

**開發環境**:
```
http://localhost:8080/api/auth/line/callback
```

**正式環境** (部署後):
```
https://yourdomain.com/api/auth/line/callback
```

### 4. 設定權限範圍 (Scopes)

確保已啟用以下權限:
- [x] `profile` - 取得使用者基本資訊 (顯示名稱、頭像)
- [x] `openid` - 取得 LINE User ID

### 5. 其他設定

- [ ] **Email address (選用)**: 若需要取得使用者 email,需額外申請權限
- [ ] **App Types**: 選擇 `Web app`

## 設定完成後

將以下資訊填入 `backend/.env`:

```env
# LINE Login 設定
LINE_LOGIN_CHANNEL_ID=你的_Channel_ID
LINE_LOGIN_CHANNEL_SECRET=你的_Channel_Secret
LINE_LOGIN_CALLBACK_URL=http://localhost:8080/api/auth/line/callback
```

## 測試方式

設定完成後,可透過以下方式測試:

1. 啟動後端服務
2. 訪問: `http://localhost:8080/api/auth/line/login`
3. 應該會自動重定向到 LINE 登入頁面
4. 授權後應該返回 callback URL

## 參考資源

- [LINE Login Documentation](https://developers.line.biz/en/docs/line-login/)
- [LINE Login API Reference](https://developers.line.biz/en/reference/line-login/)

---

**狀態**: ✅ 設定檢查完成後,可繼續執行實作任務
