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

      <!-- DEBUG 認證狀態顯示 (開發用) -->
      <div v-if="debugMode" class="debug-panel">
        <div class="debug-header">
          <h3>🔍 認證流程 DEBUG 資訊</h3>
          <button @click="debugMode = false" class="debug-close">✕</button>
        </div>
        <div class="debug-content">
          <div class="debug-section">
            <h4>認證狀態</h4>
            <div class="debug-item">
              <span class="debug-label">isAuthenticated:</span>
              <span :class="['debug-value', authStore.isAuthenticated ? 'success' : 'error']">
                {{ authStore.isAuthenticated }}
              </span>
            </div>
            <div class="debug-item">
              <span class="debug-label">當前用戶:</span>
              <span class="debug-value">{{ authStore.user ? authStore.user.display_name : 'null' }}</span>
            </div>
            <div class="debug-item">
              <span class="debug-label">用戶 ID:</span>
              <span class="debug-value">{{ authStore.user ? authStore.user.id : 'null' }}</span>
            </div>
          </div>

          <div class="debug-section">
            <h4>Cookie 狀態</h4>
            <div class="debug-item">
              <span class="debug-label">Document.cookie:</span>
              <span class="debug-value cookie">{{ cookieStatus }}</span>
            </div>
            <div class="debug-item">
              <span class="debug-label">Has access_token:</span>
              <span :class="['debug-value', hasAccessTokenCookie ? 'success' : 'error']">
                {{ hasAccessTokenCookie }}
              </span>
            </div>
          </div>

          <div class="debug-section">
            <h4>API 配置</h4>
            <div class="debug-item">
              <span class="debug-label">API URL:</span>
              <span class="debug-value">{{ apiUrl }}</span>
            </div>
            <div class="debug-item">
              <span class="debug-label">withCredentials:</span>
              <span class="debug-value success">true</span>
            </div>
            <div class="debug-item">
              <span class="debug-label">Auth Mode:</span>
              <span class="debug-value">{{ authMode }}</span>
            </div>
          </div>

          <div class="debug-section">
            <h4>認證流程日誌</h4>
            <div class="debug-log-container">
              <div v-for="(log, index) in debugLogs" :key="index" class="debug-log">
                <span class="debug-time">{{ log.time }}</span>
                <span :class="['debug-type', log.type]">{{ log.type }}</span>
                <span class="debug-message">{{ log.message }}</span>
                <pre v-if="log.data" class="debug-data">{{ JSON.stringify(log.data, null, 2) }}</pre>
              </div>
            </div>
          </div>

          <div class="debug-section">
            <h4>操作</h4>
            <div class="debug-actions">
              <button @click="testCheckAuth" class="debug-btn">測試 checkAuth</button>
              <button @click="testApiCall" class="debug-btn">測試 API Call</button>
              <button @click="checkCookies" class="debug-btn">檢查 Cookies</button>
              <button @click="clearDebugLogs" class="debug-btn">清除日誌</button>
            </div>
          </div>
        </div>
      </div>

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

      <!-- 儲存影片操作 -->
      <SaveVideoActions
        v-if="hasVideo && player.isReady.value"
        :get-video-info="getVideoInfo"
      />

      <!-- 循環播放控制 -->
      <LoopToggle
        v-if="hasVideo"
        :is-enabled="player.loopEnabled.value"
        @toggle="handleLoopToggle"
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
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
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

// DEBUG 模式相關
const debugMode = ref(false) // 預設關閉 DEBUG 模式，需要時可改為 true
const debugLogs = ref([])
const cookieStatus = ref('')
const hasAccessTokenCookie = ref(false)
const apiUrl = import.meta.env.VITE_API_URL || '/api'
const authMode = import.meta.env.VITE_AUTH_MODE || 'line'

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
  console.log('音量變更請求:', volume)
  console.log('播放器就緒狀態:', player.isReady.value)
  console.log('當前靜音狀態:', player.isMuted.value)

  // 檢查播放器是否就緒
  if (!player.isReady.value) {
    console.warn('播放器尚未就緒，無法更改音量')
    return
  }

  // 如果正在靜音，調整音量時自動取消靜音
  if (player.isMuted.value && volume > 0) {
    console.log('自動取消靜音')
    player.unmute()
  }

  // 設置音量
  player.setVolume(volume)
  console.log('音量已設置為:', volume)
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

