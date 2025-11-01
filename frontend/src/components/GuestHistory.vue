<template>
  <div v-if="!isEmpty" class="guest-history">
    <div class="history-header">
      <h3 class="history-title">最近播放</h3>
      <button
        class="clear-button"
        @click="handleClearHistory"
        title="清空歷史記錄"
      >
        清空
      </button>
    </div>

    <div class="history-list">
      <div
        v-for="item in history"
        :key="item.videoId"
        class="history-item"
        @click="handleItemClick(item.videoId)"
      >
        <!-- 縮圖 -->
        <div class="thumbnail-wrapper">
          <img
            :src="item.thumbnail"
            :alt="item.title"
            class="thumbnail"
            loading="lazy"
            @error="handleImageError"
          />
          <div class="play-overlay">
            <svg
              class="play-icon"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
        </div>

        <!-- 資訊 -->
        <div class="item-info">
          <h4 class="video-title">{{ item.title }}</h4>
          <p class="played-time">{{ formatTime(item.playedAt) }}</p>
        </div>

        <!-- 移除按鈕 -->
        <button
          class="remove-button"
          @click.stop="handleRemoveItem(item.videoId)"
          title="從歷史記錄中移除"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useGuestHistory } from '../composables/useGuestHistory'

const emit = defineEmits(['play-video'])

// 使用訪客歷史記錄服務
const guestHistory = useGuestHistory()

// 取得歷史記錄
const history = computed(() => guestHistory.history.value)
const isEmpty = computed(() => guestHistory.isEmpty.value)

/**
 * 格式化播放時間
 */
function formatTime(timestamp) {
  if (!timestamp) return ''

  const now = Date.now()
  const diff = now - timestamp

  const minute = 60 * 1000
  const hour = 60 * minute
  const day = 24 * hour
  const week = 7 * day

  if (diff < minute) {
    return '剛剛'
  } else if (diff < hour) {
    const minutes = Math.floor(diff / minute)
    return `${minutes} 分鐘前`
  } else if (diff < day) {
    const hours = Math.floor(diff / hour)
    return `${hours} 小時前`
  } else if (diff < week) {
    const days = Math.floor(diff / day)
    return `${days} 天前`
  } else {
    // 超過一週顯示實際日期
    const date = new Date(timestamp)
    return date.toLocaleDateString('zh-TW', {
      month: 'short',
      day: 'numeric'
    })
  }
}

/**
 * 處理項目點擊（播放影片）
 */
function handleItemClick(videoId) {
  emit('play-video', videoId)
}

/**
 * 處理移除項目
 */
function handleRemoveItem(videoId) {
  guestHistory.removeFromHistory(videoId)
}

/**
 * 處理清空歷史記錄
 */
function handleClearHistory() {
  if (confirm('確定要清空所有歷史記錄嗎？')) {
    guestHistory.clearHistory()
  }
}

/**
 * 處理圖片載入錯誤（使用預設縮圖）
 */
function handleImageError(event) {
  event.target.src = 'https://img.youtube.com/vi/default.jpg'
}
</script>

<style scoped>
.guest-history {
  background-color: #ffffff;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e0e0e0;
}

.history-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #212121;
}

.clear-button {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #ff0000;
  background-color: transparent;
  border: 1px solid #ff0000;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
}

.clear-button:hover {
  background-color: #ff0000;
  color: #ffffff;
}

.clear-button:active {
  transform: scale(0.98);
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background-color: #f5f5f5;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
}

.history-item:hover {
  background-color: #eeeeee;
  transform: translateX(4px);
}

.history-item:active {
  transform: scale(0.98);
}

.thumbnail-wrapper {
  position: relative;
  flex-shrink: 0;
  width: 120px;
  height: 68px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  background-color: #000;
}

.thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.3);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.history-item:hover .play-overlay {
  opacity: 1;
}

.play-icon {
  width: 32px;
  height: 32px;
  color: #ffffff;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.item-info {
  flex: 1;
  min-width: 0;
}

.video-title {
  margin: 0 0 0.25rem 0;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #212121;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-height: 1.4;
}

.played-time {
  margin: 0;
  font-size: 0.8125rem;
  color: #757575;
}

.remove-button {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  padding: 0;
  background-color: transparent;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  color: #9e9e9e;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remove-button:hover {
  background-color: #ffebee;
  color: #ff0000;
}

.remove-button:active {
  transform: scale(0.9);
}

.remove-button svg {
  width: 20px;
  height: 20px;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .guest-history {
    padding: 1rem;
  }

  .history-title {
    font-size: 1.125rem;
  }

  .thumbnail-wrapper {
    width: 100px;
    height: 56px;
  }

  .video-title {
    font-size: 0.875rem;
  }

  .played-time {
    font-size: 0.75rem;
  }
}

@media (max-width: 480px) {
  .thumbnail-wrapper {
    width: 80px;
    height: 45px;
  }

  .item-info {
    gap: 0.5rem;
  }
}
</style>
