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
              <h3>{{ item.video?.title }}</h3>
              <p>{{ formatDuration(item.video?.duration) }}</p>
            </div>
            <div class="video-actions">
              <button @click.stop="removeVideo(item.id)" class="btn-remove">
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
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePlaylistStore } from '@/stores/playlistStore'
import PlaylistControls from '@/components/PlaylistControls.vue'

const route = useRoute()
const playlistStore = usePlaylistStore()

const loading = ref(true)
const playlist = ref(null)
const items = ref([])
const currentIndex = ref(0)
const isPlaying = ref(false)

const currentItem = computed(() => items.value[currentIndex.value])

onMounted(async () => {
  const playlistId = route.params.id
  try {
    await playlistStore.fetchPlaylist(playlistId)
    playlist.value = playlistStore.currentPlaylist
    await playlistStore.fetchPlaylistItems(playlistId)
    items.value = playlistStore.playlistItems
    loading.value = false
  } catch (error) {
    console.error('Failed to load playlist:', error)
    loading.value = false
  }
})

const selectVideo = (index) => {
  currentIndex.value = index
  isPlaying.value = true
}

const playNext = () => {
  if (currentIndex.value < items.value.length - 1) {
    currentIndex.value++
  } else {
    currentIndex.value = 0
  }
  isPlaying.value = true
}

const playPrevious = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  } else {
    currentIndex.value = items.value.length - 1
  }
  isPlaying.value = true
}

const togglePlayback = () => {
  isPlaying.value = !isPlaying.value
}

const removeVideo = async (itemId) => {
  if (confirm('Remove this video from the playlist?')) {
    try {
      await playlistStore.removePlaylistItem(itemId)
      const index = items.value.findIndex(item => item.id === itemId)
      if (index > -1) {
        items.value.splice(index, 1)
      }
    } catch (error) {
      console.error('Failed to remove video:', error)
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
