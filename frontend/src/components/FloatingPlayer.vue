<template>
  <Teleport to="body">
    <div v-if="playerStore.isVisible && playerStore.currentVideo" class="floating-player-container">
      <!-- Minimized View -->
      <div v-if="playerStore.isMinimized" class="floating-player minimized">
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
            <button @click="playerStore.maximize" class="btn-control" title="展開">
              ⬆
            </button>
            <button @click="playerStore.close" class="btn-control btn-close" title="關閉">
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Expanded View -->
      <div v-else class="floating-player expanded">
        <div class="player-header">
          <h3>{{ playerStore.currentVideo.title }}</h3>
          <div class="header-actions">
            <button @click="playerStore.minimize" class="btn-icon" title="最小化">⬇</button>
            <button @click="playerStore.close" class="btn-icon" title="關閉">✕</button>
          </div>
        </div>
        <div class="player-body">
          <div id="floating-youtube-player" class="youtube-container"></div>
        </div>
        <div class="player-controls" v-if="playerStore.hasPlaylist">
          <button @click="playerStore.previous" class="btn-control">⏮ 上一首</button>
          <button @click="playerStore.togglePlay" class="btn-control btn-play">
            {{ playerStore.isPlaying ? '⏸ 暫停' : '▶ 播放' }}
          </button>
          <button @click="playerStore.next" class="btn-control">下一首 ⏭</button>
          <div class="track-info">
            {{ playerStore.currentIndex + 1 }} / {{ playerStore.currentPlaylist.items.length }}
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useGlobalPlayerStore } from '@/stores/globalPlayerStore'

const playerStore = useGlobalPlayerStore()

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
        if (playerStore.isPlaying) {
          event.target.playVideo()
        }
      },
      onStateChange: (event) => {
        if (event.data === window.YT.PlayerState.ENDED) {
          if (playerStore.hasPlaylist) {
            playerStore.next()
          }
        } else if (event.data === window.YT.PlayerState.PLAYING) {
          playerStore.play()
        } else if (event.data === window.YT.PlayerState.PAUSED) {
          playerStore.pause()
        }
      }
    }
  })
}

// 監聽當前影片變化
watch(() => playerStore.currentVideo, (newVideo) => {
  if (newVideo && !playerStore.isMinimized) {
    const videoId = newVideo.video_id || extractVideoId(newVideo.youtube_url)
    if (videoId) {
      initPlayer(videoId)
    }
  }
})

// 監聽播放狀態變化
watch(() => playerStore.isPlaying, (isPlaying) => {
  if (ytPlayer && ytPlayer.playVideo && ytPlayer.pauseVideo) {
    if (isPlaying) {
      ytPlayer.playVideo()
    } else {
      ytPlayer.pauseVideo()
    }
  }
})

// 監聽最小化狀態
watch(() => playerStore.isMinimized, (minimized) => {
  if (!minimized && playerStore.currentVideo) {
    const videoId = playerStore.currentVideo.video_id || extractVideoId(playerStore.currentVideo.youtube_url)
    if (videoId) {
      nextTick(() => initPlayer(videoId))
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
  width: 480px;
  max-width: calc(100vw - 40px);
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.player-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #f5f5f5;
  border-bottom: 1px solid #e0e0e0;
}

.player-header h3 {
  margin: 0;
  font-size: 14px;
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
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  background: #000;
}

.youtube-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.player-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #fafafa;
  border-top: 1px solid #e0e0e0;
}

.player-controls .btn-control {
  padding: 6px 12px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
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

.track-info {
  margin-left: auto;
  font-size: 12px;
  color: #757575;
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
