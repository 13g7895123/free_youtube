import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || '/api'

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

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
 * 播放清單 API 服務
 */
export const playlistService = {
  /**
   * 取得所有播放清單 (分頁)
   */
  getPlaylists(page = 1, perPage = 20) {
    return api.get('/playlists', {
      params: { page, per_page: perPage },
    })
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
   * 取得播放清單中的項目
   */
  getPlaylistItems(playlistId) {
    return api.get(`/playlists/${playlistId}/items`)
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
  reorderItems(playlistId, items) {
    return api.post(`/playlists/${playlistId}/items/reorder`, {
      items,
    })
  },

  /**
   * 從播放清單移除影片
   */
  removeItemFromPlaylist(playlistId, videoId) {
    return api.delete(`/playlists/${playlistId}/items/${videoId}`)
  },
}

export default api
