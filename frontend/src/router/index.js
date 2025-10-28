import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/Home.vue')
  },
  {
    path: '/playlists',
    name: 'PlaylistManager',
    component: () => import('../views/PlaylistManager.vue')
  },
  {
    path: '/playlists/:id',
    name: 'PlaylistDetail',
    component: () => import('../views/PlaylistDetail.vue')
  },
  {
    path: '/library',
    name: 'VideoLibrary',
    component: () => import('../views/VideoLibrary.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
