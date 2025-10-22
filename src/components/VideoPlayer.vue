<template>
  <div class="video-player-container">
    <!-- YouTube 播放器容器 -->
    <div class="player-wrapper">
      <div
        id="youtube-player"
        class="youtube-player"
        :class="{ 'is-loading': isLoading }"
      ></div>

      <!-- 載入指示器 -->
      <div v-if="isLoading" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p class="loading-text">載入中...</p>
      </div>
    </div>

    <!-- 播放器資訊 -->
    <div v-if="!isLoading && isReady" class="player-info">
      <div class="status-indicator" :class="statusClass">
        <span class="status-dot"></span>
        <span class="status-text">{{ statusText }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false
  },
  isReady: {
    type: Boolean,
    default: false
  },
  isPlaying: {
    type: Boolean,
    default: false
  },
  isPaused: {
    type: Boolean,
    default: false
  },
  isBuffering: {
    type: Boolean,
    default: false
  }
})

// 計算播放器狀態
const statusClass = computed(() => {
  if (props.isPlaying) return 'status-playing'
  if (props.isPaused) return 'status-paused'
  if (props.isBuffering) return 'status-buffering'
  return 'status-idle'
})

const statusText = computed(() => {
  if (props.isPlaying) return '播放中'
  if (props.isPaused) return '已暫停'
  if (props.isBuffering) return '緩衝中'
  return '準備就緒'
})
</script>

<style scoped>
.video-player-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.player-wrapper {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  background-color: #000;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
}

.youtube-player {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.youtube-player iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
}

.youtube-player.is-loading {
  opacity: 0.3;
}

/* 載入覆蓋層 */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.8);
  z-index: 10;
}

/* 載入動畫 */
.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ff0000;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  margin-top: 1rem;
  color: white;
  font-size: 1rem;
  font-weight: 500;
}

/* 播放器資訊 */
.player-info {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background-color: #f5f5f5;
  border-radius: 8px;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  animation: pulse 2s ease-in-out infinite;
}

.status-playing .status-dot {
  background-color: #4caf50;
}

.status-paused .status-dot {
  background-color: #ff9800;
  animation: none;
}

.status-buffering .status-dot {
  background-color: #2196f3;
}

.status-idle .status-dot {
  background-color: #9e9e9e;
  animation: none;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.status-text {
  font-size: 0.875rem;
  font-weight: 500;
  color: #424242;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .player-wrapper {
    border-radius: 8px;
  }

  .loading-spinner {
    width: 40px;
    height: 40px;
  }

  .loading-text {
    font-size: 0.875rem;
  }
}
</style>
