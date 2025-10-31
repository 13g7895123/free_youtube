<template>
  <div class="save-actions">
    <!-- 主加入按鈕 -->
    <div class="add-button-container">
      <button
        @click="toggleMenu"
        class="btn-add"
        :disabled="saving"
        v-tooltip="'加入影片'"
        aria-label="加入影片"
        aria-haspopup="true"
        :aria-expanded="showMenu"
      >
        <PlusIcon class="icon" />
        <span class="btn-text">加入</span>
        <ChevronDownIcon class="icon-chevron" :class="{ 'rotate': showMenu }" />
      </button>

      <!-- 下拉選單 -->
      <Transition name="menu">
        <div v-if="showMenu" class="dropdown-menu" role="menu">
          <button
            @click="saveToLibrary"
            class="menu-item"
            role="menuitem"
            :disabled="saving"
          >
            <FilmIcon class="menu-icon" />
            <span>加入影片庫</span>
          </button>
          <button
            @click="openPlaylistModal"
            class="menu-item"
            role="menuitem"
            :disabled="saving"
          >
            <QueueListIcon class="menu-icon" />
            <span>加入播放清單</span>
            <ChevronRightIcon class="menu-arrow" />
          </button>
        </div>
      </Transition>
    </div>

    <!-- 播放清單選擇 Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showPlaylistModal" class="modal-overlay" @click="showPlaylistModal = false">
          <div class="modal" @click.stop role="dialog" aria-labelledby="modal-title" aria-modal="true">
            <div class="modal-header">
              <h3 id="modal-title">選擇播放清單</h3>
              <button
                @click="showPlaylistModal = false"
                class="btn-close"
                v-tooltip="'關閉'"
                aria-label="關閉"
              >
                <XMarkIcon class="icon-close" />
              </button>
            </div>

            <div class="modal-body">
              <LoadingSpinner v-if="loadingPlaylists" size="medium" message="載入播放清單..." />

              <div v-else-if="playlists.length === 0" class="empty">
                <FolderOpenIcon class="empty-icon" />
                <p>還沒有播放清單</p>
                <button @click="goToPlaylistManager" class="btn-secondary">
                  <PlusCircleIcon class="icon-sm" />
                  建立播放清單
                </button>
              </div>

              <div v-else class="playlist-list">
                <button
                  v-for="playlist in playlists"
                  :key="playlist.id"
                  @click="addToPlaylist(playlist.id)"
                  class="playlist-item"
                >
                  <div class="playlist-info">
                    <QueueListIcon class="playlist-icon" />
                    <div class="playlist-text">
                      <h4>{{ playlist.name }}</h4>
                      <p>{{ playlist.item_count }} 個影片</p>
                    </div>
                  </div>
                  <ChevronRightIcon class="playlist-action" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { videoService, playlistService } from '@/services/api'
import { useToast } from '@/composables/useToast'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import {
  PlusIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  FilmIcon,
  QueueListIcon,
  XMarkIcon,
  FolderOpenIcon,
  PlusCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  getVideoInfo: {
    type: Function,
    required: true
  }
})

const toast = useToast()
const showMenu = ref(false)
const showPlaylistModal = ref(false)
const playlists = ref([])
const loadingPlaylists = ref(false)
const saving = ref(false)

// 切換選單
const toggleMenu = () => {
  showMenu.value = !showMenu.value
}

