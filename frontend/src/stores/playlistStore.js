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

  /**
   * 匯出所有播放清單資料為 JSON 檔案
   */
  const exportPlaylists = async () => {
    loading.value = true
    error.value = null

    try {
      // 取得所有播放清單的完整資料（包含項目）
      const playlistsWithItems = []
      for (const playlist of playlists.value) {
        const fullPlaylist = await getPlaylist(playlist.id)
        if (fullPlaylist) {
          playlistsWithItems.push({
            name: fullPlaylist.name,
            description: fullPlaylist.description,
            is_active: fullPlaylist.is_active,
            items: fullPlaylist.items.map(item => ({
              video_id: item.video_id,
              title: item.title,
              youtube_url: item.youtube_url,
              thumbnail_url: item.thumbnail_url,
              duration: item.duration
            }))
          })
        }
      }

      const exportData = {
        version: '1.0',
        exportDate: new Date().toISOString(),
        totalPlaylists: playlistsWithItems.length,
        playlists: playlistsWithItems
      }

      const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `playlists-export-${new Date().toISOString().split('T')[0]}.json`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)

      return { success: true, count: playlistsWithItems.length }
    } catch (err) {
      console.error('Error exporting playlists:', err)
      error.value = '匯出失敗'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * 匯入播放清單資料從 JSON 檔案
   */
  const importPlaylists = async (file, videoStore) => {
    loading.value = true
    error.value = null

    try {
      const text = await file.text()

      let importData
      try {
        importData = JSON.parse(text)
      } catch (parseError) {
        throw new Error('無效的 JSON 格式：' + parseError.message)
      }

      // 驗證資料格式
      if (!importData || typeof importData !== 'object') {
        throw new Error('無效的匯入檔案格式：檔案內容必須是有效的 JSON 物件')
      }

      if (!importData.playlists) {
        throw new Error('無效的匯入檔案格式：缺少 playlists 屬性')
      }

      if (!Array.isArray(importData.playlists)) {
        throw new Error('無效的匯入檔案格式：playlists 必須是陣列')
      }

      if (importData.playlists.length === 0) {
        throw new Error('匯入檔案中沒有播放清單資料')
      }

      let successCount = 0
      let failCount = 0
      const errors = []
      let totalItemsImported = 0
      let totalItemsFailed = 0

      // 逐一匯入播放清單
      for (const playlistData of importData.playlists) {
        try {
          console.log(`Importing playlist: ${playlistData.name}`)

          // 建立播放清單
          const newPlaylist = await createPlaylist({
            name: playlistData.name,
            description: playlistData.description,
            is_active: playlistData.is_active !== false
          })

          console.log(`Created playlist: ${newPlaylist.name} (ID: ${newPlaylist.id})`)

          // 匯入播放清單中的影片
          if (playlistData.items && Array.isArray(playlistData.items)) {
            console.log(`Importing ${playlistData.items.length} items for playlist: ${playlistData.name}`)

            for (const item of playlistData.items) {
              try {
                if (!item.video_id) {
                  console.warn('Item missing video_id, skipping:', item)
                  totalItemsFailed++
                  continue
                }

                // 先嘗試取得影片
                let video = await videoStore.getVideoByYoutubeId(item.video_id)

                if (!video) {
                  // 如果影片不存在，先建立影片
                  console.log(`Creating new video: ${item.video_id} - ${item.title}`)
                  video = await videoStore.createVideo({
                    video_id: item.video_id,
                    title: item.title || 'Untitled',
                    youtube_url: item.youtube_url,
                    thumbnail_url: item.thumbnail_url,
                    duration: item.duration
                  })
                  console.log(`Video created with ID: ${video.id}`)
                } else {
                  console.log(`Video already exists: ${item.video_id} (ID: ${video.id})`)
                }

                // 然後加入到播放清單 (使用資料庫 ID)
                if (video && video.id) {
                  console.log(`Adding video ${video.id} to playlist ${newPlaylist.id}`)
                  await addItemToPlaylist(newPlaylist.id, video.id)
                  totalItemsImported++
                  console.log(`Successfully added item ${totalItemsImported}`)
                } else {
                  console.error('Video object missing ID:', item.video_id, video)
                  totalItemsFailed++
                }
              } catch (itemErr) {
                console.error(`Error adding item to playlist (${item.video_id}):`, itemErr)
                totalItemsFailed++
              }
            }
          }

          successCount++
          console.log(`Playlist import complete: ${playlistData.name}`)
        } catch (err) {
          console.error('Error importing playlist:', playlistData.name, err)
          failCount++
          errors.push({ name: playlistData.name, error: err.message })
        }
      }

      console.log(`Import summary: ${successCount} playlists, ${totalItemsImported} items imported, ${totalItemsFailed} items failed`)

      // 重新載入播放清單
      await fetchPlaylists()

      return {
        success: true,
        successCount,
        failCount,
        total: importData.playlists.length,
        totalItemsImported,
        totalItemsFailed,
        errors
      }
    } catch (err) {
      console.error('Error importing playlists:', err)
      error.value = err.message || '匯入失敗'
      throw err
    } finally {
      loading.value = false
    }
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
    exportPlaylists,
    importPlaylists,
  }
})
