<template>
  <Teleport to="body">
    <div v-if="playerStore.isVisible && playerStore.currentVideo" class="floating-player-container">
      <!-- Minimized View -->
      <div v-show="playerStore.isMinimized" class="floating-player minimized" role="region" aria-label="播放器控制">
        <div class="minimized-content">
          <div class="video-info" @click="playerStore.maximize" role="button" tabindex="0" @keypress.enter="playerStore.maximize">
            <div class="thumbnail">
              <img :src="playerStore.currentVideo.thumbnail_url" :alt="playerStore.currentVideo.title" />
            </div>
            <div class="info">
              <div class="title">{{ truncateTitle(playerStore.currentVideo.title, 30) }}</div>
              <div class="status">
                <span v-if="playerStore.hasPlaylist">
                  {{ playerStore.currentIndex + 1 }} / {{ playerStore.currentPlaylist.items.length }}
                </span>
              </div>
            </div>
          </div>
          <div class="controls">
            <button
              v-if="playerStore.hasPlaylist"
              @click="playerStore.previous"
              class="btn-control"
              v-tooltip="'上一首'"
              aria-label="上一首"
            >
              <BackwardIcon class="icon" />
            </button>
            <button
              @click="playerStore.togglePlay"
              class="btn-control btn-play"
              v-tooltip="playerStore.isPlaying ? '暫停' : '播放'"
              :aria-label="playerStore.isPlaying ? '暫停' : '播放'"
              :aria-pressed="playerStore.isPlaying"
            >
              <PauseIcon v-if="playerStore.isPlaying" class="icon" />
              <PlayIcon v-else class="icon" />
            </button>
            <button
              v-if="playerStore.hasPlaylist"
              @click="playerStore.next"
              class="btn-control"
              v-tooltip="'下一首'"
              aria-label="下一首"
            >
              <ForwardIcon class="icon" />
            </button>
            <button
              v-if="playerStore.hasPlaylist"
              @click.stop="playerStore.toggleLoopMode"
              class="btn-control btn-mode-mini"
              :class="{ active: playerStore.loopMode !== 'playlist' }"
              v-tooltip="playerStore.loopMode === 'playlist' ? '清單循環' : '單曲循環'"
              :aria-label="playerStore.loopMode === 'playlist' ? '清單循環' : '單曲循環'"
              :aria-pressed="playerStore.loopMode !== 'playlist'"
            >
              <ArrowPathIcon v-if="playerStore.loopMode === 'playlist'" class="icon" />
              <ArrowPathRoundedSquareIcon v-else class="icon" />
            </button>
            <button
              v-if="playerStore.hasPlaylist"
              @click.stop="playerStore.toggleShuffle"
              class="btn-control btn-mode-mini"
              :class="{ active: playerStore.shuffleEnabled }"
              v-tooltip="'隨機播放'"
              aria-label="隨機播放"
              :aria-pressed="playerStore.shuffleEnabled"
            >
              <ArrowsRightLeftIcon class="icon" />
            </button>
            <button
              @click="playerStore.maximize"
              class="btn-control"
              v-tooltip="'展開'"
              aria-label="展開播放器"
            >
              <ChevronUpIcon class="icon" />
            </button>
            <button
              @click="playerStore.close"
              class="btn-control btn-close"
              v-tooltip="'關閉'"
              aria-label="關閉播放器"
            >
              <XMarkIcon class="icon" />
            </button>
          </div>
        </div>
        <!-- Hidden YouTube Player for minimized mode -->
        <div class="hidden-player">
          <div id="floating-youtube-player-minimized" class="youtube-container-minimized"></div>
        </div>
      </div>

      <!-- Expanded View -->
      <div v-show="!playerStore.isMinimized" class="floating-player expanded" :class="{ 'fullscreen': isFullscreen }" role="region" aria-label="展開的播放器">
        <div class="player-header">
          <h3>{{ playerStore.currentVideo.title }}</h3>
          <div class="header-actions">
            <button
              @click="toggleFullscreen"
              class="btn-icon"
              v-tooltip="isFullscreen ? '退出滿版' : '滿版'"
              :aria-label="isFullscreen ? '退出滿版' : '滿版'"
            >
              <ArrowsPointingInIcon v-if="isFullscreen" class="icon-sm" />
              <ArrowsPointingOutIcon v-else class="icon-sm" />
            </button>
            <button
              @click="playerStore.minimize"
              class="btn-icon"
              v-tooltip="'最小化'"
              aria-label="最小化播放器"
            >
              <ChevronDownIcon class="icon-sm" />
            </button>
            <button
              @click="playerStore.close"
              class="btn-icon"
              v-tooltip="'關閉'"
              aria-label="關閉播放器"
            >
              <XMarkIcon class="icon-sm" />
            </button>
          </div>
        </div>
        <div class="player-body">
          <div id="floating-youtube-player" class="youtube-container"></div>
        </div>
        <div class="player-controls">
          <!-- 播放列表控制 -->
          <template v-if="playerStore.hasPlaylist">
            <div class="playback-controls">
              <button
                @click="playerStore.previous"
                class="btn-control"
                v-tooltip="'上一首'"
                aria-label="上一首"
              >
                <BackwardIcon class="icon" />
              </button>
              <button
                @click="playerStore.togglePlay"
                class="btn-control btn-play"
                v-tooltip="playerStore.isPlaying ? '暫停' : '播放'"
                :aria-label="playerStore.isPlaying ? '暫停' : '播放'"
                :aria-pressed="playerStore.isPlaying"
              >
                <PauseIcon v-if="playerStore.isPlaying" class="icon" />
                <PlayIcon v-else class="icon" />
              </button>
              <button
                @click="playerStore.next"
                class="btn-control"
                v-tooltip="'下一首'"
                aria-label="下一首"
              >
                <ForwardIcon class="icon" />
              </button>
            </div>
            <div class="mode-controls">
              <button
                @click.stop="playerStore.toggleLoopMode"
                class="btn-mode"
                :class="{ active: playerStore.loopMode !== 'playlist' }"
                v-tooltip="playerStore.loopMode === 'playlist' ? '清單循環' : '單曲循環'"
                :aria-label="playerStore.loopMode === 'playlist' ? '清單循環' : '單曲循環'"
                :aria-pressed="playerStore.loopMode !== 'playlist'"
              >
                <ArrowPathIcon v-if="playerStore.loopMode === 'playlist'" class="icon" />
                <ArrowPathRoundedSquareIcon v-else class="icon" />
              </button>
              <button
                @click.stop="playerStore.toggleShuffle"
                class="btn-mode"
                :class="{ active: playerStore.shuffleEnabled }"
                v-tooltip="'隨機播放'"
                aria-label="隨機播放"
                :aria-pressed="playerStore.shuffleEnabled"
              >
                <ArrowsRightLeftIcon class="icon" />
              </button>
            </div>
            <div class="track-info" aria-live="polite">
              {{ playerStore.currentIndex + 1 }} / {{ playerStore.currentPlaylist.items.length }}
            </div>
          </template>
          <!-- 單一影片控制 -->
          <template v-else>
            <button
              @click="playerStore.togglePlay"
              class="btn-control btn-play"
              v-tooltip="playerStore.isPlaying ? '暫停' : '播放'"
              :aria-label="playerStore.isPlaying ? '暫停' : '播放'"
              :aria-pressed="playerStore.isPlaying"
            >
              <PauseIcon v-if="playerStore.isPlaying" class="icon-lg" />
              <PlayIcon v-else class="icon-lg" />
            </button>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { watch, onMounted, onUnmounted, nextTick, ref } from 'vue'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'