// 點擊外部關閉選單
const handleClickOutside = (event) => {
  const container = document.querySelector('.add-button-container')
  if (container && !container.contains(event.target)) {
    showMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// 載入播放清單
const loadPlaylists = async () => {
  loadingPlaylists.value = true
  try {
    const response = await playlistService.getPlaylists(1, 50)
    playlists.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load playlists:', error)
    toast.error('載入播放清單失敗')
  } finally {
    loadingPlaylists.value = false
  }
}

// 加入影片庫
const saveToLibrary = async () => {
  showMenu.value = false
  const videoInfo = props.getVideoInfo()
  if (!videoInfo) {
    toast.error('無法取得影片資訊')
    return
  }

  saving.value = true
  try {
    // 檢查影片是否已存在
    const checkResponse = await videoService.checkVideoExists(videoInfo.videoId)
    if (checkResponse.data.data.exists) {
      toast.warning('此影片已在影片庫中')
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

    toast.success('成功加入影片庫！')
  } catch (error) {
    console.error('Failed to save video:', error)
    if (error.response?.status === 409) {
      toast.warning('此影片已在影片庫中')
    } else {
      toast.error('加入影片庫失敗')
    }
  } finally {
    saving.value = false
  }
}

// 打開播放清單 Modal
const openPlaylistModal = () => {
  showMenu.value = false
  showPlaylistModal.value = true
}

// 加入播放清單
const addToPlaylist = async (playlistId) => {
  const videoInfo = props.getVideoInfo()
  if (!videoInfo) {
    toast.error('無法取得影片資訊')
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
      toast.error('加入播放清單失敗')
      return
    }

    if (!videoDbId) {
      toast.error('無法取得影片 ID')
      return
    }

    // 加入播放清單
    await playlistService.addItemToPlaylist(playlistId, videoDbId)
    toast.success('成功加入播放清單！')
    showPlaylistModal.value = false
  } catch (error) {
    console.error('Failed to add to playlist:', error)
    if (error.response?.status === 409) {
      toast.warning('此影片已在播放清單中')
    } else {
      toast.error('加入播放清單失敗')
    }
  } finally {
    saving.value = false
  }
}

// 前往播放清單管理頁面
const goToPlaylistManager = () => {
  window.location.href = '/playlists'
}

// 監聽 modal 開啟
watch(showPlaylistModal, (newValue) => {
  if (newValue) {
    loadPlaylists()
  }
})
</script>

<style scoped>
.save-actions {
  position: relative;
  display: flex;
  justify-content: center;
}

.add-button-container {
  position: relative;
}

.btn-add {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-5);
  background: var(--color-brand-primary);
  color: white;
  border: none;
  border-radius: var(--radius-lg);
  cursor: pointer;
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  transition: all var(--transition-fast);
  box-shadow: var(--shadow-sm);
  min-height: var(--touch-target-comfortable);
}

.btn-add:hover:not(:disabled) {
  background: var(--color-brand-primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn-add:active:not(:disabled) {
  transform: translateY(0);
}

.btn-add:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-add .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.btn-add .icon-chevron {
  width: var(--icon-sm);
  height: var(--icon-sm);
  transition: transform var(--transition-fast);
}

.btn-add .icon-chevron.rotate {
  transform: rotate(180deg);
}

.btn-text {
  white-space: nowrap;
}

/* 下拉選單 */
.dropdown-menu {
  position: absolute;
  top: calc(100% + var(--space-2));
  left: 0;
  right: 0;
  min-width: 200px;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  padding: var(--space-2);
  z-index: var(--z-dropdown);
}

.menu-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  width: 100%;
  padding: var(--space-3);
  background: transparent;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: var(--font-size-sm);
  color: var(--text-primary);
  transition: background var(--transition-fast);
  text-align: left;
}

.menu-item:hover:not(:disabled) {
  background: var(--color-neutral-100);
}

.menu-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.menu-item .menu-icon {
  width: var(--icon-md);
  height: var(--icon-md);
  color: var(--text-secondary);
  flex-shrink: 0;
}

.menu-item .menu-arrow {
  width: var(--icon-sm);
  height: var(--icon-sm);
  color: var(--text-tertiary);
  margin-left: auto;
}

/* 選單動畫 */
.menu-enter-active,
.menu-leave-active {
  transition: all var(--transition-fast);
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--bg-overlay);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-modal-backdrop);
  padding: var(--space-5);
}

.modal {
  background: white;
  border-radius: var(--radius-xl);
  max-width: 500px;
  width: 100%;
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

.modal-header h3 {
  margin: 0;
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.btn-close {
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-secondary);
  padding: var(--space-2);
  width: 32px;
  height: 32px;
  border-radius: var(--radius-full);
  transition: all var(--transition-fast);
}

.btn-close:hover {
  background: var(--color-neutral-100);
  color: var(--text-primary);
}

.btn-close .icon-close {
  width: var(--icon-md);
  height: var(--icon-md);
}

.modal-body {
  padding: var(--space-5);
  overflow-y: auto;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-4);
  text-align: center;
  padding: var(--space-10);
  color: var(--text-secondary);
}

.empty-icon {
  width: 64px;
  height: 64px;
  color: var(--text-tertiary);
}

.empty p {
  margin: 0;
  font-size: var(--font-size-base);
}

.btn-secondary {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  background: var(--color-neutral-700);
  color: white;
  padding: var(--space-3) var(--space-5);
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  transition: all var(--transition-fast);
}

.btn-secondary:hover {
  background: var(--color-neutral-800);
}

.btn-secondary .icon-sm {
  width: var(--icon-sm);
  height: var(--icon-sm);
}

.playlist-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.playlist-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.playlist-info {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.playlist-icon {
  width: var(--icon-lg);
  height: var(--icon-lg);
  color: var(--color-info);
  flex-shrink: 0;
}

.playlist-text h4 {
  margin: 0 0 var(--space-1) 0;
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.playlist-text p {
  margin: 0;
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.playlist-action {
  width: var(--icon-md);
  height: var(--icon-md);
  color: var(--color-info);
  flex-shrink: 0;
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

/* 響應式 */
@media (max-width: 480px) {
  .btn-add {
    padding: var(--space-3) var(--space-4);
    font-size: var(--font-size-sm);
  }

  .modal {
    max-height: 90vh;
  }

  .modal-header,
  .modal-body {
    padding: var(--space-4);
  }

  .playlist-item {
    padding: var(--space-3);
  }
}

@media (max-width: 360px) {
  .btn-text {
    display: none;
  }
}
</style>
