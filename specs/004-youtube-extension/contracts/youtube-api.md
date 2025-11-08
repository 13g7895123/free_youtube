# YouTube Data API v3 使用說明

**Date**: 2025-11-08
**Feature**: 004-youtube-extension

## 概述

此文件說明如何在 YouTube 瀏覽器擴充程式中使用 YouTube Data API v3 取得影片資訊。

---

## API 金鑰設定

### 1. 申請 API 金鑰

1. 前往 [Google Cloud Console](https://console.cloud.google.com/)
2. 建立新專案或選擇現有專案
3. 啟用「YouTube Data API v3」
4. 建立憑證 → API 金鑰
5. 設定 API 金鑰限制（建議限制為「HTTP referrers」）

### 2. 金鑰儲存

將 API 金鑰儲存在擴充程式的環境變數中：

```javascript
// config.js
export const YOUTUBE_API_KEY = process.env.YOUTUBE_API_KEY || 'YOUR_API_KEY';
```

**安全性注意事項**:
- 不可將 API 金鑰硬編碼在原始碼中
- 使用構建工具（如 Webpack）注入環境變數
- 限制 API 金鑰的使用權限（僅允許 YouTube Data API）

---

## 取得影片資訊

### API 端點

```
GET https://www.googleapis.com/youtube/v3/videos
```

### 請求參數

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| `id` | string | 是 | YouTube 影片 ID（可同時查詢多個，以逗號分隔） |
| `part` | string | 是 | 要取得的資料部分（`snippet`, `contentDetails`） |
| `key` | string | 是 | API 金鑰 |

### 請求範例

```javascript
const videoId = 'dQw4w9WgXcQ';
const apiKey = 'YOUR_API_KEY';

const url = `https://www.googleapis.com/youtube/v3/videos?id=${videoId}&part=snippet,contentDetails&key=${apiKey}`;

const response = await fetch(url);
const data = await response.json();
```

### 回應範例

```json
{
  "kind": "youtube#videoListResponse",
  "etag": "...",
  "items": [
    {
      "kind": "youtube#video",
      "etag": "...",
      "id": "dQw4w9WgXcQ",
      "snippet": {
        "publishedAt": "2009-10-25T06:57:33Z",
        "channelId": "UCuAXFkgsw1L7xaCfnd5JJOw",
        "title": "Rick Astley - Never Gonna Give You Up (Official Music Video)",
        "description": "...",
        "thumbnails": {
          "default": {
            "url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg",
            "width": 120,
            "height": 90
          },
          "medium": {
            "url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg",
            "width": 320,
            "height": 180
          },
          "high": {
            "url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg",
            "width": 480,
            "height": 360
          }
        },
        "channelTitle": "Rick Astley",
        "categoryId": "10",
        "liveBroadcastContent": "none",
        "localized": {
          "title": "Rick Astley - Never Gonna Give You Up (Official Music Video)",
          "description": "..."
        }
      },
      "contentDetails": {
        "duration": "PT3M33S",
        "dimension": "2d",
        "definition": "hd",
        "caption": "false",
        "licensedContent": true,
        "contentRating": {},
        "projection": "rectangular"
      }
    }
  ],
  "pageInfo": {
    "totalResults": 1,
    "resultsPerPage": 1
  }
}
```

---

## 資料擷取邏輯

### 擷取所需欄位

從 API 回應中擷取以下資訊：

```javascript
function extractVideoInfo(apiResponse) {
  if (!apiResponse.items || apiResponse.items.length === 0) {
    throw new Error('影片不存在或無法存取');
  }

  const video = apiResponse.items[0];

  return {
    youtubeVideoId: video.id,
    title: video.snippet.title,
    thumbnailUrl: video.snippet.thumbnails.medium.url, // 使用 medium 尺寸
    duration: video.contentDetails.duration // ISO 8601 格式，例如 "PT3M33S"
  };
}
```

### 時長格式轉換（選填）

若需在 UI 顯示人類可讀的時長格式，可進行轉換：

```javascript
function parseDuration(isoDuration) {
  // 解析 ISO 8601 duration 格式 (例如 "PT3M33S")
  const match = isoDuration.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);

  const hours = parseInt(match[1] || 0);
  const minutes = parseInt(match[2] || 0);
  const seconds = parseInt(match[3] || 0);

  // 格式化為 "HH:MM:SS" 或 "MM:SS"
  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }
  return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

