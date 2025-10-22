/**
 * 錯誤訊息常數 (繁體中文)
 * 所有使用者可見的錯誤訊息集中管理
 */

export const ERROR_MESSAGES = {
  // URL 驗證錯誤
  INVALID_URL: '網址格式不正確，請輸入有效的 YouTube 影片或播放清單網址',

  // 影片載入錯誤
  VIDEO_NOT_FOUND: '無法載入影片，影片可能已被移除或設為私人',
  PLAYLIST_NOT_FOUND: '無法載入播放清單，播放清單可能已被移除或設為私人',

  // 網路錯誤
  NETWORK_ERROR: '網路連線異常，請檢查網路設定後重試',

  // 播放錯誤
  PLAYBACK_ERROR: '播放發生錯誤，請嘗試重新載入',

  // YouTube API 錯誤
  EMBED_RESTRICTED: '此影片不允許嵌入播放',
  GEO_RESTRICTED: '此影片在您的地區無法播放',

  // 播放清單錯誤
  PLAYLIST_TOO_LARGE: '播放清單包含超過 200 個影片，可能影響效能',
  PLAYLIST_EMPTY: '播放清單為空或無法存取'
}

/**
 * 根據 YouTube API 錯誤代碼獲取對應的錯誤訊息
 * @param {number|string} errorCode - YouTube API 錯誤代碼
 * @returns {string} 使用者友善的錯誤訊息
 */
export function getErrorMessage(errorCode) {
  const code = String(errorCode)

  switch (code) {
    case '2':
      return ERROR_MESSAGES.INVALID_URL
    case '5':
      return ERROR_MESSAGES.GEO_RESTRICTED
    case '100':
      return ERROR_MESSAGES.VIDEO_NOT_FOUND
    case '101':
    case '150':
      return ERROR_MESSAGES.EMBED_RESTRICTED
    default:
      return ERROR_MESSAGES.PLAYBACK_ERROR
  }
}