import youtubeApiService from '@/services/youtubeApiService'
import {
  PlayIcon,
  PauseIcon,
  BackwardIcon,
  ForwardIcon,
  ArrowPathIcon,
  ArrowPathRoundedSquareIcon,
  ArrowsRightLeftIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  XMarkIcon,
  ArrowsPointingInIcon,
  ArrowsPointingOutIcon
} from '@heroicons/vue/24/solid'

const playerStore = useGlobalPlayerStore()
const isFullscreen = ref(false)

// Debug logging
watch(() => playerStore.isVisible, (val) => {
  console.log('FloatingPlayer: isVisible changed to', val)
})

watch(() => playerStore.currentVideo, (val) => {
  console.log('FloatingPlayer: currentVideo changed to', val)
})

console.log('FloatingPlayer: Component mounted')

// Task 1: 修復播放器狀態追蹤 - 改為響應式
const ytPlayer = ref(null)
const apiReady = ref(false)
const playerReady = ref(false)

// Task 2: 添加防抖機制相關變數
let videoChangeTimeout = null
let retryCount = 0
const MAX_RETRIES = 3

// 全螢幕切換
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

// Task 5: 使用統一的 YouTube API 載入服務
const loadYouTubeAPI = async () => {
  try {
    await youtubeApiService.loadApi()
    apiReady.value = true
  } catch (error) {
    console.error('Failed to load YouTube API:', error)
    throw error
  }
}

