/**
 * YouTube 播放器管理 Composable
 * 封裝 YouTube IFrame Player API 的交互邏輯
 */
import { ref, onUnmounted, getCurrentInstance } from 'vue'
import { getErrorMessage } from '@/utils/errorMessages'

/**
 * 使用 YouTube 播放器
 * @param {string} containerId - 播放器容器的 DOM 元素 ID
 * @param {Object} options - 選項配置
 * @param {boolean} options.loopEnabled - 是否啟用循環播放（預設: true）
 * @returns {Object} 播放器控制物件
 */
export function useYouTubePlayer(containerId, options = {}) {
  // 播放器實例
  let playerInstance = null

  // 響應式狀態
  const isReady = ref(false)
  const isPlaying = ref(false)
  const isPaused = ref(false)
  const isBuffering = ref(false)
  const hasError = ref(false)
  const errorMessage = ref('')
  const currentTime = ref(0)
  const duration = ref(0)
  const loopEnabled = ref(options.loopEnabled !== undefined ? options.loopEnabled : true)
  const volume = ref(options.volume !== undefined ? options.volume : 100)
  const isMuted = ref(options.isMuted !== undefined ? options.isMuted : false)

  /**
   * 當播放器就緒時的回調
   * @param {Object} event - YouTube API 事件物件
   */
  function onPlayerReady(event) {
    isReady.value = true
    playerInstance = event.target

    // 應用初始音量設定
    if (playerInstance) {
      if (isMuted.value) {
        playerInstance.mute()
      } else {
        playerInstance.unMute()
        playerInstance.setVolume(volume.value)
      }
    }
  }

  /**
   * 當播放器狀態改變時的回調
   * @param {Object} event - YouTube API 事件物件
   */
  function onStateChange(event) {
    const state = event.data

    // 根據 YouTube PlayerState 更新狀態
    isPlaying.value = state === window.YT.PlayerState.PLAYING
    isPaused.value = state === window.YT.PlayerState.PAUSED
    isBuffering.value = state === window.YT.PlayerState.BUFFERING

    // 檢測影片結束
    if (state === window.YT.PlayerState.ENDED) {
      handleVideoEnd()
    }

    // 如果正在播放，清除任何錯誤狀態
    if (isPlaying.value) {
      hasError.value = false
      errorMessage.value = ''
    }
  }

  /**
   * 處理影片結束事件
   */
  function handleVideoEnd() {
    // 如果啟用循環播放
    if (loopEnabled.value) {
      // 跳轉到開頭並重新播放
      if (playerInstance) {
        playerInstance.seekTo(0, true)
        playerInstance.playVideo()
      }
    }
  }

  /**
   * 切換循環播放狀態
   * @param {boolean} enabled - 是否啟用循環
   */
  function setLoop(enabled) {
    loopEnabled.value = enabled
  }

  /**
   * 當發生錯誤時的回調
   * @param {Object} event - YouTube API 事件物件
   */
  function onError(event) {
    hasError.value = true
    errorMessage.value = getErrorMessage(event.data)
  }

  /**
   * 初始化 YouTube 播放器
   */
  function initPlayer() {
    if (!window.YT || !window.YT.Player) {
      console.error('YouTube IFrame API not loaded')
      return false
    }

    // 檢查 DOM 元素是否存在
    const container = document.getElementById(containerId)
    if (!container) {
      console.error(`Container element with id "${containerId}" not found`)
      return false
    }

    // 避免重複初始化
    if (playerInstance) {
      console.warn('Player already initialized')
      return true
    }

    try {
      playerInstance = new window.YT.Player(containerId, {
        // 設置初始尺寸為 100%，確保 API 正確計算 iframe 大小
        width: '100%',
        height: '100%',
        playerVars: {
          autoplay: 0,
          controls: 1,
          modestbranding: 1,
          rel: 0,
          fs: 1,
          playsinline: 1
        },
        events: {
          onReady: onPlayerReady,
          onStateChange: onStateChange,
          onError: onError
        }
      })
      return true
    } catch (error) {
      console.error('Failed to initialize player:', error)
      return false
    }
  }

  /**
   * 載入影片
   * @param {string} videoId - YouTube 影片 ID
   */
  function loadVideo(videoId) {
    if (!isReady.value || !playerInstance) {
      return
    }

    if (!videoId || typeof videoId !== 'string' || videoId.trim() === '') {
      return
    }

    // 清除錯誤狀態
    hasError.value = false
    errorMessage.value = ''

    playerInstance.loadVideoById(videoId)
  }

  /**
   * 載入播放清單
   * @param {string} playlistId - YouTube 播放清單 ID
   */
  function loadPlaylist(playlistId) {
    if (!isReady.value || !playerInstance) {
      return
    }

    if (!playlistId || typeof playlistId !== 'string' || playlistId.trim() === '') {
      return
    }

    // 清除錯誤狀態
    hasError.value = false
    errorMessage.value = ''

    playerInstance.loadPlaylist({
      list: playlistId,
      listType: 'playlist'
    })
  }

  /**
   * 播放影片
   */
  function play() {
    if (!isReady.value || !playerInstance) {
      return
    }

    playerInstance.playVideo()
  }

  /**
   * 暫停影片
   */
  function pause() {
    if (!isReady.value || !playerInstance) {
      return
    }

    playerInstance.pauseVideo()
  }

  /**
   * 跳轉到指定時間
   * @param {number} seconds - 秒數
   */
  function seekTo(seconds) {
    if (!isReady.value || !playerInstance) {
      return
    }

    // 驗證輸入
    if (typeof seconds !== 'number' || seconds < 0) {
      return
    }

    playerInstance.seekTo(seconds, true)
  }

  /**
   * 獲取當前播放時間
   * @returns {number} 當前時間（秒）
   */
  function getCurrentTime() {
    if (!isReady.value || !playerInstance) {
      return 0
    }

    return playerInstance.getCurrentTime()
  }

  /**
   * 獲取影片總時長
   * @returns {number} 總時長（秒）
   */
  function getDuration() {
    if (!isReady.value || !playerInstance) {
      return 0
    }

    return playerInstance.getDuration()
  }

  /**
   * 設置音量
   * @param {number} newVolume - 音量值（0-100）
   */
  function setVolume(newVolume) {
    if (!isReady.value || !playerInstance) {
      return
    }

    // 驗證音量範圍
    const validVolume = Math.max(0, Math.min(100, newVolume))
    volume.value = validVolume

    if (!isMuted.value) {
      playerInstance.setVolume(validVolume)
    }
  }

  /**
   * 靜音
   */
  function mute() {
    if (!isReady.value || !playerInstance) {
      return
    }

    isMuted.value = true
    playerInstance.mute()
  }

  /**
   * 取消靜音
   */
  function unmute() {
    if (!isReady.value || !playerInstance) {
      return
    }

    isMuted.value = false
    playerInstance.unMute()
    playerInstance.setVolume(volume.value)
  }

  /**
   * 切換靜音狀態
   */
  function toggleMute() {
    if (isMuted.value) {
      unmute()
    } else {
      mute()
    }
  }

  /**
   * 取得當前影片資訊
   * @returns {Object|null} 影片資訊物件或 null
   */
  function getCurrentVideoInfo() {
    if (!isReady.value || !playerInstance) {
      return null
    }

    try {
      const videoUrl = playerInstance.getVideoUrl()
      const videoData = playerInstance.getVideoData()

      return {
        videoId: videoData.video_id || extractVideoIdFromUrl(videoUrl),
        title: videoData.title || 'Unknown Title',
        author: videoData.author || '',
        duration: playerInstance.getDuration() || 0,
        thumbnail: `https://img.youtube.com/vi/${videoData.video_id}/maxresdefault.jpg`,
        youtubeUrl: videoUrl || `https://www.youtube.com/watch?v=${videoData.video_id}`
      }
    } catch (error) {
      console.error('Failed to get video info:', error)
      return null
    }
  }

  /**
   * 從 URL 中提取 video ID
   * @param {string} url - YouTube URL
   * @returns {string} video ID
   */
  function extractVideoIdFromUrl(url) {
    if (!url) return ''
    const match = url.match(/[?&]v=([^&]+)/)
    return match ? match[1] : ''
  }

  /**
   * 銷毀播放器
   */
  function destroy() {
    if (playerInstance && playerInstance.destroy) {
      playerInstance.destroy()
      playerInstance = null
    }

    // 重置所有狀態
    isReady.value = false
    isPlaying.value = false
    isPaused.value = false
    isBuffering.value = false
    hasError.value = false
    errorMessage.value = ''
    currentTime.value = 0
    duration.value = 0
  }

  // 僅在 DOM 元素存在且 API 已載入時自動初始化
  // 這樣可以支援測試環境，同時避免在實際應用中過早初始化
  if (window.YT && window.YT.Player && document.getElementById(containerId)) {
    initPlayer()
  }

  // 組件卸載時清理（僅在組件上下文中註冊）
  if (getCurrentInstance()) {
    onUnmounted(() => {
      destroy()
    })
  }

  return {
    // 響應式狀態
    isReady,
    isPlaying,
    isPaused,
    isBuffering,
    hasError,
    errorMessage,
    currentTime,
    duration,
    loopEnabled,
    volume,
    isMuted,
    // 控制方法
    loadVideo,
    loadPlaylist,
    play,
    pause,
    seekTo,
    getCurrentTime,
    getDuration,
    getCurrentVideoInfo,
    setLoop,
    setVolume,
    mute,
    unmute,
    toggleMute,
    destroy,
    // 初始化方法（供外部調用）
    initPlayer
  }
}
