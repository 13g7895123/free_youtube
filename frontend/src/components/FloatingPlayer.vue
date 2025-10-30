<template>
  <Teleport to="body">
    <div v-if="playerStore.isVisible && playerStore.currentVideo" class="floating-player-container">
      <!-- Minimized View -->
      <div v-show="playerStore.isMinimized" class="floating-player minimized">
        <div class="minimized-content">
          <div class="video-info">
            <div class="thumbnail">
              <img :src="playerStore.currentVideo.thumbnail_url" alt="thumbnail" />
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
            <button @click="playerStore.previous" v-if="playerStore.hasPlaylist" class="btn-control" title="上一首">
              ⏮
            </button>
            <button @click="playerStore.togglePlay" class="btn-control btn-play" :title="playerStore.isPlaying ? '暫停' : '播放'">
              {{ playerStore.isPlaying ? '⏸' : '▶' }}
            </button>
            <button @click="playerStore.next" v-if="playerStore.hasPlaylist" class="btn-control" title="下一首">
              ⏭
            </button>
            <button
              @click.stop="playerStore.toggleLoopMode"
              v-if="playerStore.hasPlaylist"
              class="btn-control btn-mode-mini"
              :class="{ active: playerStore.loopMode !== 'playlist' }"
              :title="playerStore.loopMode === 'playlist' ? '清單循環' : '單曲循環'"
            >
              {{ playerStore.loopMode === 'playlist' ? '🔁' : '🔂' }}
            </button>
            <button
              @click.stop="playerStore.toggleShuffle"
              v-if="playerStore.hasPlaylist"
              class="btn-control btn-mode-mini"
              :class="{ active: playerStore.shuffleEnabled }"
              title="隨機播放"
            >
              🔀
            </button>
            <button @click="playerStore.maximize" class="btn-control" title="展開">
              ⬆
            </button>
            <button @click="playerStore.close" class="btn-control btn-close" title="關閉">
              ✕
            </button>
          </div>
        </div>
        <!-- Hidden YouTube Player for minimized mode -->
        <div class="hidden-player">
          <div id="floating-youtube-player-minimized" class="youtube-container-minimized"></div>
        </div>
      </div>

      <!-- Expanded View -->
      <div v-show="!playerStore.isMinimized" class="floating-player expanded" :class="{ 'fullscreen': isFullscreen }">
        <div class="player-header">
          <h3>{{ playerStore.currentVideo.title }}</h3>
          <div class="header-actions">
            <button @click="toggleFullscreen" class="btn-icon" :title="isFullscreen ? '退出滿版' : '滿版'">
              {{ isFullscreen ? '⊡' : '⛶' }}
            </button>
            <button @click="playerStore.minimize" class="btn-icon" title="最小化">⬇</button>
            <button @click="playerStore.close" class="btn-icon" title="關閉">✕</button>
          </div>
        </div>
        <div class="player-body">
          <div id="floating-youtube-player" class="youtube-container"></div>
        </div>
        <div class="player-controls">
          <!-- 播放列表控制 -->
          <template v-if="playerStore.hasPlaylist">
            <div class="playback-controls">
              <button @click="playerStore.previous" class="btn-control">⏮ 上一首</button>
              <button @click="playerStore.togglePlay" class="btn-control btn-play">
                {{ playerStore.isPlaying ? '⏸ 暫停' : '▶ 播放' }}
              </button>
              <button @click="playerStore.next" class="btn-control">下一首 ⏭</button>
            </div>
            <div class="mode-controls">
              <button
                @click.stop="playerStore.toggleLoopMode"
                class="btn-mode"
                :class="{ active: playerStore.loopMode !== 'playlist' }"
                :title="playerStore.loopMode === 'playlist' ? '清單循環' : '單曲循環'"
              >
                {{ playerStore.loopMode === 'playlist' ? '🔁 清單循環' : '🔂 單曲循環' }}
              </button>
              <button
                @click.stop="playerStore.toggleShuffle"
                class="btn-mode"
                :class="{ active: playerStore.shuffleEnabled }"
                title="隨機播放"
              >
                🔀 {{ playerStore.shuffleEnabled ? '隨機' : '順序' }}
              </button>
            </div>
            <div class="track-info">
              {{ playerStore.currentIndex + 1 }} / {{ playerStore.currentPlaylist.items.length }}
            </div>
          </template>
          <!-- 單一影片控制 -->
          <template v-else>
            <button @click="playerStore.togglePlay" class="btn-control btn-play">
              {{ playerStore.isPlaying ? '⏸ 暫停' : '▶ 播放' }}
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

// 監聽當前影片變化
watch(() => playerStore.currentVideo, (newVideo, oldVideo) => {
  console.log('FloatingPlayer: currentVideo changed', {
    newVideo: newVideo?.title,
    oldVideo: oldVideo?.title,
    newVideoId: newVideo?.video_id,
    oldVideoId: oldVideo?.video_id
  })

  if (newVideo) {
    const videoId = newVideo.video_id || extractVideoId(newVideo.youtube_url)
    console.log('FloatingPlayer: Extracted video ID:', videoId, 'ytPlayer exists:', !!ytPlayer)

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
        }
      } else if (!playerStore.isMinimized) {
        // 只有在展開狀態且播放器不存在時才初始化
        console.log('FloatingPlayer: Initializing new player')
        initPlayer(videoId)
      }
    }
  }
}, { deep: true })

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
  gap: 4px;
  flex-shrink: 0;
}

.btn-control {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.btn-control:hover {
  background: #f5f5f5;
}

.btn-play {
  color: #1976d2;
}

.btn-close {
  color: #f44336;
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
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.btn-icon:hover {
  background: #e0e0e0;
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
  padding: 4px 8px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
}

.player-controls .btn-control:hover {
  background: #f5f5f5;
  border-color: #bbb;
}

.player-controls .btn-play {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
}

.player-controls .btn-play:hover {
  background: #1565c0;
}

.btn-mode {
  padding: 4px 8px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
}

.btn-mode:hover {
  background: #f5f5f5;
  border-color: #bbb;
}

.btn-mode.active {
  background: #4caf50;
  color: white;
  border-color: #4caf50;
}

.btn-mode-mini {
  background: white;
  border: 1px solid #ddd;
}

.btn-mode-mini.active {
  background: #4caf50;
  color: white;
  border-color: #4caf50;
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
