/**
 * YouTube URL 解析 Composable
 * 提供響應式的 URL 解析功能
 */
import { ref } from 'vue'
import { isValidYouTubeUrl, extractVideoId, extractPlaylistId } from '@/utils/urlValidator'
import { ERROR_MESSAGES } from '@/utils/errorMessages'

/**
 * 使用 URL 解析器
 * @returns {Object} URL 解析器物件
 */
export function useUrlParser() {
  // 響應式狀態
  const videoId = ref(null)
  const playlistId = ref(null)
  const isValid = ref(false)
  const errorMessage = ref('')

  /**
   * 解析 YouTube URL
   * @param {string} url - 要解析的 URL
   */
  function parseUrl(url) {
    // 重置狀態
    videoId.value = null
    playlistId.value = null
    isValid.value = false
    errorMessage.value = ''

    // 驗證輸入
    if (!url || typeof url !== 'string' || url.trim() === '') {
      errorMessage.value = ERROR_MESSAGES.INVALID_URL
      return
    }

    // 驗證是否為有效的 YouTube URL
    if (!isValidYouTubeUrl(url)) {
      errorMessage.value = ERROR_MESSAGES.INVALID_URL
      return
    }

    // 只提取影片 ID，忽略 list 參數
    // 即使 URL 中有播放清單（list 參數），也只取當前影片的 video ID
    const extractedVideoId = extractVideoId(url)
    if (extractedVideoId) {
      videoId.value = extractedVideoId
    }

    // 驗證是否有有效的影片 ID
    if (videoId.value) {
      isValid.value = true
      errorMessage.value = ''
    } else {
      errorMessage.value = ERROR_MESSAGES.INVALID_URL
    }
  }

  /**
   * 重置所有狀態
   */
  function reset() {
    videoId.value = null
    playlistId.value = null
    isValid.value = false
    errorMessage.value = ''
  }

  return {
    // 響應式狀態
    videoId,
    playlistId,
    isValid,
    errorMessage,
    // 方法
    parseUrl,
    reset
  }
}