// 範例
parseDuration('PT3M33S'); // "3:33"
parseDuration('PT1H30M15S'); // "1:30:15"
```

---

## 配額管理

### 配額限制

- **每日配額**: 10,000 units
- **單次 `videos.list` 查詢**: 1 unit
- **估算使用量**: 若使用者每天加入 100 部影片，消耗 100 units

### 配額不足處理

當配額不足時，API 回應 HTTP 403：

```json
{
  "error": {
    "errors": [
      {
        "domain": "youtube.quota",
        "reason": "quotaExceeded",
        "message": "The request cannot be completed because you have exceeded your quota."
      }
    ],
    "code": 403,
    "message": "The request cannot be completed because you have exceeded your quota."
  }
}
```

**降級策略**:

```javascript
async function fetchVideoInfo(videoId) {
  try {
    const response = await fetch(`https://www.googleapis.com/youtube/v3/videos?id=${videoId}&part=snippet,contentDetails&key=${YOUTUBE_API_KEY}`);

    if (response.status === 403) {
      // 配額不足，降級為僅儲存影片 ID
      console.warn('YouTube API 配額不足，延後擷取影片資訊');
      return {
        youtubeVideoId: videoId,
        title: '（影片資訊將稍後載入）',
        thumbnailUrl: `https://i.ytimg.com/vi/${videoId}/mqdefault.jpg`, // 使用標準縮圖 URL 格式
        duration: 'PT0S' // 預設值
      };
    }

    const data = await response.json();
    return extractVideoInfo(data);
  } catch (error) {
    console.error('取得 YouTube 影片資訊失敗', error);
    throw error;
  }
}
```

---

## 錯誤處理

### 常見錯誤

| HTTP 狀態碼 | 錯誤原因 | 處理方式 |
|------------|---------|---------|
| 400 | 請求參數錯誤 | 檢查影片 ID 格式是否正確 |
| 403 | API 金鑰無效或配額不足 | 檢查金鑰設定，或降級處理 |
| 404 | 影片不存在 | 提示使用者影片已被刪除 |
| 500 | YouTube 伺服器錯誤 | 重試或稍後再試 |

### 錯誤處理範例

```javascript
async function safelyFetchVideoInfo(videoId) {
  try {
    return await fetchVideoInfo(videoId);
  } catch (error) {
    if (error.status === 404) {
      throw new Error('此影片不存在或已被刪除');
    }
    if (error.status === 403) {
      // 降級策略已在 fetchVideoInfo 中處理
      return fetchVideoInfo(videoId);
    }
    throw new Error('無法取得影片資訊，請稍後再試');
  }
}
```

---

## 最佳實踐

### 1. 快取策略

對於已查詢過的影片，建議快取結果避免重複 API 呼叫：

```javascript
const videoCache = new Map();

async function getCachedVideoInfo(videoId) {
  if (videoCache.has(videoId)) {
    return videoCache.get(videoId);
  }

  const info = await fetchVideoInfo(videoId);
  videoCache.set(videoId, info);
  return info;
}
```

### 2. 批次查詢

若需同時查詢多部影片，使用逗號分隔的 ID 列表：

```javascript
const videoIds = ['dQw4w9WgXcQ', 'jNQXAC9IVRw', 'y6120QOlsfU'];
const url = `https://www.googleapis.com/youtube/v3/videos?id=${videoIds.join(',')}&part=snippet,contentDetails&key=${YOUTUBE_API_KEY}`;
```

**注意**: 批次查詢仍按影片數量計算 units（3 部影片 = 3 units）

### 3. Rate Limiting

避免在短時間內大量 API 呼叫，建議加入節流：

```javascript
import { debounce } from 'lodash';

const debouncedFetch = debounce(fetchVideoInfo, 500); // 500ms 內僅執行一次
```

---

## 測試

### 測試影片 ID

可使用以下公開影片進行測試：

- `dQw4w9WgXcQ` - Rick Astley - Never Gonna Give You Up
- `jNQXAC9IVRw` - Me at the zoo（YouTube 第一支影片）
- `y6120QOlsfU` - YouTube Rewind 2018

### Mock 資料

開發時可使用 Mock 資料避免消耗配額：

```javascript
const MOCK_VIDEO_INFO = {
  youtubeVideoId: 'dQw4w9WgXcQ',
  title: 'Rick Astley - Never Gonna Give You Up',
  thumbnailUrl: 'https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
  duration: 'PT3M33S'
};

// 在開發環境使用 Mock
const fetchVideoInfo = process.env.NODE_ENV === 'development'
  ? async () => MOCK_VIDEO_INFO
  : realFetchVideoInfo;
```

---

## 參考資源

- [YouTube Data API v3 官方文件](https://developers.google.com/youtube/v3/docs)
- [影片資源文件](https://developers.google.com/youtube/v3/docs/videos)
- [配額計算器](https://developers.google.com/youtube/v3/determine_quota_cost)
- [API Explorer](https://developers.google.com/youtube/v3/docs/videos/list)