// 初始化播放器
const initPlayer = async (videoId) => {
  if (!apiReady.value) {
    try {
      await loadYouTubeAPI()
    } catch (error) {
      console.error('Failed to load YouTube API:', error)
      handlePlayerError(error)
      return
    }
  }

  await nextTick()

  // 根據最小化狀態選擇正確的容器
  const containerId = playerStore.isMinimized ? 'floating-youtube-player-minimized' : 'floating-youtube-player'
  const container = document.getElementById(containerId)
  if (!container) {
    console.log('FloatingPlayer: Container not found:', containerId)
    return
  }
  
  console.log('FloatingPlayer: Using container:', containerId)

  // 如果播放器存在，嘗試更新影片
  if (ytPlayer.value) {
    try {
      // 檢查播放器是否仍然附加到 DOM
      const iframe = container.querySelector('iframe')
      if (iframe) {
        console.log('FloatingPlayer: Updating existing player with video', videoId)
        ytPlayer.value.loadVideoById(videoId)
        if (playerStore.isPlaying) {
          ytPlayer.value.playVideo()
        }
        return
      } else {
        // 播放器不在 DOM 中，需要重新創建
        console.log('FloatingPlayer: Player not in DOM, recreating...')
        ytPlayer.value = null
        playerReady.value = false
      }
    } catch (error) {
      console.error('FloatingPlayer: Error updating player, will recreate:', error)
      ytPlayer.value = null
      playerReady.value = false
    }
  }

  console.log('FloatingPlayer: Creating new YouTube player with video', videoId, 'in container', containerId)
  playerReady.value = false
  ytPlayer.value = new window.YT.Player(containerId, {
    height: '100%',
    width: '100%',
    videoId: videoId,
    playerVars: {
      autoplay: playerStore.isPlaying ? 1 : 0,
      controls: 1,
      modestbranding: 1,
      rel: 0
    },
    events: {
      onReady: (event) => {
        console.log('FloatingPlayer: YouTube player ready, isPlaying:', playerStore.isPlaying)
        playerReady.value = true
        if (playerStore.playerStatus) {
          playerStore.updatePlayerStatus('READY')
        }
        if (playerStore.isPlaying) {
          event.target.playVideo()
        }
      },
      onStateChange: (event) => {
        console.log('FloatingPlayer: YouTube state changed:', event.data)
        if (event.data === window.YT.PlayerState.ENDED) {
          console.log('FloatingPlayer: Video ended, loopMode:', playerStore.loopMode, 'shuffleEnabled:', playerStore.shuffleEnabled)
          if (playerStore.hasPlaylist) {
            // next() function will handle loop mode and shuffle
            playerStore.next()
          } else {
            // Single video - replay it
            console.log('FloatingPlayer: Single video ended, replaying')
            ytPlayer.value.seekTo(0)
            ytPlayer.value.playVideo()
          }
        } else if (event.data === window.YT.PlayerState.PLAYING) {
          console.log('FloatingPlayer: Video playing, calling playerStore.play()')
          isUpdatingFromYouTube = true
          playerStore.play()
          setTimeout(() => { isUpdatingFromYouTube = false }, 50)
        } else if (event.data === window.YT.PlayerState.PAUSED) {
          console.log('FloatingPlayer: Video paused, calling playerStore.pause()')
          isUpdatingFromYouTube = true
          playerStore.pause()
          setTimeout(() => { isUpdatingFromYouTube = false }, 50)
        }
      },
      onError: (event) => {
        console.error('FloatingPlayer: YouTube player error:', event.data)
        handlePlayerError(new Error(`YouTube player error: ${event.data}`))
      }
    }
  })
}

