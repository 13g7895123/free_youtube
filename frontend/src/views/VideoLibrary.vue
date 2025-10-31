<template>
  <div class="video-library">
    <div class="header">
      <h1>
        <FilmIcon class="header-icon" />
        影片庫
      </h1>
      <div class="header-actions">
        <div class="search-bar">
          <MagnifyingGlassIcon class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="搜尋影片..."
            @input="handleSearch"
            class="search-input"
            aria-label="搜尋影片"
          />
        </div>
        <div class="export-import-buttons">
          <button
            @click="handleExport"
            class="btn btn-success"
            aria-label="匯出影片庫"
          >
            <ArrowUpTrayIcon class="icon" />
            <span>匯出</span>
          </button>
          <button
            @click="triggerImport"
            class="btn btn-info"
            aria-label="匯入影片庫"
          >
            <ArrowDownTrayIcon class="icon" />
            <span>匯入</span>
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

    <LoadingSpinner v-if="loading" size="large" message="載入影片庫中..." />

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
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showPlaylistModal" class="modal-overlay" @click="showPlaylistModal = false">
          <div class="modal" @click.stop role="dialog" aria-labelledby="modal-playlist-title">
            <div class="modal-header">
              <h2 id="modal-playlist-title">加入播放清單</h2>
              <button
                @click="showPlaylistModal = false"
                class="btn-close-icon"
                v-tooltip="'關閉'"
                aria-label="關閉"
              >
                <XMarkIcon class="icon" />
              </button>
            </div>
            <div class="playlist-list">
              <button
                v-for="playlist in playlists"
                :key="playlist.id"
                @click="addToPlaylist(playlist.id)"
                class="playlist-item"
              >
                <QueueListIcon class="playlist-icon" />
                <span>{{ playlist.name }}</span>
                <ChevronRightIcon class="arrow-icon" />
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Confirm Import Modal -->
      <Transition name="modal">
        <div v-if="showConfirmModal" class="modal-overlay" @click="cancelImport">
          <div class="modal confirm-modal" @click.stop role="dialog">
            <div class="modal-header">
              <h2>確認匯入</h2>
            </div>
            <div class="modal-body">
              <p>確定要匯入影片資料嗎？已存在的影片將會被略過。</p>
            </div>
            <div class="modal-footer">
              <button @click="cancelImport" class="btn btn-secondary">取消</button>
              <button @click="confirmImport" class="btn btn-primary">確認匯入</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useVideoStore } from '@/stores/videoStore'
import { usePlaylistStore } from '@/stores/playlistStore'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'
import { useToast } from '@/composables/useToast'
import VideoCard from '@/components/VideoCard.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import {
  FilmIcon,
  MagnifyingGlassIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  XMarkIcon,
  QueueListIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

const toast = useToast()

const showConfirmModal = ref(false)
const pendingImportFile = ref(null)

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
      toast.success('已新增到播放清單')
      showPlaylistModal.value = false
    } catch (err) {
      toast.error('新增失敗: ' + err.message)
    }
  }
}

const handleExport = async () => {
  try {
    const result = await videoStore.exportVideos()
    toast.success(`成功匯出 ${result.count} 個影片`)
  } catch (err) {
    toast.error('匯出失敗: ' + err.message)
  }
}

const triggerImport = () => {
  fileInput.value.click()
}

const handleImport = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  pendingImportFile.value = file
  showConfirmModal.value = true
  event.target.value = ''
}

const confirmImport = async () => {
  if (!pendingImportFile.value) return

  showConfirmModal.value = false
  try {
    const result = await videoStore.importVideos(pendingImportFile.value)
    toast.success(`匯入完成！成功: ${result.successCount}，略過: ${result.failCount}`)
  } catch (err) {
    toast.error('匯入失敗: ' + err.message)
  }
  pendingImportFile.value = null
}

const cancelImport = () => {
  showConfirmModal.value = false
  pendingImportFile.value = null
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
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin: 0 0 var(--space-4) 0;
  font-size: var(--font-size-3xl);
  color: var(--text-primary);
}

.header-icon {
  width: var(--icon-xl);
  height: var(--icon-xl);
  color: var(--color-brand-primary);
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.search-bar {
  flex: 1;
  display: flex;
  align-items: center;
  position: relative;
}

.search-icon {
  position: absolute;
  left: var(--space-3);
  width: var(--icon-md);
  height: var(--icon-md);
  color: var(--text-tertiary);
  pointer-events: none;
}

.search-input {
  flex: 1;
  padding: var(--space-3) var(--space-3) var(--space-3) var(--space-10);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  transition: all var(--transition-fast);
}

.search-input:focus {
  outline: none;
  border-color: var(--color-info);
  box-shadow: 0 0 0 3px var(--color-info-alpha);
}

.export-import-buttons {
  display: flex;
  gap: var(--space-2);
}

.error,
.empty {
  text-align: center;
  padding: 48px 24px;
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
  border-radius: var(--radius-xl);
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-2xl);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-5);
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
  margin: 0;
  font-size: var(--font-size-xl);
  color: var(--text-primary);
}

.btn-close-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: var(--radius-full);
  cursor: pointer;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.btn-close-icon:hover {
  background: var(--color-neutral-100);
  color: var(--text-primary);
}

.btn-close-icon .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.playlist-list {
  max-height: 400px;
  overflow-y: auto;
  padding: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.playlist-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  width: 100%;
  padding: var(--space-4);
  background: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  text-align: left;
}

.playlist-item:hover {
  background: var(--color-neutral-50);
  border-color: var(--color-info);
  transform: translateX(4px);
}

.playlist-icon {
  width: var(--icon-lg);
  height: var(--icon-lg);
  color: var(--color-info);
  flex-shrink: 0;
}

.playlist-item span {
  flex: 1;
  font-size: var(--font-size-base);
  color: var(--text-primary);
}

.arrow-icon {
  width: var(--icon-md);
  height: var(--icon-md);
  color: var(--text-tertiary);
  flex-shrink: 0;
}

.confirm-modal .modal-body {
  padding: var(--space-5);
}

.confirm-modal .modal-body p {
  margin: 0;
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  line-height: var(--line-height-relaxed);
}

.modal-footer {
  display: flex;
  gap: var(--space-3);
  padding: var(--space-5);
  border-top: 1px solid var(--border-color);
}

.modal-footer .btn {
  flex: 1;
  padding: var(--space-3) var(--space-4);
  border: none;
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-secondary {
  background: var(--color-neutral-200);
  color: var(--text-primary);
}

.btn-secondary:hover {
  background: var(--color-neutral-300);
}

.btn-primary {
  background: var(--color-brand-primary);
  color: white;
}

.btn-primary:hover {
  background: var(--color-brand-primary-dark);
}

/* Modal 動畫 */
.modal-enter-active,
.modal-leave-active {
  transition: opacity var(--transition-base);
}

.modal-enter-active .modal,
.modal-leave-active .modal {
  transition: transform var(--transition-base);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal,
.modal-leave-to .modal {
  transform: scale(0.95) translateY(20px);
}
</style>
