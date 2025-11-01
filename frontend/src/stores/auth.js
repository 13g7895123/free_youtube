import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)

  // Getters
  const isGuest = computed(() => !isAuthenticated.value)
  const userDisplayName = computed(() => user.value?.display_name || '')
  const userAvatar = computed(() => user.value?.avatar_url || '')

  // Actions

  /**
   * 檢查當前認證狀態
   */
  async function checkAuth() {
    isLoading.value = true
    try {
      const response = await api.get('/auth/user')
      if (response.data.success && response.data.data) {
        const wasGuest = !isAuthenticated.value
        user.value = response.data.data
        isAuthenticated.value = true

        // 如果是首次登入，自動觸發訪客資料遷移
        if (wasGuest) {
          await migrateGuestData()
        }
      } else {
        user.value = null
        isAuthenticated.value = false
      }
    } catch (error) {
      user.value = null
      isAuthenticated.value = false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * 登入流程
   * - Mock 模式：呼叫 Mock API
   * - LINE Login 模式：重定向到 LINE OAuth
   */
  function login() {
    const authMode = import.meta.env.VITE_AUTH_MODE || 'line'

    if (authMode === 'mock') {
      // Mock 模式：呼叫後端 Mock API
      loginWithMock()
    } else {
      // LINE Login 模式：重定向到 LINE OAuth
      const apiUrl = import.meta.env.VITE_API_URL || ''
      window.location.href = `${apiUrl}/api/auth/line/login`
    }
  }

  /**
   * Mock 登入
   * 僅在開發環境使用，直接呼叫後端 Mock API
   */
  async function loginWithMock() {
    isLoading.value = true
    try {
      const response = await api.post('/auth/mock/login')
      if (response.data.success) {
        // 直接設置會員資訊
        user.value = response.data.data.user
        isAuthenticated.value = true

        // 觸發訪客資料遷移
        await migrateGuestData()

        console.log('Mock 登入成功:', user.value.display_name)

        // 重新整理以更新 UI
        if (typeof window !== 'undefined') {
          window.location.href = '/?login=success'
        }
      }
    } catch (error) {
      console.error('Mock 登入失敗:', error)
      // 顯示錯誤訊息
      alert('Mock 登入失敗：' + (error.response?.data?.message || error.message))
    } finally {
      isLoading.value = false
    }
  }

  /**
   * 登出
   */
  async function logout() {
    isLoading.value = true
    try {
      await api.post('/auth/logout')
      user.value = null
      isAuthenticated.value = false

      // 重定向到首頁
      if (typeof window !== 'undefined') {
        window.location.href = '/'
      }
    } catch (error) {
      console.error('登出失敗:', error)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * 更新 Token (使用 refresh token)
   */
  async function refreshToken() {
    try {
      const response = await api.post('/auth/refresh')
      if (response.data.success) {
        return true
      }
      return false
    } catch (error) {
      console.error('Token 更新失敗:', error)
      return false
    }
  }

  /**
   * 處理 401 未授權事件
   */
  function handleUnauthorized() {
    user.value = null
    isAuthenticated.value = false

    // 如果已經在 session expired 頁面，不要再次重定向
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search)
      if (params.get('session') === 'expired') {
        console.warn('Session 已過期')
        return
      }
    }

    // 記錄當前路徑，方便登入後導回
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/'

    // 顯示提示訊息
    console.warn('認證已過期，請重新登入')

    // 重定向到首頁並顯示提示
    if (typeof window !== 'undefined') {
      const redirectPath = currentPath !== '/'
        ? `/?session=expired&from=${encodeURIComponent(currentPath)}`
        : '/?session=expired'

      window.location.href = redirectPath
    }
  }

  /**
   * 遷移訪客資料到會員帳號
   */
  async function migrateGuestData() {
    try {
      // 從 LocalStorage 取得訪客歷史記錄
      const guestHistoryKey = 'youtube-loop-player-guest-history'
      const guestHistoryStr = localStorage.getItem(guestHistoryKey)

      if (!guestHistoryStr) {
        console.log('無訪客歷史記錄需要遷移')
        return
      }

      const guestHistory = JSON.parse(guestHistoryStr)

      if (!Array.isArray(guestHistory) || guestHistory.length === 0) {
        console.log('訪客歷史記錄為空')
        return
      }

      // 呼叫遷移 API
      const response = await api.post('/auth/migrate-guest-data', {
        history: guestHistory
      })

      if (response.data.success) {
        const { migrated_count, skipped_count } = response.data.data
        console.log(`訪客資料遷移完成: 成功 ${migrated_count} 筆, 跳過 ${skipped_count} 筆`)

        // 遷移成功後清除 LocalStorage 中的訪客資料
        localStorage.removeItem(guestHistoryKey)
      }
    } catch (error) {
      console.error('訪客資料遷移失敗:', error)
      // 不中斷登入流程，遷移失敗不影響用戶使用
    }
  }

  // 監聽 401 事件 (由 axios interceptor 觸發)
  if (typeof window !== 'undefined') {
    window.addEventListener('auth:unauthorized', handleUnauthorized)
  }

  return {
    // State
    user,
    isAuthenticated,
    isLoading,

    // Getters
    isGuest,
    userDisplayName,
    userAvatar,

    // Actions
    checkAuth,
    login,
    logout,
    refreshToken,
    handleUnauthorized,
  }
})
