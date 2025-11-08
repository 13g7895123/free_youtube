# YouTube Video Manager - Browser Extension

快速將 YouTube 影片加入播放庫或播放清單的瀏覽器擴充功能

[English Version Below](#english-version)

## 目錄

- [功能](#功能)
- [安裝](#安裝)
- [使用方式](#使用方式)
- [設定](#設定)
- [開發](#開發)
- [測試](#測試)
- [文件](#文件)
- [常見問題](#常見問題)
- [貢獻](#貢獻)
- [授權](#授權)

## 功能

### 核心功能

✅ **LINE 帳戶登入**
- 使用 LINE 帳戶安全登入
- OAuth 2.0 標準認證流程
- 自動 Token 刷新管理

✅ **影片庫管理**
- 快速將 YouTube 影片加入個人影片庫
- 自動取得影片資訊（標題、頻道、縮圖等）
- 防止重複加入

✅ **播放清單管理**
- 支援兩種播放清單模式：
  - **預設模式**：自動加入指定播放清單，提高效率
  - **自訂模式**：每次選擇播放清單，更靈活
- 快取播放清單列表，減少 API 呼叫
- 即時反饋成功或失敗訊息

✅ **設定頁面**
- 選擇播放清單管理模式
- 設定預設播放清單
- 自動加入播放庫選項

✅ **無障礙設計**
- 完整 ARIA 標籤支援
- 螢幕閱讀器相容
- 鍵盤導航支援
- 適當的語義標記

### 技術特性

- 🔐 **安全認證**：Web Crypto API 加密 Token 儲存
- ⚡ **性能優化**：TTL 快取系統，減少 API 請求
- 🔄 **自動重試**：指數退避重試策略
- 🎯 **容錯機制**：YouTube API 額度用盡時的降級策略
- 📱 **跨瀏覽器**：支援 Chrome (Manifest V3) 和 Firefox (Manifest V2)

## 安裝

### 前置要求

- Node.js 14 以上版本
- npm 或 yarn
- Chrome 或 Firefox 瀏覽器

### 從源碼安裝

1. **複製儲存庫**
```bash
git clone <repository-url>
cd browser-extension
```

2. **安裝依賴**
```bash
npm install
```

3. **配置環境變數**
```bash
cp .env.example .env
# 編輯 .env 檔案，設定您的 API 端點和 LINE Channel ID
```

4. **開發模式執行**

對於 Chrome：
```bash
npm run dev:chrome
```

對於 Firefox：
```bash
npm run dev:firefox
```

擴充功能會自動載入，檔案變更時自動重新載入。

### 從應用商店安裝

- **Chrome Web Store** (即將推出)
- **Firefox Add-ons** (即將推出)

## 使用方式

### 基本流程

1. **登入**
   - 點擊擴充功能圖示開啟 popup
   - 點擊 "LINE 登入" 按鈕
   - 在彈出的 LINE 登入視窗授權
   - 登入成功後返回 popup

2. **加入影片到影片庫**
   - 在 YouTube 影片頁面開啟擴充功能 popup
   - 點擊 "加入播放庫" 按鈕
   - 等待確認訊息

3. **加入影片到播放清單**
   - 在 YouTube 影片頁面開啟擴充功能 popup
   - 點擊 "加入播放清單" 按鈕
   - 根據您的設定：
     - **預設模式**：自動加入預設播放清單
     - **自訂模式**：在 modal 中選擇目標播放清單
   - 等待確認訊息

4. **管理設定**
   - 點擊 popup 的 "設定" 按鈕
   - 選擇播放清單模式
   - 在預設模式下選擇預設播放清單
   - 點擊 "儲存設定"

### 訊息解釋

| 訊息 | 意義 | 操作 |
|------|------|------|
| ✅ 已加入播放庫 | 影片成功加入 | 無需操作 |
| ⓘ 此影片已在播放清單中 | 影片已存在 | 可加入其他清單 |
| ❌ 網路連線失敗 | 網路問題 | 檢查網路並重試 |
| ❌ 請先登入 | 認證失效 | 重新登入 |

## 設定

### 播放清單模式

#### 預設模式
- 自動加入指定的播放清單
- 適合有主要播放清單的使用者
- 加快工作流程
- 需在設定頁面預先選擇

#### 自訂模式 (預設)
- 每次手動選擇播放清單
- 提供更多靈活性
- 顯示各播放清單的影片數量

### 其他設定

- **自動加入播放庫**：加入播放清單時同時加入影片庫

## 開發

### 專案結構

```
browser-extension/
├── src/
│   ├── background/          # 背景服務工作者
│   ├── popup/               # Popup UI
│   ├── settings/            # 設定頁面
│   ├── services/            # API 和認證服務
│   └── utils/               # 工具函數
├── tests/                   # 測試檔案
├── docs/                    # 文件
├── icons/                   # 圖標
└── manifest-*.json          # 瀏覽器 manifest
```

詳見 [docs/development.md](docs/development.md)

### 建置

```bash
npm run build:chrome
npm run build:firefox
```

### 程式碼風格

- ES6+ 語法
- JSDoc 註解文件
- 遵循 ESLint 規則
- 使用 Prettier 格式化

## 測試

### 執行測試

```bash
# 執行所有測試
npm test

# 監視模式
npm run test:watch

# 生成覆蓋率報告
npm run test:coverage
```

### 測試覆蓋率

- API 服務：100%
- 驗證邏輯：95%
- Token 管理：100%
- 工具函數：90%

詳見 [docs/development.md](docs/development.md) 的調試部分

## 文件

- **[docs/features.md](docs/features.md)** - 功能詳細說明和使用指南
- **[docs/development.md](docs/development.md)** - 開發環境設定和架構
- **[docs/api.md](docs/api.md)** - 後端 API 參考文件
- **[CHANGELOG.md](CHANGELOG.md)** - 版本歷史和更新日誌

## 常見問題

### Q: 我的設定被重置了怎麼辦？
A: 檢查您的瀏覽器隱私設定。某些隱私模式或清除快取時會重置擴充功能資料。

### Q: 為什麼有些影片加不進去？
A: 可能原因：
- 未登入或認證過期 → 重新登入
- 影片已在播放清單中 → 選擇其他播放清單
- YouTube API 額度用盡 → 稍後重試

### Q: 如何匯出我的播放清單？
A: 此功能將在未來版本推出。

### Q: 支援哪些語言？
A: 目前支援繁體中文和英文。更多語言將在未來支援。

### Q: 我的 Token 安全嗎？
A: 是的，Token 使用 AES-GCM 加密儲存在本地。

## 瀏覽器支援

| 瀏覽器 | 版本 | Manifest | 狀態 |
|-------|------|----------|------|
| Chrome | 95+ | V3 | ✅ 完全支援 |
| Firefox | 90+ | V2 | ✅ 完全支援 |
| Edge | 95+ | V3 | ✅ 應可支援 |
| Safari | - | - | ⏳ 規劃中 |

## 性能指標

- 初次載入：< 500ms
- 加入影片：< 2s (含 API 請求)
- 彈出式視窗回應：< 100ms
- 記憶體佔用：< 50MB

## 隱私和安全

- ✅ 無追蹤代碼
- ✅ 無分析蒐集
- ✅ Token 本地加密儲存
- ✅ 不會修改頁面內容
- ✅ 僅在明確同意時發送資料

詳見我們的隱私政策（即將推出）。

## 貢獻

我們歡迎所有類型的貢獻！

1. Fork 本儲存庫
2. 建立特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交變更 (`git commit -m 'Add some amazing feature'`)
4. Push 至分支 (`git push origin feature/amazing-feature`)
5. 開啟 Pull Request

請確保：
- 新增的程式碼有相應的測試
- 所有測試通過 (`npm test`)
- 符合程式碼風格 (`npm run lint`)
- 更新相關文件

## 授權

MIT License - 詳見 [LICENSE](LICENSE) 檔案

## 致謝

感謝所有貢獻者和使用者的支持！

---

## English Version

# YouTube Video Manager - Browser Extension

Quickly add YouTube videos to your library or playlists with this browser extension.

### Features

✅ **LINE Login Integration** - Secure OAuth 2.0 authentication
✅ **Video Library** - Add videos with automatic metadata extraction
✅ **Playlist Management** - Two modes: predefined (auto) and custom (manual selection)
✅ **Settings Page** - Configure playlist mode and defaults
✅ **Accessibility** - Full ARIA support and screen reader compatibility

### Installation

```bash
# Clone repository
git clone <repository-url>
cd browser-extension

# Install dependencies
npm install

# Run in development
npm run dev:chrome    # Chrome
npm run dev:firefox   # Firefox
```

### Quick Start

1. Click the extension icon
2. Login with LINE account
3. Click "加入播放庫" to add to library or "加入播放清單" to add to playlist
4. Use Settings to configure your preferences

### Documentation

- [Features](docs/features.md) - Detailed feature documentation
- [Development](docs/development.md) - Development setup and architecture
- [API Reference](docs/api.md) - Backend API specifications

### Testing

```bash
npm test              # Run all tests
npm run test:watch   # Watch mode
npm run test:coverage # Coverage report
```

### Browser Support

- Chrome 95+
- Firefox 90+

### License

MIT License

---

**Version**: 1.0.0
**Last Updated**: January 10, 2025