// Task 2 & 3: 抽取影片切換邏輯到獨立函數，實現防抖和重試機制
const handleVideoChange = (videoId) => {
  if (!videoId) return

  if (ytPlayer.value && playerReady.value) {
    // 播放器已就緒
    console.log('FloatingPlayer: Loading video', videoId, 'with ready player')
    ytPlayer.value.loadVideoById(videoId)
    if (playerStore.isPlaying) {
      ytPlayer.value.playVideo()
    }
  } else if (ytPlayer.value && !playerReady.value && retryCount < MAX_RETRIES) {
    // 播放器存在但未就緒，重試
    console.log(`FloatingPlayer: 播放器未就緒，第 ${retryCount + 1} 次重試...`)
    retryCount++
    setTimeout(() => handleVideoChange(videoId), 300)  // 300ms 後重試
  } else {
    // 超過重試次數或播放器不存在，重新初始化
    console.log('FloatingPlayer: 重新初始化播放器')
    ytPlayer.value = null
    playerReady.value = false
    initPlayer(videoId)
  }
}

// 監聽當前影片的 video_id 變化（更精確的監聽）
watch(() => playerStore.currentVideo?.video_id, (newVideoId, oldVideoId) => {
  console.log('FloatingPlayer: currentVideo.video_id changed', {
    newVideoId,
    oldVideoId,
    currentVideo: playerStore.currentVideo?.title
  })

  // 只有當 video_id 真的改變時才更新
  if (newVideoId && newVideoId !== oldVideoId) {
    // Task 2: 清除之前的計時器
    if (videoChangeTimeout) {
      clearTimeout(videoChangeTimeout)
      videoChangeTimeout = null
    }

    // 重置重試計數
    retryCount = 0

    const videoId = newVideoId || extractVideoId(playerStore.currentVideo?.youtube_url)
    console.log('FloatingPlayer: Extracted video ID:', videoId)

    if (videoId) {
      // Task 2: 使用防抖處理影片切換
      videoChangeTimeout = setTimeout(() => {
        handleVideoChange(videoId)
      }, 100)  // 100ms 防抖延遲
    }
  }
})

// 防止循環更新的標記
let isUpdatingFromYouTube = false

// 監聽播放狀態變化
watch(() => playerStore.isPlaying, (isPlaying) => {
  console.log('FloatingPlayer: isPlaying changed to', isPlaying, 'ytPlayer exists:', !!ytPlayer.value, 'isUpdatingFromYouTube:', isUpdatingFromYouTube)

  // 如果是 YouTube 播放器觸發的狀態變化，不要再次控制播放器
  if (isUpdatingFromYouTube) {
    console.log('FloatingPlayer: Skipping control because update came from YouTube')
    return
  }

  if (ytPlayer.value) {
    try {
      if (isPlaying) {
        console.log('FloatingPlayer: Calling playVideo()')
        ytPlayer.value.playVideo()
      } else {
        console.log('FloatingPlayer: Calling pauseVideo()')
        ytPlayer.value.pauseVideo()
      }
    } catch (error) {
      console.error('FloatingPlayer: Error controlling player:', error)
    }
  } else {
    console.log('FloatingPlayer: Cannot control playback, ytPlayer not initialized')
  }
})

