/**
 * 應用程式配置
 * 載入環境變數與設定
 */

// 注意：在瀏覽器擴充程式中，我們無法直接使用 process.env
// 這些值需要在構建時注入，或在執行時從其他來源讀取
// 這裡提供預設值作為範例

export const config = {
  // YouTube Data API v3 金鑰
  youtubeApiKey: 'YOUR_YOUTUBE_API_KEY',

  // LINE OAuth 設定
  lineChannelId: 'YOUR_LINE_CHANNEL_ID',
  lineRedirectUri: 'https://your-extension-id.chromiumapp.org/callback',

  // 後端 API URL
  backendApiUrl: 'http://localhost:8080/v1',
  backendUrl: 'http://localhost:8080/v1', // Alias for compatibility

  // LINE OAuth URLs
  lineAuthUrl: 'https://access.line.me/oauth2/v2.1/authorize',
  lineTokenUrl: 'https://api.line.me/oauth2/v2.1/token',

  // YouTube API URL
  youtubeApiUrl: 'https://www.googleapis.com/youtube/v3',

  // Token 有效期限（毫秒）
  accessTokenLifetime: 60 * 60 * 1000, // 1 小時
  refreshTokenLifetime: 7 * 24 * 60 * 60 * 1000, // 7 天

  // 播放清單快取有效期限（毫秒）
  playlistCacheDuration: 5 * 60 * 1000, // 5 分鐘

  // 重試設定
  maxRetries: 3,
  retryBaseDelay: 1000 // 1 秒
};

/**
 * 從 browser.storage 載入配置（如果有的話）
 * @returns {Promise<Object>}
 */
export async function loadConfig() {
  // 未來可從 storage 讀取使用者自訂的配置
  return config;
}

/**
 * 驗證必要的配置是否已設定
 * @returns {boolean}
 */
export function validateConfig() {
  const requiredKeys = ['youtubeApiKey', 'lineChannelId', 'backendApiUrl'];
  const missingKeys = requiredKeys.filter(key => {
    const value = config[key];
    return !value || value.startsWith('YOUR_');
  });

  if (missingKeys.length > 0) {
    console.warn('Missing or invalid configuration:', missingKeys);
    return false;
  }

  return true;
}
