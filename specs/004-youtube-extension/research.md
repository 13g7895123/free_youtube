# Research & Technical Decisions: YouTube 瀏覽器擴充程式

**Date**: 2025-11-08
**Feature**: 004-youtube-extension

## 研究目標

為 YouTube 瀏覽器擴充程式確立技術選擇，解決以下關鍵問題：
1. Chrome 與 Firefox 擴充程式的跨平台相容性策略
2. LINE OAuth 2.0 在瀏覽器擴充程式中的實作方式
3. YouTube Data API 的整合與影片資訊擷取
4. Token 安全儲存與管理機制
5. 跨平台 URL 解析策略

---

## 決策 1: 擴充程式 Manifest 版本選擇

### 選擇
- **Chrome**: Manifest V3
- **Firefox**: Manifest V2（主要）+ V3 相容性（未來準備）

### 理由
- Chrome 已全面轉向 Manifest V3（2023 年後 V2 不再支援）
- Firefox 目前仍支援 Manifest V2，但正逐步遷移至 V3
- Manifest V3 的主要變更：
  - Background scripts → Service Workers
  - `chrome.*` API → Promise-based API
  - 更嚴格的內容安全政策（CSP）

### 考慮的替代方案
1. **僅使用 Manifest V2**: 不適用於 Chrome，已被淘汰
2. **僅使用 Manifest V3**: Firefox 支援尚未完全穩定，可能導致相容性問題

### 實作策略
維護兩個 `manifest.json` 檔案：
- `manifest-chrome.json` (Manifest V3)
- `manifest-firefox.json` (Manifest V2 with V3 準備)

構建腳本將根據目標平台複製對應的 manifest 檔案。

---

## 決策 2: LINE OAuth 2.0 整合方式

### 選擇
使用 **browser.identity API** 搭配 **OAuth 2.0 Authorization Code Flow**

### 理由
- `browser.identity.launchWebAuthFlow` 是瀏覽器擴充程式處理 OAuth 的標準方式
- 支援在新視窗中開啟 LINE OAuth 頁面，避免彈出視窗阻擋問題
- 可自動偵測 redirect URL 並擷取 authorization code
- Chrome 與 Firefox 皆支援此 API

### 流程
1. 使用者點擊「LINE 登入」→ `browser.identity.launchWebAuthFlow`
2. 開啟 LINE OAuth 授權頁面
3. 使用者完成授權 → LINE redirect 至 `https://<extension-id>.chromiumapp.org/callback`
4. 擴充程式擷取 authorization code
5. 透過後端 API exchange code 換取 access token 與 refresh token
6. 儲存 token 至 `browser.storage.local`

### 考慮的替代方案
1. **直接使用 LINE SDK**: 瀏覽器擴充程式環境不支援標準 SDK
2. **手動實作 OAuth 流程**: 複雜度高，且需處理 CORS 與安全性問題

### 安全性考量
- Redirect URI 必須註冊在 LINE Developers Console
- Access token 與 refresh token 加密後儲存在 `browser.storage.local`
- 使用 Web Crypto API 進行加密

---

## 決策 3: YouTube 影片資訊擷取方式

### 選擇
使用 **YouTube Data API v3** 擷取影片標題、縮圖、時長

### 理由
- 官方 API，穩定可靠
- 提供完整的影片中繼資料（title, thumbnails, duration）
- 免費配額：每日 10,000 units（單次 videos.list 查詢消耗 1 unit）
- 避免依賴 DOM 解析（YouTube 頁面結構經常變動）

### API 端點
```
GET https://www.googleapis.com/youtube/v3/videos?id={videoId}&part=snippet,contentDetails&key={API_KEY}
```

### 回傳範例
```json
{
  "items": [{
    "id": "dQw4w9WgXcQ",
    "snippet": {
      "title": "影片標題",
      "thumbnails": {
        "medium": { "url": "https://..." }
      }
    },
    "contentDetails": {
      "duration": "PT3M33S"
    }
  }]
}
```

