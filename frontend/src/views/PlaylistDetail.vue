<template>
  <div class="playlist-detail">
    <div v-if="!loading" class="detail-container">
      <!-- Header -->
      <div class="detail-header">
        <router-link to="/playlists" class="back-button" v-tooltip="'返回播放清單列表'" aria-label="返回">
          <ArrowLeftIcon class="icon" />
          返回
        </router-link>
        <div class="header-content">
          <h1>
            <QueueListIcon class="header-icon" />
            {{ playlist?.name }}
          </h1>
          <p class="description">{{ playlist?.description }}</p>
          <div class="meta">
            <span class="meta-item">
              <FilmIcon class="meta-icon" />
              {{ items.length }} 個影片
            </span>
            <span class="meta-item">
              <CalendarIcon class="meta-icon" />
              建立於 {{ formatDate(playlist?.created_at) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Player Section -->
      <div class="player-section">
        <div class="player-placeholder">
          <div class="video-info">
            <span v-if="currentItem" class="status">
              Now Playing: {{ currentIndex + 1 }}/{{ items.length }}
            </span>
            <span v-else class="status">Select a video to play</span>
          </div>
        </div>

        <!-- Playback Controls -->
        <PlaylistControls
          v-if="items.length > 0"
          :current-index="currentIndex"
          :total-items="items.length"
          :is-playing="isPlaying"
          @prev="playPrevious"
          @next="playNext"
          @play="togglePlayback"
        />
      </div>

      <!-- Videos List -->
      <div class="videos-section">
        <h2>
          <FilmIcon class="section-icon" />
          播放清單影片
        </h2>
        <div v-if="items.length === 0" class="empty-state">
          <VideoCameraSlashIcon class="empty-icon" />
          <p>此播放清單尚無影片</p>
        </div>

        <div v-else class="videos-list">
          <div
            v-for="(item, index) in items"
            :key="item.id"
            :class="['video-item', { 'is-current': index === currentIndex }]"
            @click="selectVideo(index)"
          >
            <div class="video-number">{{ index + 1 }}</div>
            <div class="video-info-detail">
              <h3>{{ item.title }}</h3>
              <p class="duration">
                <ClockIcon class="duration-icon" />
                {{ formatDuration(item.duration) }}
              </p>
            </div>
            <div class="video-actions">
              <button
                @click.stop="removeVideo(item.video_id)"
                class="btn-remove"
                v-tooltip="'從播放清單移除'"
                aria-label="移除影片"
              >
                <TrashIcon class="icon" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <LoadingSpinner v-else size="large" message="載入播放清單中..." />

    <!-- Confirm Delete Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteModal" class="modal-overlay" @click="cancelRemove">
          <div class="modal confirm-modal" @click.stop role="dialog">
            <div class="modal-header">
              <h2>確認移除</h2>
            </div>
            <div class="modal-body">
              <p>確定要從播放清單移除此影片嗎？</p>
            </div>
            <div class="modal-footer">
              <button @click="cancelRemove" class="btn btn-secondary">取消</button>
              <button @click="confirmRemove" class="btn btn-danger">移除</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePlaylistStore } from '@/stores/playlistStore'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'
import { useToast } from '@/composables/useToast'
import PlaylistControls from '@/components/PlaylistControls.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import {
  ArrowLeftIcon,
  QueueListIcon,
  FilmIcon,
  CalendarIcon,
  VideoCameraSlashIcon,
  ClockIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

const route = useRoute()
const playlistStore = usePlaylistStore()
const globalPlayerStore = useGlobalPlayerStore()
const toast = useToast()

const loading = ref(true)
const playlist = ref(null)
const items = ref([])

// 使用 computed 從 globalPlayerStore 取得狀態，而不是本地 ref
const currentIndex = computed(() => globalPlayerStore.currentIndex)
const isPlaying = computed(() => globalPlayerStore.isPlaying)

const currentItem = computed(() => items.value[currentIndex.value])

// 監聽 globalPlayerStore 的變化，同步到本地顯示
watch(() => globalPlayerStore.currentVideo, (newVideo) => {
  console.log('PlaylistDetail: globalPlayerStore.currentVideo changed', newVideo?.title)
})

onMounted(async () => {
  const playlistId = route.params.id
  console.log('PlaylistDetail: onMounted, playlistId:', playlistId)
  console.log('PlaylistDetail: Current globalPlayerStore state:', {
    isVisible: globalPlayerStore.isVisible,
    isPlaying: globalPlayerStore.isPlaying,
    currentVideo: globalPlayerStore.currentVideo?.title,
    hasPlaylist: globalPlayerStore.hasPlaylist
  })

  try {
    const playlistData = await playlistStore.getPlaylist(playlistId)
    if (playlistData) {
      playlist.value = playlistData
      items.value = playlistData.items || []
      console.log('PlaylistDetail: Loaded playlist:', playlistData.name, 'with', items.value.length, 'items')
    }
    loading.value = false
  } catch (error) {
    console.error('Failed to load playlist:', error)
    loading.value = false
  }
})

const selectVideo = (index) => {
  // Play the playlist using global player store
  // 不需要設置本地狀態，globalPlayerStore 會自動更新
  if (playlist.value && items.value.length > 0) {
    globalPlayerStore.playPlaylist({
      id: playlist.value.id,
      name: playlist.value.name,
      items: items.value
    }, index)
  }
}

const playNext = () => {
  // 直接調用 globalPlayerStore，不需要本地狀態管理
  globalPlayerStore.next()
}

const playPrevious = () => {
  // 直接調用 globalPlayerStore，不需要本地狀態管理
  globalPlayerStore.previous()
}

const togglePlayback = () => {
  // If global player is not visible yet, start playing the playlist from the current index
  if (!globalPlayerStore.isVisible && playlist.value && items.value.length > 0) {
    globalPlayerStore.playPlaylist({
      id: playlist.value.id,
      name: playlist.value.name,
      items: items.value
    }, currentIndex.value)
  } else {
    globalPlayerStore.togglePlay()
  }
  // 不需要設置本地 isPlaying，computed 會自動從 store 取得
}

const showDeleteModal = ref(false)
const deletingVideoId = ref(null)

const removeVideo = (videoId) => {
  deletingVideoId.value = videoId
  showDeleteModal.value = true
}

const confirmRemove = async () => {
  if (!deletingVideoId.value) return

  try {
    const playlistId = route.params.id
    await playlistStore.removeItemFromPlaylist(playlistId, deletingVideoId.value)

    // Remove from local items array
    const index = items.value.findIndex(item => item.video_id === deletingVideoId.value)
    if (index > -1) {
      items.value.splice(index, 1)
    }
    // Update playlist item count
    if (playlist.value) {
      playlist.value.item_count = Math.max(0, (playlist.value.item_count || 1) - 1)
    }

    toast.success('影片已從播放清單移除')
    showDeleteModal.value = false
    deletingVideoId.value = null
  } catch (error) {
    console.error('Failed to remove video:', error)
    toast.error('移除影片失敗: ' + error.message)
  }
}

const cancelRemove = () => {
  showDeleteModal.value = false
  deletingVideoId.value = null
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

const formatDuration = (seconds) => {
  if (!seconds) return ''
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
  }
  return `${minutes}:${String(secs).padStart(2, '0')}`
}
</script>

<style scoped>
.playlist-detail {
  min-height: 100vh;
  background: var(--bg-primary);
  padding: var(--space-5);
}

.detail-container {
  max-width: 900px;
  margin: 0 auto;
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  margin-bottom: var(--space-4);
  padding: var(--space-2) var(--space-3);
  color: var(--color-info);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  font-weight: var(--font-weight-medium);
}

.back-button:hover {
  background: var(--color-info-alpha);
  transform: translateX(-4px);
}

.back-button .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.detail-header {
  background: white;
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  margin-bottom: var(--space-6);
  box-shadow: var(--shadow-sm);
}

.header-content h1 {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin: 0 0 var(--space-3) 0;
  font-size: var(--font-size-3xl);
  color: var(--text-primary);
}

.header-icon {
  width: var(--icon-xl);
  height: var(--icon-xl);
  color: var(--color-brand-primary);
}

.description {
  margin: 0 0 var(--space-4) 0;
  color: var(--text-secondary);
  font-size: var(--font-size-base);
  line-height: var(--line-height-relaxed);
}

.meta {
  display: flex;
  gap: var(--space-6);
  color: var(--text-tertiary);
  font-size: var(--font-size-sm);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.meta-icon {
  width: var(--icon-sm);
  height: var(--icon-sm);
}

.player-section {
  background: white;
  border-radius: var(--radius-md);
  padding: 24px;
  margin-bottom: 24px;
}

.player-placeholder {
  background: #333;
  border-radius: var(--radius-sm);
  padding: 60px 20px;
  text-align: center;
  margin-bottom: 20px;
}

.video-info {
  color: white;
  font-size: 16px;
}

.status {
  color: #fff;
}

.videos-section {
  background: white;
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  box-shadow: var(--shadow-sm);
}

.videos-section h2 {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin-top: 0;
  margin-bottom: var(--space-4);
  font-size: var(--font-size-xl);
  color: var(--text-primary);
}

.section-icon {
  width: var(--icon-lg);
  height: var(--icon-lg);
  color: var(--color-brand-primary);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  text-align: center;
  padding: var(--space-12) var(--space-5);
  color: var(--text-tertiary);
}

.empty-icon {
  width: 64px;
  height: 64px;
  color: var(--color-neutral-300);
}

.videos-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.video-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background 0.2s;
}

.video-item:hover {
  background: #f9f9f9;
}

.video-item.is-current {
  background: #e3f2fd;
  border-color: #1976d2;
}

.video-number {
  min-width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f0f0;
  border-radius: var(--radius-sm);
  font-weight: 600;
}

.video-info-detail {
  flex: 1;
  min-width: 0;
}

.video-info-detail h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.video-info-detail .duration {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  margin: 0;
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
}

.duration-icon {
  width: 14px;
  height: 14px;
}

.video-actions {
  display: flex;
  gap: var(--space-2);
}

.btn-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-error);
  transition: all var(--transition-fast);
}

.btn-remove:hover {
  background: var(--color-error-alpha);
}

.btn-remove .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}


/* Modal Styles */
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
  box-shadow: var(--shadow-2xl);
}

.confirm-modal .modal-header {
  padding: var(--space-5);
  border-bottom: 1px solid var(--border-color);
}

.confirm-modal .modal-header h2 {
  margin: 0;
  font-size: var(--font-size-xl);
  color: var(--text-primary);
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

.btn-danger {
  background: var(--color-error);
  color: white;
}

.btn-danger:hover {
  background: var(--color-error-dark);
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

/* 響應式設計 */
@media (max-width: 768px) {
  .playlist-detail {
    padding: var(--space-4);
  }

  .detail-header {
    padding: var(--space-4);
  }

  .meta {
    flex-direction: column;
    gap: var(--space-2);
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .back-button:hover,
  .btn-remove:hover,
  .modal-enter-active,
  .modal-leave-active {
    transition: none;
  }

  .back-button:hover {
    transform: none;
  }
}
</style>
