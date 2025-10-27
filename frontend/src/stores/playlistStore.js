import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { playlistService } from '@/services/api'

export const usePlaylistStore = defineStore('playlist', () => {
  // State
  const playlists = ref([])
  const selectedPlaylist = ref(null)
  const playlistItems = ref([])
  const loading = ref(false)
  const error = ref(null)
  const currentPage = ref(1)
  const perPage = ref(20)
  const total = ref(0)

  // Computed
  const totalPages = computed(() => Math.ceil(total.value / perPage.value))

  // Actions
  const fetchPlaylists = async (page = 1) => {
    loading.value = true
    error.value = null
    try {
      const response = await playlistService.getPlaylists(page, perPage.value)
      playlists.value = response.data.data
      currentPage.value = page
      total.value = response.data.pagination?.total || 0
    } catch (err) {
      error.value = err.message || '無法載入播放清單'
      console.error('Error fetching playlists:', err)
    } finally {
      loading.value = false
    }
  }

  const getPlaylist = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await playlistService.getPlaylist(id)
      selectedPlaylist.value = response.data.data
      playlistItems.value = response.data.data.items || []
      return response.data.data
    } catch (err) {
      error.value = err.message || '無法載入播放清單詳情'
      console.error('Error fetching playlist:', err)
    } finally {
      loading.value = false
    }
  }

  const createPlaylist = async (playlistData) => {
    loading.value = true
    error.value = null
    try {
      const response = await playlistService.createPlaylist(playlistData)
      playlists.value.unshift(response.data.data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || '建立播放清單失敗'
      console.error('Error creating playlist:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updatePlaylist = async (id, playlistData) => {
    loading.value = true
    error.value = null
    try {
      const response = await playlistService.updatePlaylist(id, playlistData)
      const index = playlists.value.findIndex((p) => p.id === id)
      if (index !== -1) {
        playlists.value[index] = response.data.data
      }
      if (selectedPlaylist.value?.id === id) {
        selectedPlaylist.value = response.data.data
      }
      return response.data.data
    } catch (err) {
      error.value = err.message || '更新播放清單失敗'
      console.error('Error updating playlist:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deletePlaylist = async (id) => {
    loading.value = true
    error.value = null
    try {
      await playlistService.deletePlaylist(id)
      playlists.value = playlists.value.filter((p) => p.id !== id)
      if (selectedPlaylist.value?.id === id) {
        selectedPlaylist.value = null
        playlistItems.value = []
      }
    } catch (err) {
      error.value = err.message || '刪除播放清單失敗'
      console.error('Error deleting playlist:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const addItemToPlaylist = async (playlistId, videoId) => {
    loading.value = true
    error.value = null
    try {
      const response = await playlistService.addItemToPlaylist(playlistId, videoId)
      playlistItems.value = response.data.data
      if (selectedPlaylist.value?.id === playlistId) {
        selectedPlaylist.value.item_count = (selectedPlaylist.value.item_count || 0) + 1
      }
    } catch (err) {
      error.value = err.response?.data?.message || '新增項目失敗'
      console.error('Error adding item:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const removeItemFromPlaylist = async (playlistId, videoId) => {
    loading.value = true
    error.value = null
    try {
      await playlistService.removeItemFromPlaylist(playlistId, videoId)
      playlistItems.value = playlistItems.value.filter((i) => i.id !== videoId)
      if (selectedPlaylist.value?.id === playlistId) {
        selectedPlaylist.value.item_count = Math.max(0, (selectedPlaylist.value.item_count || 1) - 1)
      }
    } catch (err) {
      error.value = err.message || '移除項目失敗'
      console.error('Error removing item:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const reorderItems = async (playlistId, items) => {
    loading.value = true
    error.value = null
    try {
      const response = await playlistService.reorderItems(playlistId, items)
      playlistItems.value = response.data.data
    } catch (err) {
      error.value = err.message || '排序失敗'
      console.error('Error reordering items:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    playlists,
    selectedPlaylist,
    playlistItems,
    loading,
    error,
    currentPage,
    perPage,
    total,
    totalPages,
    fetchPlaylists,
    getPlaylist,
    createPlaylist,
    updatePlaylist,
    deletePlaylist,
    addItemToPlaylist,
    removeItemFromPlaylist,
    reorderItems,
    clearError,
  }
})
