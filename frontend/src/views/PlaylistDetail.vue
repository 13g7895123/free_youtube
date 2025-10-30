<template>
  <div class="playlist-detail">
    <div v-if="!loading" class="detail-container">
      <!-- Header -->
      <div class="detail-header">
        <div class="back-button">
          <router-link to="/playlists">← Back</router-link>
        </div>
        <div class="header-content">
          <h1>{{ playlist?.name }}</h1>
          <p class="description">{{ playlist?.description }}</p>
          <div class="meta">
            <span>{{ items.length }} videos</span>
            <span>Created {{ formatDate(playlist?.created_at) }}</span>
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
        <h2>Videos in Playlist</h2>
        <div v-if="items.length === 0" class="empty-state">
          <p>No videos in this playlist yet</p>
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
              <p>{{ formatDuration(item.duration) }}</p>
            </div>
            <div class="video-actions">
              <button @click.stop="removeVideo(item.video_id)" class="btn-remove">
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="loading">Loading...</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePlaylistStore } from '@/stores/playlistStore'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'
import PlaylistControls from '@/components/PlaylistControls.vue'

const route = useRoute()
const playlistStore = usePlaylistStore()
const globalPlayerStore = useGlobalPlayerStore()

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

const removeVideo = async (videoId) => {
  if (confirm('Remove this video from the playlist?')) {
    try {
      const playlistId = route.params.id
      await playlistStore.removeItemFromPlaylist(playlistId, videoId)
      // Remove from local items array
      const index = items.value.findIndex(item => item.video_id === videoId)
      if (index > -1) {
        items.value.splice(index, 1)
      }
      // Update playlist item count
      if (playlist.value) {
        playlist.value.item_count = Math.max(0, (playlist.value.item_count || 1) - 1)
      }
    } catch (error) {
      console.error('Failed to remove video:', error)
      alert('移除影片失敗')
    }
  }
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
  background: #f5f5f5;
  padding: 20px;
}

.detail-container {
  max-width: 900px;
  margin: 0 auto;
}

.back-button {
  margin-bottom: 16px;
}

.back-button a {
  color: #1976d2;
  text-decoration: none;
}

.back-button a:hover {
  text-decoration: underline;
}

.detail-header {
  background: white;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 24px;
}

.detail-header h1 {
  margin: 0 0 8px 0;
  font-size: 32px;
}

.description {
  margin: 0 0 16px 0;
  color: #666;
  font-size: 16px;
}

.meta {
  display: flex;
  gap: 24px;
  color: #999;
  font-size: 14px;
}

.player-section {
  background: white;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 24px;
}

.player-placeholder {
  background: #333;
  border-radius: 4px;
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
  border-radius: 8px;
  padding: 24px;
}

.videos-section h2 {
  margin-top: 0;
  margin-bottom: 16px;
  font-size: 20px;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #999;
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
  border-radius: 4px;
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
  border-radius: 4px;
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

.video-info-detail p {
  margin: 0;
  font-size: 12px;
  color: #999;
}

.video-actions {
  display: flex;
  gap: 8px;
}

.btn-remove {
  background: #fee;
  border: none;
  border-radius: 4px;
  width: 32px;
  height: 32px;
  cursor: pointer;
  color: #d32f2f;
  font-size: 16px;
  transition: background 0.2s;
}

.btn-remove:hover {
  background: #fdd;
}

.loading {
  text-align: center;
  padding: 40px 20px;
  font-size: 16px;
  color: #999;
}
</style>
