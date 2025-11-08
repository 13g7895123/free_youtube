# Quick Start: YouTube 瀏覽器擴充程式開發指南

**Date**: 2025-11-08
**Feature**: 004-youtube-extension

## 目標

本指南幫助開發者快速建立並測試 YouTube 瀏覽器擴充程式的開發環境。

---

## 前置條件

### 必要工具
- Node.js 16.x 或更高版本
- npm 或 yarn
- Chrome 瀏覽器（用於開發與測試）
- Firefox 瀏覽器（用於跨平台測試）

### API 金鑰申請
1. **YouTube Data API Key**
   - 前往 [Google Cloud Console](https://console.cloud.google.com/)
   - 啟用 YouTube Data API v3
   - 建立 API 金鑰並記錄

2. **LINE OAuth 憑證**
   - 前往 [LINE Developers Console](https://developers.line.biz/console/)
   - 建立 LINE Login channel
   - 記錄 Channel ID 與 Channel Secret
   - 設定 Callback URL: `https://<extension-id>.chromiumapp.org/callback`

---

## 專案初始化

### 1. 建立專案目錄

```bash
cd /home/jarvis/project/idea/free_youtube
mkdir -p browser-extension
cd browser-extension
```

### 2. 初始化 npm 專案

```bash
npm init -y
```

### 3. 安裝依賴

```bash
# 生產依賴
npm install webextension-polyfill

# 開發依賴
npm install --save-dev \
  jest \
  @types/jest \
  @types/chrome \
  @types/firefox-webext-browser \
  web-ext \
  webpack \
  webpack-cli \
  copy-webpack-plugin
```

### 4. 建立目錄結構

```bash
mkdir -p src/{popup,services,utils,background} icons tests/{unit,integration}
```

---

## 環境變數設定

### 1. 建立 `.env` 檔案

```bash
# browser-extension/.env
YOUTUBE_API_KEY=your_youtube_api_key_here
LINE_CHANNEL_ID=your_line_channel_id_here
LINE_REDIRECT_URI=https://your-extension-id.chromiumapp.org/callback
```

**⚠️ 重要**: 將 `.env` 加入 `.gitignore`，避免洩漏 API 金鑰

```bash
echo ".env" >> .gitignore
```

### 2. 建立環境變數載入工具

```javascript
// src/utils/config.js
export const config = {
  youtubeApiKey: process.env.YOUTUBE_API_KEY || '',
  lineChannelId: process.env.LINE_CHANNEL_ID || '',
  lineRedirectUri: process.env.LINE_REDIRECT_URI || '',
  backendApiUrl: process.env.BACKEND_API_URL || 'http://localhost:8080/v1'
};
```

---

## 建立基本檔案

### 1. Manifest 檔案（Chrome）

```json
// manifest-chrome.json
{
  "manifest_version": 3,
  "name": "YouTube Video Manager",
  "version": "1.0.0",
  "description": "快速將 YouTube 影片加入播放庫或播放清單",
  "permissions": [
    "storage",
    "tabs",
    "identity"
  ],
  "host_permissions": [
    "https://www.youtube.com/*",
    "https://www.googleapis.com/*"
  ],
  "action": {
    "default_popup": "src/popup/popup.html",
    "default_icon": {
      "16": "icons/icon-16.png",
      "48": "icons/icon-48.png",
      "128": "icons/icon-128.png"
    }
  },
  "background": {
    "service_worker": "src/background/background.js"
  },
  "icons": {
    "16": "icons/icon-16.png",
    "48": "icons/icon-48.png",
    "128": "icons/icon-128.png"
  }
}
```

### 2. Popup HTML

```html
<!-- src/popup/popup.html -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YouTube Video Manager</title>
  <link rel="stylesheet" href="popup.css">
</head>
<body>
  <div class="container">
    <h1>YouTube Video Manager</h1>

    <div id="auth-section" class="section">
      <button id="login-btn" class="btn btn-primary">LINE 登入</button>
    </div>

    <div id="main-section" class="section hidden">
      <div id="user-info"></div>

      <div class="actions">
        <button id="add-to-library-btn" class="btn btn-success">加入播放庫</button>
        <button id="add-to-playlist-btn" class="btn btn-success">加入播放清單</button>
      </div>

      <div id="message" class="message hidden"></div>
    </div>
  </div>

  <script type="module" src="popup.js"></script>
</body>
</html>
```

### 3. Popup JavaScript（基本骨架）

```javascript
// src/popup/popup.js
import browser from 'webextension-polyfill';

// DOM 元素
const loginBtn = document.getElementById('login-btn');
const addToLibraryBtn = document.getElementById('add-to-library-btn');
const authSection = document.getElementById('auth-section');
const mainSection = document.getElementById('main-section');
const messageDiv = document.getElementById('message');

// 初始化
async function init() {
  // 檢查登入狀態
  const authData = await browser.storage.local.get('auth_data');

  if (authData && authData.auth_data) {
    showMainSection();
  } else {
    showAuthSection();
  }
}

function showAuthSection() {
  authSection.classList.remove('hidden');
  mainSection.classList.add('hidden');
}

function showMainSection() {
  authSection.classList.add('hidden');
  mainSection.classList.remove('hidden');
}

function showMessage(text, type = 'success') {
  messageDiv.textContent = text;
  messageDiv.className = `message ${type}`;
  messageDiv.classList.remove('hidden');

  setTimeout(() => {
    messageDiv.classList.add('hidden');
  }, 3000);
}

// 事件監聽
loginBtn.addEventListener('click', async () => {
  // TODO: 實作 LINE OAuth 登入
  console.log('LINE 登入');
});

addToLibraryBtn.addEventListener('click', async () => {
  // TODO: 實作加入播放庫
  console.log('加入播放庫');
});

// 啟動
init();
```

### 4. Popup CSS（基本樣式）

```css
/* src/popup/popup.css */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  width: 320px;
  font-family: Arial, sans-serif;
  font-size: 14px;
}

.container {
  padding: 16px;
}

h1 {
  font-size: 18px;
  margin-bottom: 16px;
}

.section {
  margin-bottom: 16px;
}

.hidden {
  display: none;
}

.btn {
  width: 100%;
  padding: 10px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  margin-bottom: 8px;
}

.btn-primary {
  background-color: #00B900;
  color: white;
}

.btn-success {
  background-color: #4CAF50;
  color: white;
}

.btn:hover {
  opacity: 0.9;
}

.message {
  padding: 10px;
  border-radius: 4px;
  margin-top: 16px;
}

.message.success {
  background-color: #d4edda;
  color: #155724;
}

.message.error {
  background-color: #f8d7da;
  color: #721c24;
}
```

---

## 開發工作流程

### 1. 構建擴充程式

建立簡單的構建腳本：

```json
// package.json
{
  "scripts": {
    "build:chrome": "cp manifest-chrome.json manifest.json",
    "build:firefox": "cp manifest-firefox.json manifest.json",
    "test": "jest",
    "dev:chrome": "npm run build:chrome && web-ext run --target chromium",
    "dev:firefox": "npm run build:firefox && web-ext run --target firefox-desktop"
  }
}
```

### 2. 在 Chrome 載入擴充程式

```bash
# 構建
npm run build:chrome

# 手動載入
# 1. 開啟 Chrome
# 2. 前往 chrome://extensions/
# 3. 啟用「開發人員模式」
# 4. 點擊「載入未封裝項目」
# 5. 選擇 browser-extension 目錄
```

### 3. 在 Firefox 測試

```bash
npm run dev:firefox
```

---

## 測試

### 1. 單元測試設定

```javascript
// jest.config.js
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1'
  }
};
```

### 2. 範例測試

```javascript
// tests/unit/url-parser.test.js
import { parseYouTubeURL } from '../../src/utils/url-parser';

describe('YouTube URL Parser', () => {
  test('解析標準 youtube.com URL', () => {
    const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
    expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
  });

  test('解析 youtu.be 短網址', () => {
    const url = 'https://youtu.be/dQw4w9WgXcQ';
    expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
  });

  test('解析包含播放清單的 URL，僅提取影片 ID', () => {
    const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf';
    expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
  });

  test('非 YouTube URL 回傳 null', () => {
    const url = 'https://example.com';
    expect(parseYouTubeURL(url)).toBeNull();
  });
});
```

### 3. 執行測試

```bash
npm test
```

---

## 除錯技巧

### 1. Chrome 擴充程式除錯

- **Popup 除錯**: 右鍵點擊擴充程式圖示 → 「檢查彈出式視窗」
- **Background Script 除錯**: 前往 `chrome://extensions/` → 點擊「service worker」
- **查看儲存資料**: DevTools → Application → Storage → Local Storage

### 2. Firefox 擴充程式除錯

- **Popup 除錯**: 右鍵點擊擴充程式圖示 → 「檢查擴充功能」
- **查看日誌**: about:debugging → 「本機 Firefox」→ 點擊「檢測」

### 3. 常見問題

| 問題 | 解決方案 |
|------|---------|
| 擴充程式無法載入 | 檢查 manifest.json 語法是否正確 |
| API 呼叫失敗 | 檢查 CORS 設定與 API 金鑰 |
| Storage 無法讀取 | 確認已在 manifest 中宣告 `storage` 權限 |
| OAuth redirect 失敗 | 檢查 LINE Console 的 Callback URL 設定 |

---

## 下一步

完成基本設定後，可依序實作以下功能：

1. **LINE OAuth 登入** (`src/services/auth.js`)
2. **YouTube URL 解析** (`src/utils/url-parser.js`)
3. **Token 管理** (`src/utils/token-manager.js`)
4. **API 通訊** (`src/services/api.js`)
5. **播放庫功能** (加入/移除影片)
6. **播放清單功能** (預設模式/自訂模式)

詳細實作請參考：
- [研究文件](./research.md) - 技術決策與最佳實踐
- [資料模型](./data-model.md) - 資料結構定義
- [API 合約](./contracts/backend-api.yaml) - 後端 API 規格

---

## 資源連結

- [Chrome Extensions 官方文件](https://developer.chrome.com/docs/extensions/)
- [Firefox Extensions 官方文件](https://developer.mozilla.org/en-US/docs/Mozilla/Add-ons/WebExtensions)
- [WebExtension Polyfill](https://github.com/mozilla/webextension-polyfill)
- [YouTube Data API v3](https://developers.google.com/youtube/v3)
- [LINE Login 文件](https://developers.line.biz/en/docs/line-login/)
