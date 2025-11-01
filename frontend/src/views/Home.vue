<template>
  <div id="app" class="container">
    <header class="app-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="app-title">YouTube Loop Player</h1>
          <p class="app-subtitle">貼上 YouTube 網址，自動循環播放</p>
        </div>
      </div>
    </header>

    <main class="app-main">
      <!-- 認證提示訊息 -->
      <div v-if="showAuthRequiredMessage" class="auth-required-message">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="info-icon"
        >
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        <p>此功能需要登入，請先使用 LINE 登入</p>
      </div>

      <!-- 會話過期提示訊息 -->
      <div v-if="showSessionExpiredMessage" class="session-expired-message">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="warning-icon"
        >
          <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
        </svg>
        <p>您的登入已過期，請重新登入以繼續使用會員功能</p>
      </div>

      <!-- URL 輸入 -->
      <UrlInput
        :is-loading="isLoading"
        :validation-error="parser.errorMessage.value"
        @submit="handleUrlSubmit"
      />

      <!-- 錯誤訊息 -->
      <ErrorMessage
        :message="player.errorMessage.value"
        @close="clearError"
      />

      <!-- 影片播放器 -->
      <VideoPlayer
        v-if="hasVideo"
        :is-loading="isLoading"
        :is-ready="player.isReady.value"
        :is-playing="player.isPlaying.value"
        :is-paused="player.isPaused.value"
        :is-buffering="player.isBuffering.value"
      />

      <!-- 播放控制 -->
      <PlayerControls
        v-if="hasVideo && player.isReady.value"
        :is-playing="player.isPlaying.value"
        :is-paused="player.isPaused.value"
        :volume="player.volume.value"
        :is-muted="player.isMuted.value"
        @play="player.play"
        @pause="player.pause"
        @volume-change="handleVolumeChange"
        @mute-toggle="player.toggleMute"
      />

      <!-- 循環播放控制 -->
      <LoopToggle
        v-if="hasVideo"
        :is-enabled="player.loopEnabled.value"
        @toggle="handleLoopToggle"
      />

      <!-- 儲存影片操作 -->
      <SaveVideoActions
        v-if="hasVideo && player.isReady.value"
        :get-video-info="getVideoInfo"
      />

      <!-- 初始狀態提示 -->
      <div v-if="!hasVideo && !isLoading" class="welcome-message">
        <div class="welcome-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
          </svg>
        </div>
        <h2 class="welcome-title">歡迎使用 YouTube Loop Player</h2>
        <p class="welcome-text">
          在上方輸入框貼上 YouTube 影片或播放清單網址，即可開始自動循環播放
        </p>
      </div>

      <!-- 訪客播放歷史 -->
      <GuestHistory @play-video="handlePlayFromHistory" />
    </main>

    <footer class="app-footer">
      <p class="footer-text">
        支援 YouTube 影片和播放清單 · 自動循環播放 · 開源專案
      </p>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UrlInput from '../components/UrlInput.vue'
import VideoPlayer from '../components/VideoPlayer.vue'
import ErrorMessage from '../components/ErrorMessage.vue'
import PlayerControls from '../components/PlayerControls.vue'
import LoopToggle from '../components/LoopToggle.vue'
import SaveVideoActions from '../components/SaveVideoActions.vue'
import GuestHistory from '../components/GuestHistory.vue'
import { useUrlParser } from '../composables/useUrlParser'
import { useYouTubePlayer } from '../composables/useYouTubePlayer'
import { useLocalStorage } from '../composables/useLocalStorage'
import { useGlobalPlayerStore } from '../stores/globalPlayerStore'
import { useGuestHistory } from '../composables/useGuestHistory'
import { useAuthStore } from '../stores/auth'

// 路由
const route = useRoute()
const router = useRouter()

// 狀態管理
const isLoading = ref(false)
const hasVideo = ref(false)
const apiReady = ref(false)
const showAuthRequiredMessage = ref(false)
const showSessionExpiredMessage = ref(false)

// 從 LocalStorage 載入用戶偏好設定
const settingsStorage = useLocalStorage('youtube-loop-player-settings', {
  loopEnabled: true,
  volume: 100,
  isMuted: false
})

// Composables
const parser = useUrlParser()
const player = useYouTubePlayer('youtube-player', {
  loopEnabled: settingsStorage.value?.loopEnabled ?? true,
  volume: settingsStorage.value?.volume ?? 100,
  isMuted: settingsStorage.value?.isMuted ?? false
})
const globalPlayerStore = useGlobalPlayerStore()
const guestHistory = useGuestHistory()
const authStore = useAuthStore()

// 監聽設定變化，自動保存到 LocalStorage
watch(() => player.loopEnabled.value, (newValue) => {
  settingsStorage.value = {
    ...settingsStorage.value,
    loopEnabled: newValue
  }
})

