<template>
  <div class="video-card">
    <div class="thumbnail-container">
      <img
        v-if="video.thumbnail_url"
        :src="video.thumbnail_url"
        :alt="video.title"
        class="thumbnail"
      />
      <div v-else class="thumbnail-placeholder">
        <span>📹</span>
      </div>
      <div class="duration" v-if="video.duration">
        {{ formatDuration(video.duration) }}
      </div>
    </div>

    <div class="video-info">
      <h3 class="title">{{ video.title }}</h3>
      <p class="channel">{{ video.channel_name }}</p>
      <p class="description">{{ truncateText(video.description, 100) }}</p>
      <div class="actions">
        <button @click="handlePlay" class="btn btn-primary">播放</button>
        <button @click="handleAddToPlaylist" class="btn btn-secondary">
          加入播放清單
        </button>
        <button @click="handleDelete" class="btn btn-danger" v-if="showDelete">
          刪除
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  video: {
    type: Object,
    required: true,
  },
  showDelete: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['play', 'add-to-playlist', 'delete'])

const formatDuration = (seconds) => {
  if (!seconds) return ''
  const hrs = Math.floor(seconds / 3600)
  const mins = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  if (hrs > 0) return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const handlePlay = () => {
  emit('play', props.video)
}

const handleAddToPlaylist = () => {
  emit('add-to-playlist', props.video)
}

const handleDelete = () => {
  if (confirm(`確定要刪除 "${props.video.title}" 嗎？`)) {
    emit('delete', props.video.id)
  }
}
</script>

<style scoped>
.video-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.video-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.thumbnail-container {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%;
  background: #f0f0f0;
}

.thumbnail,
.thumbnail-placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.thumbnail {
  object-fit: cover;
}

.thumbnail-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.duration {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.video-info {
  padding: 16px;
}

.title {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  line-height: 1.4;
  max-height: 2.8em;
  overflow: hidden;
}

.channel {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #666;
}

.description {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #999;
  line-height: 1.4;
}

.actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn {
  flex: 1;
  min-width: 80px;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #667eea;
  color: white;
}

.btn-primary:hover {
  background-color: #5568d3;
}

.btn-secondary {
  background-color: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background-color: #d0d0d0;
}

.btn-danger {
  background-color: #ff6b6b;
  color: white;
}

.btn-danger:hover {
  background-color: #ff5252;
}
</style>
