import { config } from '../utils/config.js';

/**
 * YouTube API 服務
 * 負責與 YouTube Data API 互動，取得影片資訊
 */

/**
 * 取得影片資訊
 * @param {string} videoId - YouTube 影片 ID
 * @returns {Promise<Object>} 影片資訊 { title, thumbnailUrl, duration, channelTitle }
 */
export async function getVideoInfo(videoId) {
  try {
    // 構建 YouTube API 請求 URL
    const url = new URL('https://www.googleapis.com/youtube/v3/videos');
    url.searchParams.set('part', 'snippet,contentDetails');
    url.searchParams.set('id', videoId);
    url.searchParams.set('key', config.youtubeApiKey);

    // 發送請求
    const response = await fetch(url.toString());

    // 檢查配額限制或其他錯誤
    if (!response.ok) {
      // YouTube API 配額不足 (HTTP 403) - 使用降級策略
      if (response.status === 403) {
        console.warn('YouTube API quota exceeded, using fallback strategy');
        return getFallbackVideoInfo(videoId);
      }

      // 其他錯誤
      throw new Error(`YouTube API error: ${response.status} ${response.statusText}`);
    }

    // 解析回應
    const data = await response.json();

    // 檢查是否有找到影片
    if (!data.items || data.items.length === 0) {
      throw new Error('Video not found');
    }

    const video = data.items[0];

    // 提取影片資訊
    return extractVideoInfo(video);

  } catch (error) {
    console.error('Failed to get video info from YouTube API:', error);

    // 如果是網路錯誤或其他問題，也使用降級策略
    if (error.message.includes('quota') || error.message.includes('403')) {
      return getFallbackVideoInfo(videoId);
    }

    throw error;
  }
}

/**
 * 從 YouTube API 回應中提取影片資訊
 * @param {Object} video - YouTube API 回應中的 video 物件
 * @returns {Object} 影片資訊
 */
function extractVideoInfo(video) {
  const { snippet, contentDetails } = video;

  return {
    title: snippet.title,
    description: snippet.description,
    thumbnailUrl: getThumbnailUrl(snippet.thumbnails),
    duration: parseDuration(contentDetails.duration),
    channelTitle: snippet.channelTitle,
    publishedAt: snippet.publishedAt
  };
}

/**
 * 從 thumbnails 物件中取得最佳縮圖 URL
 * 優先順序：medium > high > default
 * @param {Object} thumbnails - YouTube API 回應中的 thumbnails 物件
 * @returns {string} 縮圖 URL
 */
function getThumbnailUrl(thumbnails) {
  if (thumbnails.medium) {
    return thumbnails.medium.url;
  }
  if (thumbnails.high) {
    return thumbnails.high.url;
  }
  if (thumbnails.default) {
    return thumbnails.default.url;
  }
  return null;
}

/**
 * 解析 ISO 8601 duration 格式 (例如: PT1H2M10S)
 * @param {string} isoDuration - ISO 8601 duration 字串
 * @returns {number} 總秒數
 */
function parseDuration(isoDuration) {
  // PT1H2M10S -> 3730 秒
  // PT5M30S -> 330 秒
  // PT45S -> 45 秒

  const matches = isoDuration.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);

  if (!matches) {
    return 0;
  }

  const hours = parseInt(matches[1]) || 0;
  const minutes = parseInt(matches[2]) || 0;
  const seconds = parseInt(matches[3]) || 0;

  return hours * 3600 + minutes * 60 + seconds;
}

/**
 * 降級策略：當 YouTube API 配額不足時使用
 * 僅使用影片 ID，標題設為空，使用標準縮圖 URL 格式
 * @param {string} videoId - YouTube 影片 ID
 * @returns {Object} 基本影片資訊
 */
function getFallbackVideoInfo(videoId) {
  return {
    title: null, // 無法取得標題
    description: null,
    thumbnailUrl: `https://img.youtube.com/vi/${videoId}/mqdefault.jpg`, // 標準中等品質縮圖
    duration: null, // 無法取得時長
    channelTitle: null,
    publishedAt: null,
    isFallback: true // 標記為降級模式
  };
}

/**
 * 格式化秒數為人類可讀的時長格式
 * @param {number} seconds - 總秒數
 * @returns {string} 格式化後的時長 (例如: "1:23:45", "5:30", "0:45")
 */
export function formatDuration(seconds) {
  if (!seconds || seconds === 0) {
    return '0:00';
  }

  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  } else {
    return `${minutes}:${String(secs).padStart(2, '0')}`;
  }
}

/**
 * 批次取得多個影片資訊
 * @param {string[]} videoIds - YouTube 影片 ID 陣列（最多 50 個）
 * @returns {Promise<Object[]>} 影片資訊陣列
 */
export async function getBatchVideoInfo(videoIds) {
  if (videoIds.length === 0) {
    return [];
  }

  // YouTube API 最多支援一次查詢 50 個影片
  if (videoIds.length > 50) {
    throw new Error('Maximum 50 video IDs allowed per request');
  }

  try {
    const url = new URL('https://www.googleapis.com/youtube/v3/videos');
    url.searchParams.set('part', 'snippet,contentDetails');
    url.searchParams.set('id', videoIds.join(','));
    url.searchParams.set('key', config.youtubeApiKey);

    const response = await fetch(url.toString());

    if (!response.ok) {
      if (response.status === 403) {
        console.warn('YouTube API quota exceeded for batch request');
        return videoIds.map(id => getFallbackVideoInfo(id));
      }
      throw new Error(`YouTube API error: ${response.status}`);
    }

    const data = await response.json();

    return data.items.map(video => extractVideoInfo(video));

  } catch (error) {
    console.error('Failed to get batch video info:', error);
    return videoIds.map(id => getFallbackVideoInfo(id));
  }
}