watch(() => player.volume.value, (newValue) => {
  settingsStorage.value = {
    ...settingsStorage.value,
    volume: newValue
  }
})

watch(() => player.isMuted.value, (newValue) => {
  settingsStorage.value = {
    ...settingsStorage.value,
    isMuted: newValue
  }
})

// 監聽播放狀態，自動記錄到訪客歷史
watch(() => player.isPlaying.value, (isNowPlaying) => {
  if (isNowPlaying && player.isReady.value) {
    // 當影片開始播放時，取得影片資訊並加入歷史記錄
    const videoInfo = player.getCurrentVideoInfo()
    if (videoInfo && videoInfo.videoId) {
      guestHistory.addToHistory({
        videoId: videoInfo.videoId,
        title: videoInfo.title,
        thumbnail: videoInfo.thumbnail
      })
    }
  }
})

/**
 * 載入 YouTube IFrame API
 */
function loadYouTubeAPI() {
  return new Promise((resolve, reject) => {
    // 檢查是否已經載入
    if (window.YT && window.YT.Player) {
      apiReady.value = true
      resolve()
      return
    }

    // 創建 script 標籤
    const tag = document.createElement('script')
    tag.src = 'https://www.youtube.com/iframe_api'
    tag.onerror = () => reject(new Error('Failed to load YouTube IFrame API'))

    // 設置全域回調
    window.onYouTubeIframeAPIReady = () => {
      apiReady.value = true
      // 注意：不在這裡初始化播放器，因為 DOM 元素可能還不存在
      // 初始化會在 handleUrlSubmit 中進行
      resolve()
    }

    // 添加到文檔
    const firstScriptTag = document.getElementsByTagName('script')[0]
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag)
  })
}

/**
 * 處理 URL 提交
 * @param {string} url - 使用者輸入的 URL
 */
async function handleUrlSubmit(url) {
  // 解析 URL
  parser.parseUrl(url)

  // 驗證 URL
  if (!parser.isValid.value) {
    return
  }

  isLoading.value = true

  // 步驟 1: 確保 API 已載入
  if (!apiReady.value) {
    try {
      await loadYouTubeAPI()
    } catch (error) {
      console.error('Failed to load YouTube API:', error)
      player.errorMessage.value = '無法載入 YouTube 播放器，請檢查網路連線'
      isLoading.value = false
      return
    }
  }

  // 步驟 2: 確保 DOM 元素存在
  hasVideo.value = true
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 200))

  // 步驟 3: 初始化播放器（如果尚未初始化）
  if (!player.isReady.value) {
    const initSuccess = player.initPlayer()
    if (!initSuccess) {
      player.errorMessage.value = '無法初始化播放器，請重新整理頁面'
      isLoading.value = false
      hasVideo.value = false
      return
    }

    // 步驟 4: 等待播放器就緒
    const maxWaitTime = 10000
    const startTime = Date.now()

    await new Promise((resolve, reject) => {
      const checkInterval = setInterval(() => {
        if (player.isReady.value) {
          clearInterval(checkInterval)
          resolve()
        } else if (Date.now() - startTime > maxWaitTime) {
          clearInterval(checkInterval)
          reject(new Error('播放器初始化超時'))
        }
      }, 100)
    }).catch(error => {
      console.error('Player initialization timeout:', error)
      player.errorMessage.value = '播放器初始化超時，請重新整理頁面'
      isLoading.value = false
      hasVideo.value = false
      throw error
    })
  }

  // 步驟 5: 載入內容
  await loadContent()
}

/**
 * 載入影片或播放清單
 */
async function loadContent() {
  // 優先載入播放清單
  if (parser.playlistId.value) {
    player.loadPlaylist(parser.playlistId.value)
  } else if (parser.videoId.value) {
    player.loadVideo(parser.videoId.value)
  }

  isLoading.value = false
}

/**
 * 清除錯誤訊息
 */
function clearError() {
  player.errorMessage.value = ''
  player.hasError.value = false
}

/**
 * 處理循環播放切換
 * @param {boolean} enabled - 是否啟用循環
 */
function handleLoopToggle(enabled) {
  player.setLoop(enabled)
}

/**
 * 處理音量變化
 * @param {number} volume - 新的音量值（0-100）
 */
function handleVolumeChange(volume) {
  player.setVolume(volume)
}

/**
 * 取得當前影片資訊（供 SaveVideoActions 使用）
 * @returns {Object|null} 影片資訊
 */
function getVideoInfo() {
  return player.getCurrentVideoInfo()
}

/**
 * 從歷史記錄播放影片
 * @param {string} videoId - YouTube 影片 ID
 */
