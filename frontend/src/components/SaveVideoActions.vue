<template>
  <div class="save-actions">
    <div class="actions-container">
      <button @click="saveToLibrary" class="btn btn-library" :disabled="saving">
        <span class="btn-icon">📚</span>
        <span class="btn-text">加入影片庫</span>
      </button>

      <button @click="showPlaylistModal = true" class="btn btn-playlist" :disabled="saving">
        <span class="btn-icon">📋</span>
        <span class="btn-text">加入播放清單</span>
      </button>
    </div>

    <!-- 播放清單選擇 Modal -->
    <div v-if="showPlaylistModal" class="modal-overlay" @click="showPlaylistModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>選擇播放清單</h3>
          <button @click="showPlaylistModal = false" class="btn-close">✕</button>
        </div>

        <div class="modal-body">
          <div v-if="loadingPlaylists" class="loading">載入中...</div>

          <div v-else-if="playlists.length === 0" class="empty">
            <p>還沒有播放清單</p>
            <button @click="goToPlaylistManager" class="btn btn-secondary">
              建立播放清單
            </button>
          </div>

          <div v-else class="playlist-list">
            <div
              v-for="playlist in playlists"
              :key="playlist.id"
              @click="addToPlaylist(playlist.id)"
              class="playlist-item"
            >
              <div class="playlist-info">
                <h4>{{ playlist.name }}</h4>
                <p>{{ playlist.item_count }} 個影片</p>
              </div>
              <div class="playlist-action">→</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 成功/錯誤訊息 Toast -->
    <div v-if="message" :class="['toast', messageType]">
      {{ message }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { videoService, playlistService } from '@/services/api'

const props = defineProps({
  getVideoInfo: {
    type: Function,
    required: true
  }
})

const showPlaylistModal = ref(false)
const playlists = ref([])
const loadingPlaylists = ref(false)
const saving = ref(false)
const message = ref('')
const messageType = ref('success') // 'success' or 'error'

// 載入播放清單
const loadPlaylists = async () => {
  loadingPlaylists.value = true
  try {
    const response = await playlistService.getPlaylists(1, 50)
    playlists.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load playlists:', error)
    showMessage('載入播放清單失敗', 'error')
  } finally {
    loadingPlaylists.value = false
  }
}

// 加入影片庫
const saveToLibrary = async () => {
  const videoInfo = props.getVideoInfo()
  if (!videoInfo) {
    showMessage('無法取得影片資訊', 'error')
    return
  }

  saving.value = true
  try {
    // 檢查影片是否已存在
    const checkResponse = await videoService.checkVideoExists(videoInfo.videoId)
    if (checkResponse.data.data.exists) {
      showMessage('此影片已在影片庫中', 'error')
      return
    }

    // 建立影片記錄
    await videoService.createVideo({
      video_id: videoInfo.videoId,
      title: videoInfo.title,
      youtube_url: videoInfo.youtubeUrl,
      thumbnail_url: videoInfo.thumbnail,
      duration: Math.floor(videoInfo.duration),
      channel_name: videoInfo.author
    })

    showMessage('成功加入影片庫！', 'success')
  } catch (error) {
    console.error('Failed to save video:', error)
    if (error.response?.status === 409) {
      showMessage('此影片已在影片庫中', 'error')
    } else {
      showMessage('加入影片庫失敗', 'error')
    }
  } finally {
    saving.value = false
  }
}

// 加入播放清單
const addToPlaylist = async (playlistId) => {
  const videoInfo = props.getVideoInfo()
  if (!videoInfo) {
    showMessage('無法取得影片資訊', 'error')
    return
  }

  saving.value = true
  try {
    // 先確保影片在影片庫中
    let videoDbId
    try {
      const checkResponse = await videoService.checkVideoExists(videoInfo.videoId)
      if (checkResponse.data.data.exists) {
        // 影片已存在，需要取得其 ID
        const videos = await videoService.searchVideos(videoInfo.videoId)
        videoDbId = videos.data.data.find(v => v.video_id === videoInfo.videoId)?.id
      } else {
        // 建立新影片記錄
        const createResponse = await videoService.createVideo({
          video_id: videoInfo.videoId,
          title: videoInfo.title,
          youtube_url: videoInfo.youtubeUrl,
          thumbnail_url: videoInfo.thumbnail,
          duration: Math.floor(videoInfo.duration),
          channel_name: videoInfo.author
        })
        videoDbId = createResponse.data.data.id
      }
    } catch (error) {
      console.error('Failed to ensure video exists:', error)
      showMessage('加入播放清單失敗', 'error')
      return
    }

    if (!videoDbId) {
      showMessage('無法取得影片 ID', 'error')
      return
    }

    // 加入播放清單
    await playlistService.addItemToPlaylist(playlistId, videoDbId)
    showMessage('成功加入播放清單！', 'success')
    showPlaylistModal.value = false
  } catch (error) {
    console.error('Failed to add to playlist:', error)
    if (error.response?.status === 409) {
      showMessage('此影片已在播放清單中', 'error')
    } else {
      showMessage('加入播放清單失敗', 'error')
    }
  } finally {
    saving.value = false
  }
}

// 前往播放清單管理頁面
const goToPlaylistManager = () => {
  window.location.href = '/playlists'
}

// 顯示訊息
const showMessage = (text, type = 'success') => {
  message.value = text
  messageType.value = type
  setTimeout(() => {
    message.value = ''
  }, 3000)
}

// 當 modal 打開時載入播放清單
const handleModalOpen = () => {
  if (showPlaylistModal.value) {
    loadPlaylists()
  }
}

// 監聽 modal 開啟
import { watch } from 'vue'
watch(showPlaylistModal, (newValue) => {
  if (newValue) {
    loadPlaylists()
  }
})
</script>

<style scoped>
.save-actions {
  position: relative;
}

.actions-container {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-library {
  background: #4caf50;
  color: white;
}

.btn-library:hover:not(:disabled) {
  background: #45a049;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.btn-playlist {
  background: #2196f3;
  color: white;
}

.btn-playlist:hover:not(:disabled) {
  background: #1976d2;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

.btn-icon {
  font-size: 18px;
}

.btn-text {
  white-space: nowrap;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.btn-close:hover {
  background: #f5f5f5;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
}

.loading,
.empty {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.empty p {
  margin: 0 0 16px 0;
}

.btn-secondary {
  background: #757575;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary:hover {
  background: #616161;
}

.playlist-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.playlist-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.playlist-item:hover {
  background: #f5f5f5;
  border-color: #2196f3;
}

.playlist-info h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  color: #212121;
}

.playlist-info p {
  margin: 0;
  font-size: 14px;
  color: #757575;
}

.playlist-action {
  font-size: 20px;
  color: #2196f3;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 24px;
  border-radius: 8px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 2000;
  animation: slideUp 0.3s ease;
}

.toast.success {
  background: #4caf50;
}

.toast.error {
  background: #f44336;
}

@keyframes slideUp {
  from {
    transform: translateX(-50%) translateY(100px);
    opacity: 0;
  }
  to {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
  }
}

/* 響應式 */
@media (max-width: 480px) {
  .btn {
    padding: 10px 16px;
    font-size: 13px;
  }

  .btn-icon {
    font-size: 16px;
  }

  .modal {
    max-height: 90vh;
  }

  .modal-header {
    padding: 16px;
  }

  .modal-body {
    padding: 16px;
  }
}
</style>
