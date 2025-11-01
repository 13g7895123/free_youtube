import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

/**
 * 認證守衛：檢查用戶是否已登入
 */
async function requireAuth(to, from, next) {
  const authStore = useAuthStore()

  // 如果尚未檢查認證狀態，先檢查
  if (!authStore.isAuthenticated && !authStore.isLoading) {
    await authStore.checkAuth()
  }

  if (authStore.isAuthenticated) {
    next()
  } else {
    // 未登入，重定向到首頁並顯示提示
    next({
      path: '/',
      query: { requireAuth: '1', from: to.path }
    })
  }
}

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/Home.vue')
  },
  {
    path: '/playlists',
    name: 'PlaylistManager',
    component: () => import('../views/PlaylistManager.vue'),
    beforeEnter: requireAuth
  },
  {
    path: '/playlists/:id',
    name: 'PlaylistDetail',
    component: () => import('../views/PlaylistDetail.vue'),
    beforeEnter: requireAuth
  },
  {
    path: '/library',
    name: 'VideoLibrary',
    component: () => import('../views/VideoLibrary.vue'),
    beforeEnter: requireAuth
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
