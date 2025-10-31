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

let ytPlayer = null
let apiReady = false

// 全螢幕切換
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

// 載入 YouTube IFrame API
const loadYouTubeAPI = () => {
  return new Promise((resolve, reject) => {
    if (window.YT && window.YT.Player) {
      apiReady = true
      resolve()
      return
    }

    if (document.querySelector('script[src*="youtube.com/iframe_api"]')) {
      // Script already loading
      const checkInterval = setInterval(() => {
        if (window.YT && window.YT.Player) {
          clearInterval(checkInterval)
          apiReady = true
          resolve()
        }
      }, 100)
      return
    }

    const tag = document.createElement('script')
    tag.src = 'https://www.youtube.com/iframe_api'
    tag.onerror = () => reject(new Error('Failed to load YouTube API'))

    window.onYouTubeIframeAPIReady = () => {
      apiReady = true
      resolve()
    }

    const firstScriptTag = document.getElementsByTagName('script')[0]
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag)
  })
}

// 初始化播放器
const initPlayer = async (videoId) => {
  if (!apiReady) {
    try {
      await loadYouTubeAPI()
    } catch (error) {
      console.error('Failed to load YouTube API:', error)
      return
    }
  }

  await nextTick()

  const container = document.getElementById('floating-youtube-player')
  if (!container) {
    console.log('FloatingPlayer: Container not found')
    return
  }

  // 如果播放器存在，嘗試更新影片
  if (ytPlayer) {
    try {
      // 檢查播放器是否仍然附加到 DOM
      const iframe = container.querySelector('iframe')
      if (iframe) {
        console.log('FloatingPlayer: Updating existing player with video', videoId)
        ytPlayer.loadVideoById(videoId)
        if (playerStore.isPlaying) {
          ytPlayer.playVideo()
        }
        return
      } else {
        // 播放器不在 DOM 中，需要重新創建
        console.log('FloatingPlayer: Player not in DOM, recreating...')
        ytPlayer = null
      }
    } catch (error) {
      console.error('FloatingPlayer: Error updating player, will recreate:', error)
      ytPlayer = null
    }
  }

  console.log('FloatingPlayer: Creating new YouTube player with video', videoId)
  ytPlayer = new window.YT.Player('floating-youtube-player', {
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
            ytPlayer.seekTo(0)
            ytPlayer.playVideo()
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
      }
    }
  })
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
    const videoId = newVideoId || extractVideoId(playerStore.currentVideo?.youtube_url)
    console.log('FloatingPlayer: Extracted video ID:', videoId, 'ytPlayer exists:', !!ytPlayer, 'isMinimized:', playerStore.isMinimized)

    if (videoId) {
      // 無論是否最小化都要更新影片
      if (ytPlayer) {
        // 如果播放器已存在，直接載入新影片
        console.log('FloatingPlayer: Loading new video', videoId)
        try {
          ytPlayer.loadVideoById(videoId)
          if (playerStore.isPlaying) {
            console.log('FloatingPlayer: Auto-playing after load')
            ytPlayer.playVideo()
          }
        } catch (error) {
          console.error('FloatingPlayer: Error loading video:', error)
          // 如果載入失敗，可能是播放器實例有問題，嘗試重新初始化
          ytPlayer = null
          initPlayer(videoId)
        }
      } else {
        // 播放器不存在時，初始化播放器（無論是否最小化）
        console.log('FloatingPlayer: Initializing new player (minimized:', playerStore.isMinimized, ')')
        initPlayer(videoId)
      }
    }
  }
})

// 防止循環更新的標記
let isUpdatingFromYouTube = false

// 監聽播放狀態變化
watch(() => playerStore.isPlaying, (isPlaying) => {
  console.log('FloatingPlayer: isPlaying changed to', isPlaying, 'ytPlayer exists:', !!ytPlayer, 'isUpdatingFromYouTube:', isUpdatingFromYouTube)

  // 如果是 YouTube 播放器觸發的狀態變化，不要再次控制播放器
  if (isUpdatingFromYouTube) {
    console.log('FloatingPlayer: Skipping control because update came from YouTube')
    return
  }

  if (ytPlayer) {
    try {
      if (isPlaying) {
        console.log('FloatingPlayer: Calling playVideo()')
        ytPlayer.playVideo()
      } else {
        console.log('FloatingPlayer: Calling pauseVideo()')
        ytPlayer.pauseVideo()
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
    if (playerContainer && minimizedContainer && !minimizedContainer.contains(playerContainer.querySelector('iframe'))) {
      const iframe = playerContainer.querySelector('iframe')
      if (iframe) {
        minimizedContainer.appendChild(iframe)
      }
    }
  } else {
    // 展開時：移動播放器回到可見容器
    if (playerContainer && minimizedContainer) {
      const iframe = minimizedContainer.querySelector('iframe')
      if (iframe) {
        playerContainer.appendChild(iframe)
      }
    }

    // 只有當播放器不存在時才重新初始化
    if (!ytPlayer && playerStore.currentVideo) {
      const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
      if (videoId) {
        initPlayer(videoId)
      }
    }
  }
})

// 監聽播放器可見狀態
watch(() => playerStore.isVisible, (isVisible) => {
  if (!isVisible && ytPlayer) {
    // 當播放器關閉時，銷毀 YouTube 實例
    console.log('FloatingPlayer: Destroying YouTube player instance')
    if (ytPlayer.destroy) {
      ytPlayer.destroy()
    }
    ytPlayer = null
  } else if (isVisible && !ytPlayer && playerStore.currentVideo && !playerStore.isMinimized) {
    // 當播放器重新打開時，重新初始化
    console.log('FloatingPlayer: Reinitializing YouTube player')
    const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
    if (videoId) {
      nextTick(() => initPlayer(videoId))
    }
  }
})

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
  if (ytPlayer && ytPlayer.destroy) {
    ytPlayer.destroy()
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
  border-radius: 12px;
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
  border-radius: 4px;
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
  border-radius: 12px;
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
