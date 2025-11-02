import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || '/api'

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // 允許發送 cookies (HTTP-only cookies)
})

// Request interceptor: 記錄請求詳情
api.interceptors.request.use(
  (config) => {
    console.log('[API Request]', {
      method: config.method?.toUpperCase(),
      url: config.url,
      baseURL: config.baseURL,
      withCredentials: config.withCredentials,
      headers: config.headers,
      data: config.data
    })
    return config
  },
  (error) => {
    console.error('[API Request Error]', error)
    return Promise.reject(error)
  }
)

// Response interceptor: 處理 401 未授權錯誤
api.interceptors.response.use(
  (response) => {
    console.log('[API Response]', {
      status: response.status,
      statusText: response.statusText,
      url: response.config.url,
      data: response.data,
      headers: response.headers
    })
    return response
  },
  (error) => {
    console.error('[API Response Error]', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      url: error.config?.url,
      data: error.response?.data,
      message: error.message
    })

    if (error.response && error.response.status === 401) {
      console.warn('[API] 收到 401 未授權錯誤，觸發 auth:unauthorized 事件')
      // Token 過期或無效,清除認證狀態
      // 這裡會觸發 auth store 的登出邏輯
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('auth:unauthorized'))
      }
    }
    return Promise.reject(error)
  }
)

/**
 * 影片 API 服務
 */
export const videoService = {
  /**
   * 取得所有影片 (分頁)
   */
  getVideos(page = 1, perPage = 20) {
    return api.get('/videos', {
      params: { page, per_page: perPage },
    })
  },

  /**
   * 搜尋影片
   */
  searchVideos(query) {
    return api.get('/videos/search', {
      params: { q: query },
    })
  },

  /**
   * 取得單一影片
   */
  getVideo(id) {
    return api.get(`/videos/${id}`)
  },

  /**
   * 建立影片
   */
  createVideo(videoData) {
    return api.post('/videos', videoData)
  },

  /**
   * 更新影片
   */
  updateVideo(id, videoData) {
    return api.put(`/videos/${id}`, videoData)
  },

  /**
   * 刪除影片
   */
  deleteVideo(id) {
    return api.delete(`/videos/${id}`)
  },

  /**
   * 檢查影片是否存在
   */
  checkVideoExists(videoId) {
    return api.post('/videos/check', { video_id: videoId })
  },
}

/**
 * 影片庫 API 服務
 */
export const videoLibraryService = {
  /**
   * 取得影片庫 (分頁)
   */
  getLibrary(page = 1, perPage = 20) {
    return api.get('/video-library', {
      params: { page, per_page: perPage },
    })
  },

  /**
   * 新增影片到影片庫
   */
  addVideo(videoData) {
    return api.post('/video-library', videoData)
  },

  /**
   * 從影片庫移除影片
   */
  removeVideo(videoId) {
    return api.delete(`/video-library/${videoId}`)
  },
}

/**
 * 播放清單 API 服務
 */
export const playlistService = {
  /**
   * 取得所有播放清單
   */
  getPlaylists() {
    return api.get('/playlists')
  },

  /**
   * 取得播放清單詳情及項目
   */
  getPlaylist(id) {
    return api.get(`/playlists/${id}`)
  },

  /**
   * 建立播放清單
   */
  createPlaylist(playlistData) {
    return api.post('/playlists', playlistData)
  },

  /**
   * 更新播放清單
   */
  updatePlaylist(id, playlistData) {
    return api.put(`/playlists/${id}`, playlistData)
  },

  /**
   * 刪除播放清單
   */
  deletePlaylist(id) {
    return api.delete(`/playlists/${id}`)
  },

  /**
   * 新增影片到播放清單
   */
  addItemToPlaylist(playlistId, videoId) {
    return api.post(`/playlists/${playlistId}/items`, {
      video_id: videoId,
    })
  },

  /**
   * 重新排序播放清單項目
   */
  reorderItems(playlistId, itemIds) {
    return api.put(`/playlists/${playlistId}/reorder`, {
      item_ids: itemIds,
    })
  },

  /**
   * 從播放清單移除項目
   */
  removeItemFromPlaylist(playlistId, itemId) {
    return api.delete(`/playlists/${playlistId}/items/${itemId}`)
  },
}

export default api
