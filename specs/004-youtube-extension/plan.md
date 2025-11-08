# Implementation Plan: YouTube 瀏覽器擴充程式

**Branch**: `004-youtube-extension` | **Date**: 2025-11-08 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-youtube-extension/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

建立 Chrome 與 Firefox 瀏覽器擴充程式，讓使用者透過 LINE OAuth 2.0 登入後，能在 YouTube 影片頁面快速將影片加入播放庫或播放清單。擴充程式僅在使用者點擊圖示時執行，遵循最小權限原則，並與現有後端 API 整合。

## Technical Context

**Language/Version**: JavaScript ES2020+ (Browser Extension)
**Primary Dependencies**:
- WebExtension APIs (browser.storage, browser.tabs, browser.runtime)
- LINE OAuth 2.0 SDK
- YouTube Data API v3（用於取得影片資訊）
**Storage**: Browser Storage API (chrome.storage.local / browser.storage.local)
**Testing**: Jest (單元測試), WebExtension Testing Framework
**Target Platform**: Chrome (Manifest V3) / Firefox (Manifest V2/V3)
**Project Type**: Browser Extension (跨平台擴充程式)
**Performance Goals**:
- LINE OAuth 登入流程 < 30 秒
- 加入影片操作 < 3 秒
- URL 解析準確率 100%
**Constraints**:
- 擴充程式大小 < 5MB
- 不可在背景持續運作（僅 Popup/Action 模式）
- Token 儲存必須使用安全的 Browser Storage
- 必須同時支援 Chrome 與 Firefox
**Scale/Scope**:
- 單一使用者本地操作
- 無數量限制的播放庫與播放清單
- Access token: 1 小時，Refresh token: 7 天

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### ✅ 憲章合規檢查

| 原則 | 狀態 | 說明 |
|------|------|------|
| **尊重棕地專案** | ✅ PASS | 此為全新功能（瀏覽器擴充程式），不修改現有後端或前端程式碼 |
| **最小化變更** | ✅ PASS | 擴充程式為獨立專案，僅透過 API 整合，對現有系統零侵入 |
| **需要明確許可** | ✅ PASS | 所有技術選擇已在規格澄清階段確認 |
| **測試紀律** | ✅ PASS | 新功能將包含單元測試與整合測試 |
| **技術堆疊穩定性** | ✅ PASS | 使用標準 WebExtension APIs，不影響現有 Vue.js 3.x 前端或 CodeIgniter 4.x 後端 |

**結論**: 無憲章違規，可進入 Phase 0 研究階段。

## Project Structure

### Documentation (this feature)

```text
specs/004-youtube-extension/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   ├── backend-api.yaml # 後端 API 規格（OpenAPI 3.0）
│   └── youtube-api.md   # YouTube Data API 使用說明
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
browser-extension/
├── manifest-chrome.json    # Chrome 擴充程式配置 (Manifest V3)
├── manifest-firefox.json   # Firefox 擴充程式配置 (Manifest V2/V3)
├── src/
│   ├── popup/
│   │   ├── popup.html     # 擴充程式彈出視窗
│   │   ├── popup.js       # 彈出視窗邏輯
│   │   └── popup.css      # 彈出視窗樣式
│   ├── services/
│   │   ├── auth.js        # LINE OAuth 2.0 驗證服務
│   │   ├── api.js         # 後端 API 通訊服務
│   │   ├── youtube.js     # YouTube URL 解析與 API 服務
│   │   └── storage.js     # Browser Storage 管理服務
│   ├── utils/
│   │   ├── url-parser.js  # YouTube URL 解析工具
│   │   └── token-manager.js # Token 管理工具
│   └── background/
│       └── background.js  # 背景腳本（僅用於 OAuth redirect 處理）
├── icons/
│   ├── icon-16.png
│   ├── icon-48.png
│   └── icon-128.png
└── tests/
    ├── unit/
    │   ├── url-parser.test.js
    │   ├── token-manager.test.js
    │   └── auth.test.js
    └── integration/
        └── extension.test.js
```

**Structure Decision**: 採用獨立的 `browser-extension/` 目錄結構，與現有的 `backend/` 和 `frontend/` 並列。此擴充程式為獨立專案，透過 RESTful API 與後端整合，不需要修改現有程式碼庫，符合憲章的棕地專案尊重原則。

## Complexity Tracking

> **無憲章違規，此表不適用**

此功能為全新獨立專案，不涉及現有程式碼修改，所有技術選擇已在規格澄清階段確認，無需複雜度追蹤。