### 考慮的替代方案
1. **DOM 解析 YouTube 頁面**: 不穩定，YouTube 頻繁更新 DOM 結構
2. **後端代理 API 呼叫**: 增加後端負載，且擴充程式無法離線工作

### 配額管理
- 每日 10,000 units 可支援 10,000 次影片查詢
- 若使用者超過配額，降級為僅儲存影片 ID，延後擷取中繼資料

---

## 決策 4: Token 儲存與管理

### 選擇
使用 **browser.storage.local** + **AES-GCM 加密**

### 理由
- `browser.storage.local` 是擴充程式的標準儲存機制
- 資料持久化，瀏覽器關閉後仍保留
- Chrome 與 Firefox 皆支援
- 加密確保 token 不會以明文儲存

### 加密策略
```javascript
// 使用 Web Crypto API
const encryptToken = async (token) => {
  const encoder = new TextEncoder();
  const data = encoder.encode(token);
  const key = await crypto.subtle.generateKey(
    { name: "AES-GCM", length: 256 },
    true,
    ["encrypt", "decrypt"]
  );
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const encrypted = await crypto.subtle.encrypt(
    { name: "AES-GCM", iv },
    key,
    data
  );
  return { encrypted, iv, key };
};
```

### Token 管理邏輯
- Access token 過期時間：儲存時記錄 `expiresAt = Date.now() + 3600000` (1 小時)
- Refresh token 過期時間：`expiresAt = Date.now() + 604800000` (7 天)
- 每次 API 呼叫前檢查 access token 是否過期，若過期則自動使用 refresh token 更新

### 考慮的替代方案
1. **明文儲存**: 安全性風險，不可接受
2. **SessionStorage**: 瀏覽器關閉後遺失，使用者體驗差
3. **Cookie**: 擴充程式無法直接存取網站 Cookie

---

## 決策 5: YouTube URL 解析策略

### 選擇
使用 **正則表達式** + **URL API** 組合解析

### 理由
- YouTube URL 格式穩定，適合正則表達式解析
- 支援多種 URL 格式：
  - `https://www.youtube.com/watch?v=VIDEO_ID`
  - `https://www.youtube.com/watch?v=VIDEO_ID&list=PLAYLIST_ID`
  - `https://youtu.be/VIDEO_ID`
  - `https://m.youtube.com/watch?v=VIDEO_ID`

### 解析邏輯
```javascript
function parseYouTubeURL(url) {
  // 使用 URL API 解析
  const urlObj = new URL(url);

  // 格式 1: youtube.com/watch?v=VIDEO_ID
  if (urlObj.hostname.includes('youtube.com') && urlObj.pathname === '/watch') {
    return urlObj.searchParams.get('v');
  }

  // 格式 2: youtu.be/VIDEO_ID
  if (urlObj.hostname === 'youtu.be') {
    return urlObj.pathname.slice(1); // 移除開頭的 '/'
  }

  return null;
}
```

### 考慮的替代方案
1. **純正則表達式**: 難以維護，且容易遺漏邊緣案例
2. **第三方套件**: 增加擴充程式大小，不符合輕量化原則

---

## 決策 6: 跨平台相容性策略

### 選擇
使用 **WebExtension Polyfill** 統一 API

### 理由
- Chrome 使用 `chrome.*` API（callback-based）
- Firefox 使用 `browser.*` API（Promise-based）
- WebExtension Polyfill 提供統一的 Promise-based API
- 自動處理平台差異，簡化開發

### 使用方式
```javascript
import browser from 'webextension-polyfill';

// 統一使用 Promise API
await browser.storage.local.set({ key: 'value' });
const data = await browser.storage.local.get('key');
```

### 考慮的替代方案
1. **手動處理平台差異**: 開發成本高，容易出錯
2. **僅支援單一平台**: 不符合需求（必須同時支援 Chrome 與 Firefox）

---

## 決策 7: 錯誤處理與重試機制

### 選擇
實作 **指數退避（Exponential Backoff）** 重試策略

### 理由
- 網路錯誤、API 限流等問題需要自動重試
- 指數退避避免在伺服器過載時加劇問題
- 最多重試 3 次，超過後提示使用者手動重試