/**
 * DEBUG 相關函數
 */

// 添加 debug 日誌
function addDebugLog(type, message, data = null) {
  const time = new Date().toLocaleTimeString('zh-TW')
  debugLogs.value.unshift({
    time,
    type,
    message,
    data
  })
  // 限制日誌數量
  if (debugLogs.value.length > 50) {
    debugLogs.value = debugLogs.value.slice(0, 50)
  }
}

// 檢查 Cookies
function checkCookies() {
  addDebugLog('info', '檢查 Cookies...')

  // 檢查 document.cookie
  const cookies = document.cookie
  cookieStatus.value = cookies || '(空)'

  // 檢查是否有 access_token
  hasAccessTokenCookie.value = cookies.includes('access_token')

  addDebugLog('info', 'Cookie 檢查完成', {
    cookies: cookies || '無',
    hasAccessToken: hasAccessTokenCookie.value
  })
}

// 測試 checkAuth
async function testCheckAuth() {
  addDebugLog('info', '開始測試 checkAuth...')

  try {
    await authStore.checkAuth()
    addDebugLog('success', 'checkAuth 完成', {
      isAuthenticated: authStore.isAuthenticated,
      user: authStore.user
    })
  } catch (error) {
    addDebugLog('error', 'checkAuth 失敗', {
      error: error.message,
      response: error.response?.data
    })
  }
}

// 測試 API Call
async function testApiCall() {
  addDebugLog('info', '開始測試 API Call...')

  try {
    // 使用 fetch 直接測試，以便看到更多細節
    const response = await fetch(`${apiUrl}/auth/user`, {
      method: 'GET',
      credentials: 'include', // 這相當於 withCredentials: true
      headers: {
        'Content-Type': 'application/json',
      }
    })

    const data = await response.json()

    addDebugLog(response.ok ? 'success' : 'error', `API 回應: ${response.status}`, {
      status: response.status,
      statusText: response.statusText,
      data: data,
      headers: Object.fromEntries(response.headers.entries())
    })
  } catch (error) {
    addDebugLog('error', 'API Call 失敗', {
      error: error.message
    })
  }
}

// 清除 debug 日誌
function clearDebugLogs() {
  debugLogs.value = []
  addDebugLog('info', 'Debug 日誌已清除')
}

/**
 * 路由守衛：離開頁面時處理播放器狀態
 * 如果正在播放影片，將狀態轉移到 FloatingPlayer 並以展開模式顯示
 */
onBeforeRouteLeave((to, from) => {
  // 檢查是否正在播放
  if (player.isReady.value && player.isPlaying.value) {
    console.log('離開首頁，正在播放影片，轉移到懸浮視窗')

    // 獲取當前影片資訊
    const videoInfo = player.getCurrentVideoInfo()

    if (videoInfo) {
      // 將當前播放狀態轉移到 FloatingPlayer
      globalPlayerStore.playVideo({
        video_id: videoInfo.videoId,
        title: videoInfo.title,
        youtube_url: videoInfo.youtubeUrl,
        thumbnail_url: videoInfo.thumbnail,
        duration: videoInfo.duration,
        channel_name: videoInfo.author
      })

      // 設為展開模式（不是最小化）
      globalPlayerStore.show()

      console.log('懸浮視窗已啟用（展開模式）')
    }
  }

  // 允許導航
  return true
})

