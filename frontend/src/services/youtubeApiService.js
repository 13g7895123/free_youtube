// YouTube API 載入服務（單例模式）
class YouTubeApiService {
  constructor() {
    this.apiReady = false
    this.loadPromise = null
  }

  loadApi() {
    // 如果已經載入，返回現有 Promise
    if (this.loadPromise) {
      return this.loadPromise
    }

    // 如果 API 已就緒，直接返回
    if (this.apiReady && window.YT) {
      return Promise.resolve()
    }

    // 創建新的載入 Promise
    this.loadPromise = new Promise((resolve, reject) => {
      // 檢查是否已經載入
      if (window.YT && window.YT.Player) {
        this.apiReady = true
        resolve()
        return
      }

      // 設置全域回調
      window.onYouTubeIframeAPIReady = () => {
        this.apiReady = true
        resolve()
      }

      // 載入 API
      const tag = document.createElement('script')
      tag.src = 'https://www.youtube.com/iframe_api'
      tag.onerror = reject
      const firstScriptTag = document.getElementsByTagName('script')[0]
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag)
    })

    return this.loadPromise
  }

  isReady() {
    return this.apiReady && window.YT && window.YT.Player
  }
}

export default new YouTubeApiService()