// 監聽最小化狀態
watch(() => playerStore.isMinimized, async (minimized) => {
  await nextTick()

  const playerContainer = document.getElementById('floating-youtube-player')
  const minimizedContainer = document.getElementById('floating-youtube-player-minimized')

  if (minimized) {
    // 縮小時：移動播放器到隱藏容器
    if (playerContainer && minimizedContainer) {
      const iframe = playerContainer.querySelector('iframe')
      if (iframe) {
        minimizedContainer.appendChild(iframe)
        console.log('FloatingPlayer: Moved iframe to minimized container')
      } else if (!ytPlayer.value && playerStore.currentVideo) {
        // 如果沒有 iframe 且播放器不存在，需要初始化
        console.log('FloatingPlayer: No player found, initializing in minimized mode')
        const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
        if (videoId) {
          initPlayer(videoId)
        }
      }
    }
  } else {
    // 展開時：移動播放器回到可見容器
    if (playerContainer && minimizedContainer) {
      const iframe = minimizedContainer.querySelector('iframe')
      if (iframe) {
        playerContainer.appendChild(iframe)
        console.log('FloatingPlayer: Moved iframe to expanded container')
      } else if (!ytPlayer.value && playerStore.currentVideo) {
        // 如果沒有 iframe 且播放器不存在，需要初始化
        console.log('FloatingPlayer: No player found, initializing in expanded mode')
        const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
        if (videoId) {
          initPlayer(videoId)
        }
      }
    }
  }
})

// 監聽播放器可見狀態
watch(() => playerStore.isVisible, (isVisible) => {
  if (!isVisible && ytPlayer.value) {
    // 當播放器關閉時，銷毀 YouTube 實例
    console.log('FloatingPlayer: Destroying YouTube player instance')
    if (ytPlayer.value.destroy) {
      ytPlayer.value.destroy()
    }
    ytPlayer.value = null
    playerReady.value = false
  } else if (isVisible && !ytPlayer.value && playerStore.currentVideo) {
    // 當播放器重新打開時，重新初始化（無論是否最小化）
    console.log('FloatingPlayer: Reinitializing YouTube player, isMinimized:', playerStore.isMinimized)
    const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
    if (videoId) {
      nextTick(() => initPlayer(videoId))
    }
  }
})

// Task 7: 添加錯誤恢復機制
const handlePlayerError = (error) => {
  console.error('播放器錯誤:', error)

  // 更新狀態（如果 store 支援）
  if (playerStore.updatePlayerStatus) {
    playerStore.updatePlayerStatus('ERROR', error.message)
  }

  // 自動重試邏輯
  if (playerStore.playerStatus && playerStore.playerStatus.retryCount < 3) {
    setTimeout(() => {
      console.log(`嘗試恢復播放器 (第 ${playerStore.playerStatus.retryCount + 1} 次)`)
      reinitializePlayer()
    }, 2000)
  } else {
    console.error('播放器載入失敗，已達最大重試次數')
  }
}

// 手動重新初始化
const reinitializePlayer = () => {
  ytPlayer.value = null
  playerReady.value = false
  apiReady.value = false
  if (playerStore.currentVideo) {
    const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
    if (videoId) {
      initPlayer(videoId)
    }
  }
}

// 提取 video ID
const extractVideoId = (url) => {
  if (!url) return null
  const match = url.match(/[?&]v=([^&]+)/)
  return match ? match[1] : null
}

// 截斷標題
const truncateTitle = (title, maxLength) => {
  if (!title) return ''
  return title.length > maxLength ? title.substring(0, maxLength) + '...' : title
}

onMounted(() => {
  loadYouTubeAPI()
})

onUnmounted(() => {
  // 清理計時器
  if (videoChangeTimeout) {
    clearTimeout(videoChangeTimeout)
  }
  
  if (ytPlayer.value && ytPlayer.value.destroy) {
    ytPlayer.value.destroy()
  }
})
</script>

<style scoped>
.floating-player-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
}

/* Hidden player for minimized mode */
.hidden-player {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
  overflow: hidden;
}

.youtube-container-minimized {
  width: 100%;
  height: 100%;
}

.youtube-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.floating-player.minimized {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  width: 350px;
}

.minimized-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  gap: 12px;
}

