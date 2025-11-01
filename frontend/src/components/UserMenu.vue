<template>
  <div class="user-menu">
    <!-- 載入中狀態 -->
    <div v-if="authStore.isLoading" class="loading-skeleton">
      <div class="skeleton-circle"></div>
    </div>

    <!-- 已登入狀態 -->
    <div v-else-if="authStore.isAuthenticated" class="user-info">
      <!-- 用戶頭像 -->
      <img
        v-if="authStore.userAvatar"
        :src="authStore.userAvatar"
        :alt="authStore.userDisplayName"
        class="user-avatar"
        @error="handleAvatarError"
      />
      <div v-else class="user-avatar-placeholder">
        {{ getInitial(authStore.userDisplayName) }}
      </div>

      <!-- 用戶名稱 -->
      <span class="user-name">{{ authStore.userDisplayName }}</span>

      <!-- 登出按鈕 -->
      <button
        class="logout-button"
        @click="handleLogout"
        title="登出"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
        >
          <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
        </svg>
      </button>
    </div>

    <!-- 未登入狀態 -->
    <div v-else class="guest-actions">
      <button
        class="login-button"
        @click="handleLogin"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="line-icon"
        >
          <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/>
        </svg>
        LINE 登入
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

/**
 * 元件掛載時檢查認證狀態
 */
onMounted(async () => {
  await authStore.checkAuth()
})

/**
 * 處理登入
 */
function handleLogin() {
  authStore.login()
}

/**
 * 處理登出
 */
async function handleLogout() {
  if (confirm('確定要登出嗎？')) {
    await authStore.logout()
  }
}

/**
 * 取得用戶名稱首字母（用於頭像佔位符）
 */
function getInitial(name) {
  if (!name) return '?'
  return name.charAt(0).toUpperCase()
}

/**
 * 處理頭像載入錯誤
 */
function handleAvatarError(event) {
  event.target.style.display = 'none'
}
</script>

<style scoped>
.user-menu {
  display: flex;
  align-items: center;
}

/* 載入骨架屏 */
.loading-skeleton {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.skeleton-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* 已登入用戶資訊 */
.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background-color: #f5f5f5;
  border-radius: var(--radius-full, 9999px);
  transition: background-color 0.2s ease;
}

.user-info:hover {
  background-color: #eeeeee;
}

.user-avatar,
.user-avatar-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  flex-shrink: 0;
}

.user-avatar {
  object-fit: cover;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-avatar-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  font-weight: 600;
  font-size: 1.125rem;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-name {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #212121;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logout-button {
  width: 32px;
  height: 32px;
  padding: 0;
  background-color: transparent;
  border: none;
  border-radius: var(--radius-sm, 4px);
  cursor: pointer;
  color: #757575;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logout-button:hover {
  background-color: #ffebee;
  color: #ff0000;
}

.logout-button:active {
  transform: scale(0.95);
}

.logout-button svg {
  width: 20px;
  height: 20px;
}

/* 訪客登入按鈕 */
.guest-actions {
  display: flex;
  gap: 0.75rem;
}

.login-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #ffffff;
  background: linear-gradient(135deg, #00b900 0%, #00c300 100%);
  border: none;
  border-radius: var(--radius-full, 9999px);
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 185, 0, 0.3);
}

.login-button:hover {
  background: linear-gradient(135deg, #00c300 0%, #00d400 100%);
  box-shadow: 0 4px 12px rgba(0, 185, 0, 0.4);
  transform: translateY(-1px);
}

.login-button:active {
  transform: translateY(0);
  box-shadow: 0 2px 6px rgba(0, 185, 0, 0.3);
}

.line-icon {
  width: 20px;
  height: 20px;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .user-name {
    display: none;
  }

  .user-info {
    padding: 0.5rem;
    gap: 0.5rem;
  }

  .login-button {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }

  .login-button span {
    display: none;
  }
}
</style>
