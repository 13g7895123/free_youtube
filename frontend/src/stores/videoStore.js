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

  const searchVideos = async (query) => {
    if (query.length < 2) {
      videos.value = []
      return
    }
    searchQuery.value = query
    loading.value = true
    error.value = null
    try {
      const response = await videoService.searchVideos(query)
      videos.value = response.data.data
    } catch (err) {
      error.value = err.message || '搜尋失敗'
      console.error('Error searching videos:', err)
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

  const clearError = () => {
    error.value = null
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
    searchVideos,
    getVideo,
    createVideo,
    updateVideo,
    deleteVideo,
    checkVideoExists,
    clearError,
  }
})
