import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { videoService } from '@/services/api'

export const useVideoStore = defineStore('video', () => {
  // State
  const videos = ref([])
  const selectedVideo = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(20)
  const total = ref(0)

  // Computed
  const filteredVideos = computed(() => {
    if (!searchQuery.value) return videos.value
    const query = searchQuery.value.toLowerCase()
    return videos.value.filter(
      (v) =>
        v.title.toLowerCase().includes(query) ||
        v.description?.toLowerCase().includes(query) ||
        v.channel_name?.toLowerCase().includes(query)
    )
  })

  const totalPages = computed(() => Math.ceil(total.value / perPage.value))

  // Actions
  const fetchVideos = async (page = 1) => {
    loading.value = true
    error.value = null
    try {
      const response = await videoService.getVideos(page, perPage.value)
      videos.value = response.data.data
      currentPage.value = page
      total.value = response.data.pagination?.total || 0
    } catch (err) {
      error.value = err.message || '無法載入影片'
      console.error('Error fetching videos:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * 獲取所有影片（不限於單頁）
   * 會循環請求所有頁面直到獲取完整的影片庫
   */
  const fetchAllVideos = async () => {
    loading.value = true
    error.value = null
    let allVideos = []
    let page = 1

    try {
      // 循環請求直到獲取所有頁面
      while (true) {
        const response = await videoService.getVideos(page, perPage.value)
        const pageVideos = response.data.data

        // 如果當前頁沒有資料，代表已經到最後一頁
        if (!pageVideos || pageVideos.length === 0) {
          break
        }

        allVideos = [...allVideos, ...pageVideos]

        // 檢查是否還有下一頁
        const totalCount = response.data.pagination?.total || 0
        const totalPages = Math.ceil(totalCount / perPage.value)

        if (page >= totalPages) {
          break
        }

        page++
      }

      videos.value = allVideos
      total.value = allVideos.length
      currentPage.value = 1 // 重置為第一頁
    } catch (err) {
      error.value = err.message || '無法載入所有影片'
      console.error('Error fetching all videos:', err)
    } finally {
      loading.value = false
    }
  }

  const searchVideos = async (query) => {
    if (query.length < 2) {
      videos.value = []
      return { data: { data: [] } }
    }
    searchQuery.value = query
    loading.value = true
    error.value = null
    try {
      const response = await videoService.searchVideos(query)
      videos.value = response.data.data
      // 重置分頁狀態（搜尋不支援分頁）
      currentPage.value = 1
      total.value = response.data.data.length
      return response
    } catch (err) {
      error.value = err.message || '搜尋失敗'
      console.error('Error searching videos:', err)
      return { data: { data: [] } }
    } finally {
      loading.value = false
    }
  }

  const getVideo = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await videoService.getVideo(id)
      selectedVideo.value = response.data.data
      return response.data.data
    } catch (err) {
      error.value = err.message || '無法載入影片詳情'
      console.error('Error fetching video:', err)
    } finally {
      loading.value = false
    }
  }

  const createVideo = async (videoData) => {
    loading.value = true
    error.value = null
    try {
      const response = await videoService.createVideo(videoData)
      videos.value.unshift(response.data.data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || '建立影片失敗'
      console.error('Error creating video:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateVideo = async (id, videoData) => {
    loading.value = true
    error.value = null
    try {
      const response = await videoService.updateVideo(id, videoData)
      const index = videos.value.findIndex((v) => v.id === id)
      if (index !== -1) {
        videos.value[index] = response.data.data
      }
      if (selectedVideo.value?.id === id) {
        selectedVideo.value = response.data.data
      }
      return response.data.data
    } catch (err) {
      error.value = err.message || '更新影片失敗'
      console.error('Error updating video:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteVideo = async (id) => {
    loading.value = true
    error.value = null
    try {
      await videoService.deleteVideo(id)
      videos.value = videos.value.filter((v) => v.id !== id)
      if (selectedVideo.value?.id === id) {
        selectedVideo.value = null
      }
    } catch (err) {
      error.value = err.message || '刪除影片失敗'
      console.error('Error deleting video:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const checkVideoExists = async (videoId) => {
    try {
      const response = await videoService.checkVideoExists(videoId)
      return response.data.data.exists
    } catch (err) {
      console.error('Error checking video:', err)
      return false
    }
  }

  /**
   * Get video by YouTube video_id
   * Returns the video object if it exists, null otherwise
   */
  const getVideoByYoutubeId = async (videoId) => {
    try {
      const response = await videoService.checkVideoExists(videoId)
      if (response.data.data.exists && response.data.data.video) {
        return response.data.data.video
      }
      return null
    } catch (err) {
      console.error('Error getting video by YouTube ID:', err)
      return null
    }
  }

  const clearError = () => {
    error.value = null
  }

  /**
   * 匯出所有影片資料為 JSON 檔案
   */
  const exportVideos = () => {
    try {
      const exportData = {
        version: '1.0',
        exportDate: new Date().toISOString(),
        totalVideos: videos.value.length,
        videos: videos.value.map(v => ({
          video_id: v.video_id,
          title: v.title,
          description: v.description,
          youtube_url: v.youtube_url,
          thumbnail_url: v.thumbnail_url,
          duration: v.duration,
          channel_name: v.channel_name,
          channel_id: v.channel_id,
          published_at: v.published_at
        }))
      }

      const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `videos-export-${new Date().toISOString().split('T')[0]}.json`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)

      return { success: true, count: videos.value.length }
    } catch (err) {
      console.error('Error exporting videos:', err)
      error.value = '匯出失敗'
      throw err
    }
  }

  /**
   * 匯入影片資料從 JSON 檔案
   */
  const importVideos = async (file) => {
    loading.value = true
    error.value = null

    try {
      const text = await file.text()
      const importData = JSON.parse(text)

      // 驗證資料格式
      if (!importData.videos || !Array.isArray(importData.videos)) {
        throw new Error('無效的匯入檔案格式')
      }

      let successCount = 0
      let failCount = 0
      const errors = []

      // 逐一匯入影片
      for (const videoData of importData.videos) {
        try {
          // 檢查影片是否已存在
          const exists = await checkVideoExists(videoData.video_id)
          if (!exists) {
            await createVideo(videoData)
            successCount++
          } else {
            console.log('Video already exists:', videoData.video_id)
            failCount++
          }
        } catch (err) {
          console.error('Error importing video:', videoData.video_id, err)
          failCount++
          errors.push({ video_id: videoData.video_id, error: err.message })
        }
      }

      // 重新載入影片列表
      await fetchVideos()

      return {
        success: true,
        successCount,
        failCount,
        total: importData.videos.length,
        errors
      }
    } catch (err) {
      console.error('Error importing videos:', err)
      error.value = err.message || '匯入失敗'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    videos,
    selectedVideo,
    loading,
    error,
    searchQuery,
    currentPage,
    perPage,
    total,
    filteredVideos,
    totalPages,
    fetchVideos,
    fetchAllVideos,
    searchVideos,
    getVideo,
    createVideo,
    updateVideo,
    deleteVideo,
    checkVideoExists,
    getVideoByYoutubeId,
    clearError,
    exportVideos,
    importVideos,
  }
})
