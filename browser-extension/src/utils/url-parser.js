/**
 * YouTube URL 解析器
 * 支援解析 youtube.com、youtu.be、m.youtube.com 等格式
 */

/**
 * 從 YouTube URL 解析影片 ID
 * @param {string} url - YouTube URL
 * @returns {string|null} 影片 ID，若無法解析則回傳 null
 */
export function parseYouTubeURL(url) {
  if (!url || typeof url !== 'string') {
    return null;
  }

  try {
    const urlObj = new URL(url);

    // 格式 1: youtube.com/watch?v=VIDEO_ID
    // 格式 2: m.youtube.com/watch?v=VIDEO_ID
    if (
      (urlObj.hostname === 'www.youtube.com' ||
       urlObj.hostname === 'youtube.com' ||
       urlObj.hostname === 'm.youtube.com') &&
      urlObj.pathname === '/watch'
    ) {
      return urlObj.searchParams.get('v');
    }

    // 格式 3: youtu.be/VIDEO_ID
    if (urlObj.hostname === 'youtu.be') {
      // 移除開頭的 '/'
      const videoId = urlObj.pathname.slice(1);
      // 確保不是空字串
      return videoId || null;
    }

    return null;
  } catch (error) {
    // URL 解析失敗
    console.error('Failed to parse URL:', error);
    return null;
  }
}

/**
 * 檢查 URL 是否為 YouTube 網址
 * @param {string} url - URL
 * @returns {boolean}
 */
export function isYouTubeURL(url) {
  if (!url || typeof url !== 'string') {
    return false;
  }

  try {
    const urlObj = new URL(url);
    return (
      urlObj.hostname === 'www.youtube.com' ||
      urlObj.hostname === 'youtube.com' ||
      urlObj.hostname === 'm.youtube.com' ||
      urlObj.hostname === 'youtu.be'
    );
  } catch (error) {
    return false;
  }
}

/**
 * 檢查 URL 是否為 YouTube 影片頁面（而非首頁或搜尋頁）
 * @param {string} url - URL
 * @returns {boolean}
 */
export function isYouTubeVideoURL(url) {
  const videoId = parseYouTubeURL(url);
  return videoId !== null && videoId.length > 0;
}
