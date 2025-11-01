import { computed } from 'vue'
import { useLocalStorage } from './useLocalStorage'

/**
 * 訪客播放歷史記錄 LocalStorage 服務
 *
 * 資料結構:
 * {
 *   videoId: string,      // YouTube 影片 ID
 *   title: string,        // 影片標題
 *   thumbnail: string,    // 縮圖 URL
 *   playedAt: number      // 最後播放時間戳記 (timestamp)
 * }
 */
export function useGuestHistory() {
  // LocalStorage key
  const STORAGE_KEY = 'youtube-loop-player-guest-history'
  const MAX_HISTORY_ITEMS = 50 // 最多保存 50 筆歷史記錄

  // 使用 LocalStorage composable 管理資料
  const historyData = useLocalStorage(STORAGE_KEY, [])

  /**
   * 取得所有歷史記錄（按播放時間降序排列）
   */
  const history = computed(() => {
    return [...historyData.value].sort((a, b) => b.playedAt - a.playedAt)
  })

  /**
   * 取得歷史記錄數量
   */
  const count = computed(() => historyData.value.length)

  /**
   * 檢查是否為空
   */
  const isEmpty = computed(() => historyData.value.length === 0)

  /**
   * 新增影片到歷史記錄
   *
   * @param {Object} videoInfo - 影片資訊
   * @param {string} videoInfo.videoId - YouTube 影片 ID
   * @param {string} videoInfo.title - 影片標題
   * @param {string} videoInfo.thumbnail - 縮圖 URL
   */
  function addToHistory(videoInfo) {
    if (!videoInfo || !videoInfo.videoId) {
      console.warn('Invalid video info:', videoInfo)
      return
    }

    const { videoId, title = '未知標題', thumbnail = '' } = videoInfo

    // 檢查是否已存在，如果存在則移除舊記錄
    const existingIndex = historyData.value.findIndex(item => item.videoId === videoId)
    if (existingIndex !== -1) {
      historyData.value.splice(existingIndex, 1)
    }

    // 新增新記錄（放在陣列開頭）
    historyData.value.unshift({
      videoId,
      title,
      thumbnail,
      playedAt: Date.now()
    })

    // 限制歷史記錄數量
    if (historyData.value.length > MAX_HISTORY_ITEMS) {
      historyData.value = historyData.value.slice(0, MAX_HISTORY_ITEMS)
    }
  }

  /**
   * 從歷史記錄中移除特定影片
   *
   * @param {string} videoId - YouTube 影片 ID
   */
  function removeFromHistory(videoId) {
    const index = historyData.value.findIndex(item => item.videoId === videoId)
    if (index !== -1) {
      historyData.value.splice(index, 1)
    }
  }

  /**
   * 清空所有歷史記錄
   */
  function clearHistory() {
    historyData.value = []
  }

  /**
   * 取得特定影片的歷史記錄
   *
   * @param {string} videoId - YouTube 影片 ID
   * @returns {Object|null} 歷史記錄項目或 null
   */
  function getHistoryItem(videoId) {
    return historyData.value.find(item => item.videoId === videoId) || null
  }

  /**
   * 檢查影片是否在歷史記錄中
   *
   * @param {string} videoId - YouTube 影片 ID
   * @returns {boolean}
   */
  function hasInHistory(videoId) {
    return historyData.value.some(item => item.videoId === videoId)
  }

  /**
   * 取得原始歷史記錄資料（用於遷移到會員帳號）
   *
   * @returns {Array} 歷史記錄陣列
   */
  function getRawHistory() {
    return historyData.value
  }

  /**
   * 匯入歷史記錄（用於從其他來源恢復）
   *
   * @param {Array} items - 歷史記錄陣列
   */
  function importHistory(items) {
    if (!Array.isArray(items)) {
      console.warn('Invalid import data, expected array')
      return
    }

    // 驗證並過濾有效的項目
    const validItems = items.filter(item =>
      item &&
      typeof item.videoId === 'string' &&
      item.videoId.length > 0
    )

    historyData.value = validItems
  }

  return {
    // State
    history,
    count,
    isEmpty,

    // Methods
    addToHistory,
    removeFromHistory,
    clearHistory,
    getHistoryItem,
    hasInHistory,
    getRawHistory,
    importHistory
  }
}
