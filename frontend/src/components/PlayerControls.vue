<template>
  <div class="player-controls">
    <div class="controls-container">
      <!-- 播放/暫停按鈕 -->
      <button
        type="button"
        class="control-button play-pause-button"
        :aria-label="isPlaying ? '暫停' : '播放'"
        @click="handlePlayPause"
      >
        <!-- 播放圖示 -->
        <svg
          v-if="!isPlaying"
          class="control-icon"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
        >
          <polygon points="5 3 19 12 5 21 5 3"></polygon>
        </svg>

        <!-- 暫停圖示 -->
        <svg
          v-else
          class="control-icon"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
        >
          <rect x="6" y="4" width="4" height="16"></rect>
          <rect x="14" y="4" width="4" height="16"></rect>
        </svg>
      </button>

      <!-- 音量控制區域 -->
      <div class="volume-control">
        <!-- 靜音切換按鈕 -->
        <button
          type="button"
          class="control-button mute-button"
          :aria-label="isMuted ? '取消靜音' : '靜音'"
          @click="handleMuteToggle"
        >
          <!-- 音量圖示 -->
          <svg
            v-if="!isMuted && volume > 50"
            class="control-icon"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
          </svg>

          <!-- 低音量圖示 -->
          <svg
            v-else-if="!isMuted && volume > 0"
            class="control-icon"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>

          <!-- 靜音圖示 -->
          <svg
            v-else
            class="control-icon"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <line x1="23" y1="9" x2="17" y2="15"></line>
            <line x1="17" y1="9" x2="23" y2="15"></line>
          </svg>
        </button>

        <!-- 音量滑桿 -->
        <div class="volume-slider-container">
          <input
            type="range"
            class="volume-slider"
            min="0"
            max="100"
            :value="volume"
            :aria-label="`音量: ${volume}%`"
            @input="handleVolumeChange"
          />
          <div class="volume-fill" :style="{ width: `${volume}%` }"></div>
        </div>

        <!-- 音量數值 -->
        <span class="volume-value">{{ volume }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  isPlaying: {
    type: Boolean,
    default: false
  },
  isPaused: {
    type: Boolean,
    default: false
  },
  volume: {
    type: Number,
    default: 100
  },
  isMuted: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['play', 'pause', 'volume-change', 'mute-toggle'])

function handlePlayPause() {
  if (props.isPlaying) {
    emit('pause')
  } else {
    emit('play')
  }
}

function handleVolumeChange(event) {
  const newVolume = parseInt(event.target.value, 10)
  emit('volume-change', newVolume)
}

function handleMuteToggle() {
  emit('mute-toggle')
}
</script>

<style scoped>
.player-controls {
  width: 100%;
  max-width: 1200px;
  margin: 1rem auto 0;
}

.controls-container {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem 1.5rem;
  background-color: #ffffff;
  border-radius: var(--radius-lg);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* 控制按鈕 */
.control-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  padding: 0;
  background-color: #ff0000;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
  color: white;
  flex-shrink: 0;
}

.control-button:hover {
  background-color: #cc0000;
  transform: scale(1.05);
}

.control-button:active {
  transform: scale(0.95);
}

.control-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.2);
}

.control-icon {
  width: 24px;
  height: 24px;
}

/* 靜音按鈕樣式稍微不同 */
.mute-button {
  width: 40px;
  height: 40px;
  background-color: #f5f5f5;
  color: #616161;
}

.mute-button:hover {
  background-color: #e0e0e0;
  color: #212121;
}

.mute-button .control-icon {
  width: 20px;
  height: 20px;
}

/* 音量控制區域 */
.volume-control {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
  min-width: 0;
}

/* 音量滑桿容器 */
.volume-slider-container {
  position: relative;
  flex: 1;
  height: 6px;
  background-color: #e0e0e0;
  border-radius: var(--radius-sm);
  overflow: hidden;
}

/* 音量滑桿 */
.volume-slider {
  position: relative;
  width: 100%;
  height: 100%;
  margin: 0;
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
  cursor: pointer;
  z-index: 2;
}

/* WebKit 瀏覽器滑桿樣式 */
.volume-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  background-color: #ff0000;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.volume-slider::-webkit-slider-thumb:hover {
  background-color: #cc0000;
  transform: scale(1.2);
}

/* Firefox 滑桿樣式 */
.volume-slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background-color: #ff0000;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.volume-slider::-moz-range-thumb:hover {
  background-color: #cc0000;
  transform: scale(1.2);
}

/* 音量填充 */
.volume-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background-color: #ff0000;
  border-radius: var(--radius-sm);
  transition: width 0.1s ease;
  pointer-events: none;
  z-index: 1;
}

/* 音量數值 */
.volume-value {
  min-width: 45px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #616161;
  text-align: right;
  flex-shrink: 0;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .controls-container {
    gap: 1rem;
    padding: 0.875rem 1rem;
  }

  .control-button {
    width: 44px;
    height: 44px;
  }

  .control-icon {
    width: 22px;
    height: 22px;
  }

  .mute-button {
    width: 36px;
    height: 36px;
  }

  .mute-button .control-icon {
    width: 18px;
    height: 18px;
  }
}

@media (max-width: 480px) {
  .controls-container {
    gap: 0.75rem;
    padding: 0.75rem 0.875rem;
  }

  .control-button {
    width: 40px;
    height: 40px;
  }

  .control-icon {
    width: 20px;
    height: 20px;
  }

  .mute-button {
    width: 32px;
    height: 32px;
  }

  .volume-value {
    min-width: 40px;
    font-size: 0.8125rem;
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .control-button,
  .volume-slider::-webkit-slider-thumb,
  .volume-slider::-moz-range-thumb,
  .volume-fill {
    transition: none;
  }

  .control-button:hover,
  .control-button:active {
    transform: none;
  }
}
</style>
