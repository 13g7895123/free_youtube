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
        <VideoCameraIcon class="placeholder-icon" />
      </div>
      <div class="duration" v-if="video.duration">
        <ClockIcon class="duration-icon" />
        {{ formatDuration(video.duration) }}
      </div>
    </div>

    <div class="video-info">
      <h3 class="title">{{ video.title }}</h3>
      <p class="channel" v-if="video.channel_name">{{ video.channel_name }}</p>
      <p class="description" v-if="video.description">{{ truncateText(video.description, 100) }}</p>
      <div class="actions">
        <button
          @click="handlePlay"
          class="btn btn-primary"
          v-tooltip="'播放影片'"
          aria-label="播放影片"
        >
          <PlayIcon class="icon" />
        </button>
        <button
          @click="handleAddToPlaylist"
          class="btn btn-secondary"
          v-tooltip="'加入播放清單'"
          aria-label="加入播放清單"
        >
          <PlusIcon class="icon" />
        </button>
        <button
          v-if="showDelete"
          @click="handleDelete"
          class="btn btn-danger"
          v-tooltip="'刪除影片'"
          aria-label="刪除影片"
        >
          <TrashIcon class="icon" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import {
  VideoCameraIcon,
  ClockIcon,
  PlayIcon,
  PlusIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

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
  // 直接 emit 給父組件處理刪除確認
  emit('delete', props.video.id)
}
</script>

<style scoped>
.video-card {
  background: white;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-fast);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.video-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.thumbnail-container {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%;
  background: var(--color-neutral-100);
  flex-shrink: 0;
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
  background: linear-gradient(135deg, var(--color-brand-primary) 0%, var(--color-brand-secondary) 100%);
}

.placeholder-icon {
  width: 64px;
  height: 64px;
  color: white;
  opacity: 0.8;
}

.duration {
  display: flex;
  align-items: center;
  gap: 4px;
  position: absolute;
  bottom: var(--space-2);
  right: var(--space-2);
  background: rgba(0, 0, 0, 0.85);
  color: white;
  padding: var(--space-1) var(--space-2);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  backdrop-filter: blur(4px);
}

.duration-icon {
  width: 12px;
  height: 12px;
}

.video-info {
  padding: var(--space-4);
  display: flex;
  flex-direction: column;
  flex: 1;
}

.title {
  margin: 0 0 var(--space-2) 0;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  line-height: var(--line-height-snug);
  max-height: 2.8em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.channel {
  margin: 0 0 var(--space-2) 0;
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  font-weight: var(--font-weight-medium);
}

.description {
  margin: 0 0 var(--space-3) 0;
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
  line-height: var(--line-height-normal);
  max-height: 3.6em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.actions {
  display: flex;
  gap: var(--space-2);
  margin-top: auto;
}

.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
  min-width: 40px;
  min-height: var(--touch-target-min);
  padding: var(--space-2);
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.btn-primary {
  background-color: var(--color-brand-primary);
  color: white;
}

.btn-primary:hover {
  background-color: var(--color-brand-primary-dark);
  transform: scale(1.05);
}

.btn-secondary {
  background-color: var(--color-neutral-200);
  color: var(--text-primary);
}

.btn-secondary:hover {
  background-color: var(--color-neutral-300);
  transform: scale(1.05);
}

.btn-danger {
  background-color: var(--color-error);
  color: white;
}

.btn-danger:hover {
  background-color: var(--color-error-dark);
  transform: scale(1.05);
}

/* 響應式設計 */
@media (max-width: 640px) {
  .video-info {
    padding: var(--space-3);
  }

  .actions {
    gap: var(--space-1);
  }

  .btn {
    min-width: 36px;
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .video-card,
  .btn {
    transition: none;
  }

  .video-card:hover,
  .btn:hover {
    transform: none;
  }
}
</style>