.video-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.thumbnail {
  width: 60px;
  height: 45px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  flex-shrink: 0;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info {
  flex: 1;
  min-width: 0;
}

.title {
  font-size: 13px;
  font-weight: 500;
  color: #212121;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status {
  font-size: 11px;
  color: #757575;
  margin-top: 2px;
}

.controls {
  display: flex;
  gap: var(--space-1);
  flex-shrink: 0;
}

.btn-control {
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  min-width: var(--touch-target-min);
  min-height: var(--touch-target-min);
  padding: var(--space-2);
  border-radius: var(--radius-full);
  transition: all var(--transition-fast);
  color: var(--text-secondary);
}

.btn-control:hover {
  background: var(--color-neutral-100);
  color: var(--text-primary);
}

.btn-control:active {
  transform: scale(0.95);
}

.btn-control .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.btn-play {
  background: var(--color-info);
  color: white;
}

.btn-play:hover {
  background: var(--color-info-dark);
  color: white;
}

.btn-close {
  color: var(--color-error);
}

.btn-close:hover {
  background: var(--color-error-alpha);
  color: var(--color-error-dark);
}

.floating-player.expanded {
  width: 320px;
  max-width: calc(100vw - 40px);
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

/* 滿版樣式 */
.floating-player.expanded.fullscreen {
  position: fixed;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  max-width: 100vw !important;
  height: 100vh !important;
  border-radius: 0;
  z-index: 10000;
}

.floating-player.expanded.fullscreen .player-body {
  padding-bottom: 0;
  height: calc(100vh - 50px - 60px); /* 減去 header 和 controls 的高度 */
}

.floating-player.expanded.fullscreen .player-body .youtube-container {
  position: static;
  height: 100%;
}

.player-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.player-header h3 {
  margin: 0;
  font-size: 13px;
  font-weight: 500;
  color: #212121;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-actions {
  display: flex;
  gap: 4px;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: var(--space-2);
  border-radius: var(--radius-sm);
  transition: all var(--transition-fast);
  color: var(--text-secondary);
}

.btn-icon:hover {
  background: var(--color-neutral-200);
  color: var(--text-primary);
}

.btn-icon:active {
  transform: scale(0.95);
}

.btn-icon .icon-sm {
  width: var(--icon-sm);
  height: var(--icon-sm);
}

.player-body {
  position: relative;
  height: 150px; /* 更矮的固定高度 */
  background: #000;
}

.player-controls {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 8px 12px;
  background: #fafafa;
  border-top: 1px solid #e0e0e0;
}

.playback-controls {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
}

.mode-controls {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
}

.player-controls .btn-control {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  padding: var(--space-3);
  background: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  flex: 1;
  min-height: var(--touch-target-comfortable);
  color: var(--text-primary);
}

.player-controls .btn-control:hover {
  background: var(--color-neutral-100);
  border-color: var(--border-color-hover);
}

.player-controls .btn-control:active {
  transform: scale(0.98);
}

.player-controls .btn-control .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.player-controls .btn-control .icon-lg {
  width: var(--icon-xl);
  height: var(--icon-xl);
}

.player-controls .btn-play {
  background: var(--color-info);
  color: white;
  border-color: var(--color-info);
}

.player-controls .btn-play:hover {
  background: var(--color-info-dark);
  border-color: var(--color-info-dark);
}

.btn-mode {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  padding: var(--space-3);
  background: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  flex: 1;
  color: var(--text-secondary);
}

.btn-mode:hover {
  background: var(--color-neutral-100);
  border-color: var(--border-color-hover);
  color: var(--text-primary);
}

.btn-mode:active {
  transform: scale(0.98);
}

.btn-mode .icon {
  width: var(--icon-md);
  height: var(--icon-md);
}

.btn-mode.active {
  background: var(--color-success);
  color: white;
  border-color: var(--color-success);
  box-shadow: 0 0 0 3px var(--color-success-alpha);
}

.btn-mode.active:hover {
  background: var(--color-success-dark);
  border-color: var(--color-success-dark);
}

.btn-mode-mini {
  background: white;
  border: 1px solid var(--border-color);
}

.btn-mode-mini.active {
  background: var(--color-success);
  color: white;
  border-color: var(--color-success);
}

.track-info {
  margin-left: auto;
  font-size: 12px;
  color: #757575;
  text-align: center;
}

/* 響應式 */
@media (max-width: 768px) {
  .floating-player-container {
    bottom: 10px;
    right: 10px;
  }

  .floating-player.minimized {
    width: 300px;
  }

  .floating-player.expanded {
    width: calc(100vw - 20px);
  }

  .thumbnail {
    width: 50px;
    height: 38px;
  }

  .title {
    font-size: 12px;
  }

  .btn-control {
    font-size: 16px;
    padding: 4px 6px;
  }
}
</style>
