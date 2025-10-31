<template>
  <div id="app">
    <nav class="navbar">
      <div class="nav-container">
        <router-link to="/" class="nav-brand">
          <PlayCircleIcon class="brand-icon" />
          YouTube Loop Player
        </router-link>
        <div class="nav-links">
          <router-link to="/" class="nav-link">播放器</router-link>
          <router-link to="/library" class="nav-link">影片庫</router-link>
          <router-link to="/playlists" class="nav-link">播放清單</router-link>
        </div>
      </div>
    </nav>
    <router-view />
    <FloatingPlayer />
    <Toast />
  </div>
</template>

<script setup>
import FloatingPlayer from '@/components/FloatingPlayer.vue'
import Toast from '@/components/Toast.vue'
import { PlayCircleIcon } from '@heroicons/vue/24/solid'
</script>

<style>
/* 全局樣式 */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

#app {
  min-height: 100vh;
  background: linear-gradient(to bottom, #ffffff, #f8f9fa);
}

/* Navbar 樣式 */
.navbar {
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand {
  font-size: 1.5rem;
  font-weight: 700;
  text-decoration: none;
  color: #212121;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.nav-brand:hover {
  color: #ff0000;
}

.brand-icon {
  width: 32px;
  height: 32px;
  color: #ff0000;
  flex-shrink: 0;
}

.nav-links {
  display: flex;
  gap: 1.5rem;
}

.nav-link {
  text-decoration: none;
  color: #616161;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.2s;
}

.nav-link:hover {
  background: #f5f5f5;
  color: #212121;
}

.nav-link.router-link-active {
  color: #ff0000;
  background: #ffebee;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .nav-container {
    flex-direction: column;
    gap: 1rem;
  }

  .nav-links {
    width: 100%;
    justify-content: center;
  }

  .nav-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }
}
</style>
