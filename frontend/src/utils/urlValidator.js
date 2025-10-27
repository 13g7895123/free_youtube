/**
 * YouTube URL 驗證和解析工具
 */

/**
 * 驗證是否為有效的 YouTube 網址
 * @param {string} url - 要驗證的網址
 * @returns {boolean} 是否為有效的 YouTube 網址
 */
export function isValidYouTubeUrl(url) {
  if (!url || typeof url !== 'string') {
    return false
  }

  try {
    const urlObj = new URL(url)
    const hostname = urlObj.hostname

    // 檢查是否為 YouTube 域名
    return (
      hostname.includes('youtube.com') ||
      hostname.includes('youtu.be') ||
      hostname === 'www.youtube.com' ||
      hostname === 'youtube.com' ||
      hostname === 'youtu.be'
    )
  } catch {
    return false
  }
}

/**
 * 從 YouTube 網址中提取影片 ID
 * @param {string} url - YouTube 網址
 * @returns {string|null} 影片 ID 或 null
 */
export function extractVideoId(url) {
  if (!url) return null

  // 支援的格式：
  // https://www.youtube.com/watch?v=VIDEO_ID
  // https://youtu.be/VIDEO_ID
  // https://www.youtube.com/embed/VIDEO_ID

  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/,
    /youtube\.com\/embed\/([a-zA-Z0-9_-]+)/
  ]

  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) {
      return match[1]
    }
  }

  return null
}

/**
 * 從 YouTube 網址中提取播放清單 ID
 * @param {string} url - YouTube 網址
 * @returns {string|null} 播放清單 ID 或 null
 */
export function extractPlaylistId(url) {
  if (!url) return null

  try {
    const urlObj = new URL(url)
    return urlObj.searchParams.get('list')
  } catch {
    return null
  }
}

/**
 * 驗證影片 ID 格式
 * @param {string} videoId - 影片 ID
 * @returns {boolean} 是否為有效的影片 ID
 */
export function isValidVideoId(videoId) {
  if (!videoId || typeof videoId !== 'string') {
    return false
  }
  return /^[a-zA-Z0-9_-]{11}$/.test(videoId)
}