// 組件掛載時預先載入 YouTube API（但不初始化播放器）
onMounted(async () => {
  // 初始化時檢查一次 Cookie
  checkCookies()
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

    addDebugLog('info', '檢測到登入回調', {
      loginStatus,
      message,
      restored
    })

    if (loginStatus === 'success') {
      console.log('登入成功！檢查認證狀態...')
      addDebugLog('success', 'LINE 登入成功，開始檢查認證狀態')

      // 檢查 Cookie 狀態
      checkCookies()

      // 添加延遲以確保 cookie 已設置
      addDebugLog('info', '等待 300ms 確保 Cookie 已設置...')
      await new Promise(resolve => setTimeout(resolve, 300))

      // 再次檢查 Cookie
      checkCookies()

      // 重試邏輯：最多嘗試 3 次
      let authSuccess = false
      for (let i = 0; i < 3; i++) {
        console.log(`認證檢查嘗試 ${i + 1}/3...`)
        addDebugLog('info', `認證檢查嘗試 ${i + 1}/3`)

        try {
          await authStore.checkAuth()
          addDebugLog('info', `checkAuth 結果`, {
            isAuthenticated: authStore.isAuthenticated,
            user: authStore.user
          })
        } catch (error) {
          addDebugLog('error', `checkAuth 失敗`, {
            error: error.message,
            response: error.response?.data
          })
        }

        if (authStore.isAuthenticated) {
          console.log('認證成功！')
          addDebugLog('success', '認證成功！')
          authSuccess = true

          // 檢查是否為帳號恢復
          if (restored === '1') {
            alert('歡迎回來！您的帳號資料已完全恢復')
          }
          break
        }

        // 如果未成功且還有重試機會，等待後重試
        if (i < 2) {
          console.log('認證尚未成功，等待後重試...')
          addDebugLog('warning', '認證尚未成功，等待 500ms 後重試...')
          await new Promise(resolve => setTimeout(resolve, 500))
        }
      }

      if (!authSuccess) {
        console.warn('認證檢查失敗，請手動重新整理頁面')
        addDebugLog('error', '3 次認證檢查都失敗', {
          finalStatus: {
            isAuthenticated: authStore.isAuthenticated,
            user: authStore.user,
            cookieStatus: cookieStatus.value,
            hasAccessToken: hasAccessTokenCookie.value
          }
        })
        player.errorMessage.value = '登入成功但認證檢查失敗，請重新整理頁面'
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

/* DEBUG Panel Styles */
.debug-panel {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 500px;
  max-height: 80vh;
  background: white;
  border: 2px solid #ff6b6b;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.debug-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #ff6b6b;
  color: white;
}

.debug-header h3 {
  margin: 0;
  font-size: 16px;
}

.debug-close {
  background: none;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.debug-close:hover {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.debug-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.debug-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e0e0e0;
}

.debug-section:last-child {
  border-bottom: none;
}

.debug-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.debug-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 8px;
  font-size: 13px;
}

.debug-label {
  font-weight: 600;
  color: #666;
  margin-right: 8px;
  min-width: 120px;
}

.debug-value {
  color: #333;
  word-break: break-all;
}

.debug-value.success {
  color: #4caf50;
  font-weight: 600;
}

.debug-value.error {
  color: #f44336;
  font-weight: 600;
}

.debug-value.cookie {
  font-family: monospace;
  font-size: 11px;
  background: #f5f5f5;
  padding: 4px;
  border-radius: 3px;
}

.debug-log-container {
  max-height: 200px;
  overflow-y: auto;
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 8px;
}

.debug-log {
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e0e0e0;
  font-size: 12px;
}

.debug-log:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.debug-time {
  color: #999;
  margin-right: 8px;
  font-size: 11px;
}

.debug-type {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  margin-right: 8px;
}

.debug-type.info {
  background: #e3f2fd;
  color: #1976d2;
}

.debug-type.success {
  background: #e8f5e9;
  color: #4caf50;
}

.debug-type.warning {
  background: #fff3e0;
  color: #ff9800;
}

.debug-type.error {
  background: #ffebee;
  color: #f44336;
}

.debug-message {
  color: #333;
}

.debug-data {
  margin-top: 4px;
  padding: 8px;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 3px;
  font-size: 11px;
  font-family: monospace;
  overflow-x: auto;
}

.debug-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.debug-btn {
  padding: 6px 12px;
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.debug-btn:hover {
  background: #1976d2;
}

.debug-btn:active {
  transform: scale(0.98);
}
</style>