function handlePlayFromHistory(videoId) {
  const youtubeUrl = `https://www.youtube.com/watch?v=${videoId}`
  handleUrlSubmit(youtubeUrl)
}

// 組件掛載時預先載入 YouTube API（但不初始化播放器）
onMounted(async () => {
  try {
    await loadYouTubeAPI()
    console.log('YouTube API preloaded successfully')
  } catch (error) {
    console.error('Failed to preload YouTube API:', error)
  }

  // 檢查是否因需要認證被重定向
  if (route.query.requireAuth === '1') {
    showAuthRequiredMessage.value = true
    setTimeout(() => {
      showAuthRequiredMessage.value = false
      router.replace({ path: '/', query: {} })
    }, 5000)
  }

  // 檢查會話是否過期
  if (route.query.session === 'expired') {
    showSessionExpiredMessage.value = true
    setTimeout(() => {
      showSessionExpiredMessage.value = false
      router.replace({ path: '/', query: {} })
    }, 5000)
  }

  // 處理登入結果訊息
  if (route.query.login) {
    const loginStatus = route.query.login
    const message = route.query.message
    const restored = route.query.restored

    if (loginStatus === 'success') {
      console.log('登入成功！重新檢查認證狀態...')

      // 重新檢查認證狀態以更新 UI
      await authStore.checkAuth()

      // 檢查是否為帳號恢復
      if (restored === '1') {
        // 顯示帳號恢復提示（使用 ErrorMessage 元件暫時顯示，或可建立專用 Toast 元件）
        alert('歡迎回來！您的帳號資料已完全恢復')
      }
    } else if (loginStatus === 'cancelled') {
      player.errorMessage.value = message || '您已取消 LINE 登入'
    } else if (loginStatus === 'error') {
      player.errorMessage.value = message || '登入失敗，請重試'
    }

    // 清除 query 參數
    setTimeout(() => {
      router.replace({ path: '/', query: {} })
    }, 100)
  }
})
</script>

<style scoped>
.container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
  background: linear-gradient(to bottom, #ffffff, #f8f9fa);
}

/* Header */
.app-header {
  padding: 1.5rem 1rem;
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-text {
  text-align: center;
  flex: 1;
}

.app-title {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  color: #212121;
  background: linear-gradient(135deg, #ff0000, #cc0000);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.app-subtitle {
  margin: 0;
  font-size: 1.125rem;
  color: #616161;
  font-weight: 400;
}

/* Main */
.app-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* 認證提示訊息 */
.auth-required-message {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background-color: #e3f2fd;
  border: 1px solid #90caf9;
  border-radius: var(--radius-lg);
  color: #1976d2;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.auth-required-message .info-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.auth-required-message p {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 500;
}

/* 會話過期提示訊息 */
.session-expired-message {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background-color: #fff3e0;
  border: 1px solid #ffb74d;
  border-radius: var(--radius-lg);
  color: #f57c00;
  animation: slideDown 0.3s ease-out;
}

.session-expired-message .warning-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.session-expired-message p {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 500;
}

/* Welcome Message */
.welcome-message {
  text-align: center;
  padding: 4rem 2rem;
  background-color: #ffffff;
  border-radius: var(--radius-xl);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.welcome-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  color: #ff0000;
}

.welcome-icon svg {
  width: 100%;
  height: 100%;
}

.welcome-title {
  margin: 0 0 1rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #212121;
}

.welcome-text {
  margin: 0;
  font-size: 1.125rem;
  line-height: 1.6;
  color: #616161;
  max-width: 600px;
  margin: 0 auto;
}

/* Footer */
.app-footer {
  margin-top: 3rem;
  padding: 2rem 1rem;
  text-align: center;
  border-top: 1px solid #e0e0e0;
}

.footer-text {
  margin: 0;
  font-size: 0.875rem;
  color: #9e9e9e;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .container {
    padding: 0.75rem;
  }

  .app-header {
    padding: 1rem 0.5rem;
    margin-bottom: 1.5rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
  }

  .app-title {
    font-size: 2rem;
  }

  .app-subtitle {
    font-size: 1rem;
  }

  .welcome-message {
    padding: 3rem 1.5rem;
  }

  .welcome-icon {
    width: 60px;
    height: 60px;
  }

  .welcome-title {
    font-size: 1.5rem;
  }

  .welcome-text {
    font-size: 1rem;
  }

  .app-footer {
    margin-top: 2rem;
    padding: 1.5rem 0.5rem;
  }
}

@media (max-width: 480px) {
  .app-title {
    font-size: 1.75rem;
  }

  .app-subtitle {
    font-size: 0.9375rem;
  }

  .welcome-message {
    padding: 2rem 1rem;
  }

  .welcome-title {
    font-size: 1.25rem;
  }

  .welcome-text {
    font-size: 0.9375rem;
  }
}
</style>
