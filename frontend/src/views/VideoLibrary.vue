<template>
  <div class="video-library">
    <div class="header">
      <h1>📺 影片庫</h1>
      <div class="header-actions">
        <div class="search-bar">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="搜尋影片..."
            @input="handleSearch"
            class="search-input"
          />
        </div>
        <div class="export-import-buttons">
          <button @click="handleExport" class="btn-export" title="匯出影片庫">
            📤 匯出
          </button>
          <button @click="triggerImport" class="btn-import" title="匯入影片庫">
            📥 匯入
          </button>
          <input
            ref="fileInput"
            type="file"
            accept=".json"
            @change="handleImport"
            style="display: none"
          />
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>載入中...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="fetchVideos" class="btn-retry">重新載入</button>
    </div>

    <div v-else-if="videos.length === 0" class="empty">
      <p>沒有找到影片</p>
    </div>

    <div v-else>
      <div class="video-grid">
        <VideoCard
          v-for="video in videos"
          :key="video.id"
          :video="video"
          @play="handlePlayVideo"
          @add-to-playlist="handleAddToPlaylist"
        />
      </div>

      <div class="pagination" v-if="totalPages > 1">
        <button
          @click="currentPage > 1 && fetchVideos(currentPage - 1)"
          :disabled="currentPage === 1"
          class="btn"
        >
          上一頁
        </button>
        <span>第 {{ currentPage }} / {{ totalPages }} 頁</span>
        <button
          @click="currentPage < totalPages && fetchVideos(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="btn"
        >
          下一頁
        </button>
      </div>
    </div>

    <!-- Add to Playlist Modal -->
    <div v-if="showPlaylistModal" class="modal-overlay" @click="showPlaylistModal = false">
      <div class="modal" @click.stop>
        <h2>加入播放清單</h2>
        <div class="playlist-list">
          <div
            v-for="playlist in playlists"
            :key="playlist.id"
            @click="addToPlaylist(playlist.id)"
            class="playlist-item"
          >
            {{ playlist.name }}
          </div>
        </div>
        <button @click="showPlaylistModal = false" class="btn-close">關閉</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useVideoStore } from '@/stores/videoStore'
import { usePlaylistStore } from '@/stores/playlistStore'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'
import VideoCard from '@/components/VideoCard.vue'

const videoStore = useVideoStore()
const playlistStore = usePlaylistStore()
const globalPlayerStore = useGlobalPlayerStore()

const searchQuery = ref('')
const showPlaylistModal = ref(false)
const selectedVideo = ref(null)
const fileInput = ref(null)
let searchTimeout = null

const videos = computed(() => videoStore.videos)
const loading = computed(() => videoStore.loading)
const error = computed(() => videoStore.error)
const currentPage = computed(() => videoStore.currentPage)
const totalPages = computed(() => videoStore.totalPages)
const playlists = computed(() => playlistStore.playlists)

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (searchQuery.value.length > 1) {
      videoStore.searchVideos(searchQuery.value)
    } else {
      fetchVideos()
    }
  }, 300)
}

const fetchVideos = async (page = 1) => {
  await videoStore.fetchVideos(page)
}

const handlePlayVideo = (video) => {
  console.log('Playing video:', video)
  // 使用全局播放器播放影片
  globalPlayerStore.playVideo({
    id: video.id,
    video_id: video.video_id,
    title: video.title,
    youtube_url: video.youtube_url,
    thumbnail_url: video.thumbnail_url,
    duration: video.duration
  })
}

const handleAddToPlaylist = (video) => {
  selectedVideo.value = video
  showPlaylistModal.value = true
  playlistStore.fetchPlaylists()
}

const addToPlaylist = async (playlistId) => {
  if (selectedVideo.value) {
    try {
      await playlistStore.addItemToPlaylist(playlistId, selectedVideo.value.id)
      alert('已新增到播放清單')
      showPlaylistModal.value = false
    } catch (err) {
      alert('新增失敗: ' + err.message)
    }
  }
}

const handleExport = async () => {
  try {
    const result = await videoStore.exportVideos()
    alert(`成功匯出 ${result.count} 個影片`)
  } catch (err) {
    alert('匯出失敗: ' + err.message)
  }
}

const triggerImport = () => {
  fileInput.value.click()
}

const handleImport = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (confirm('確定要匯入影片資料嗎？已存在的影片將會被略過。')) {
    try {
      const result = await videoStore.importVideos(file)
      alert(`匯入完成！\n成功: ${result.successCount}\n略過: ${result.failCount}\n總計: ${result.total}`)
    } catch (err) {
      alert('匯入失敗: ' + err.message)
    }
  }

  // Reset file input
  event.target.value = ''
}

onMounted(() => {
  fetchVideos()
})
</script>

<style scoped>
.video-library {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  margin-bottom: 24px;
}

.header h1 {
  margin: 0 0 16px 0;
  font-size: 28px;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.search-bar {
  flex: 1;
  display: flex;
}

.search-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.export-import-buttons {
  display: flex;
  gap: 8px;
}

.btn-export,
.btn-import {
  padding: 10px 16px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-export {
  background: #4caf50;
  color: white;
}

.btn-export:hover {
  background: #45a049;
}

.btn-import {
  background: #2196f3;
  color: white;
}

.btn-import:hover {
  background: #0b7dda;
}

.loading,
.error,
.empty {
  text-align: center;
  padding: 48px 24px;
  color: #666;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading p {
  margin: 0;
  font-size: 16px;
  color: #666;
}

.error {
  background: #fee;
  border-radius: 4px;
  padding: 24px;
}

.btn-retry {
  margin-top: 12px;
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.video-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.btn {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

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
}

.modal {
  background: white;
  border-radius: 8px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
}

.modal h2 {
  margin-top: 0;
}

.playlist-list {
  max-height: 300px;
  overflow-y: auto;
  margin: 16px 0;
}

.playlist-item {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  margin-bottom: 8px;
  transition: background-color 0.2s;
}

.playlist-item:hover {
  background-color: #f0f0f0;
}

.btn-close {
  width: 100%;
  padding: 8px 16px;
  background: #e0e0e0;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>