### 實作
```javascript
async function retryWithBackoff(fn, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      const delay = Math.pow(2, i) * 1000; // 1s, 2s, 4s
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
}
```

### 考慮的替代方案
1. **固定間隔重試**: 可能在伺服器過載時加劇問題
2. **無重試機制**: 使用者體驗差，需手動重新操作

---

## 決策 8: 使用者介面設計

### 選擇
使用 **Vanilla JavaScript** + **CSS Grid/Flexbox**

### 理由
- 擴充程式介面簡單，不需要引入完整框架（如 Vue.js）
- 保持擴充程式輕量化（< 5MB）
- 原生 JavaScript 效能最佳，載入速度快

### UI 元件
- **Popup 視窗**: 320px x 400px
- **按鈕**: LINE 登入、加入播放庫、加入播放清單、設定
- **狀態顯示**: 登入狀態、操作成功/失敗提示
- **設定面板**: 播放清單模式選擇、預設播放清單設定

### 考慮的替代方案
1. **使用 Vue.js**: 增加擴充程式大小（~100KB），對簡單 UI 過度設計
2. **使用 UI 框架（如 Bootstrap）**: 同樣增加大小，且許多功能用不到

---

## 技術選擇摘要

| 技術領域 | 選擇 | 主要理由 |
|---------|------|----------|
| Manifest 版本 | Chrome: V3, Firefox: V2 | Chrome 強制 V3，Firefox 尚未完全遷移 |
| OAuth 整合 | browser.identity API | 標準方式，跨平台支援 |
| YouTube API | YouTube Data API v3 | 官方穩定，避免 DOM 解析 |
| Token 儲存 | browser.storage.local + AES-GCM | 安全且持久化 |
| URL 解析 | 正則 + URL API | 穩定且涵蓋所有格式 |
| 跨平台相容 | WebExtension Polyfill | 統一 API，簡化開發 |
| 錯誤處理 | 指數退避重試 | 平衡可靠性與伺服器負載 |
| UI 框架 | Vanilla JS + CSS | 輕量化，效能最佳 |

---

## 依賴套件清單

### 生產依賴
- `webextension-polyfill` (^0.10.0) - 跨平台 API 統一
- 無其他外部依賴（使用原生 Web APIs）

### 開發依賴
- `jest` (^29.0.0) - 單元測試框架
- `@types/jest` (^29.0.0) - Jest TypeScript 類型
- `@types/chrome` (^0.0.246) - Chrome API 類型
- `@types/firefox-webext-browser` (^111.0.0) - Firefox API 類型
- `web-ext` (^7.0.0) - Firefox 擴充程式開發工具
- `webpack` (^5.0.0) - 打包工具
- `webpack-cli` (^5.0.0) - Webpack CLI
- `copy-webpack-plugin` (^11.0.0) - 複製靜態資源

### API 金鑰需求
- **YouTube Data API Key**: 需在 Google Cloud Console 申請
- **LINE OAuth Client ID & Secret**: 需在 LINE Developers Console 申請

---

## 風險評估與緩解策略

| 風險 | 影響 | 緩解策略 |
|------|------|----------|
| YouTube Data API 配額不足 | 使用者無法取得影片資訊 | 降級為僅儲存影片 ID，延後擷取 |
| LINE OAuth redirect 失敗 | 使用者無法登入 | 提供錯誤訊息與重試按鈕 |
| 瀏覽器 API 版本變更 | 擴充程式失效 | 使用 Polyfill 抽象化，定期測試新版瀏覽器 |
| Token 被竊取 | 安全性風險 | 加密儲存 + 短期 access token (1 小時) |
| 跨瀏覽器相容性問題 | Firefox 或 Chrome 功能異常 | 自動化測試涵蓋兩個平台 |

---

## 下一步：Phase 1 設計

所有技術決策已完成，可進入 Phase 1：
1. 設計資料模型（data-model.md）
2. 定義 API 合約（contracts/）
3. 撰寫快速入門指南（quickstart.md）
