import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useGlobalPlayerStore = defineStore('globalPlayer', () => {
  // State
  const isPlaying = ref(false)
  const currentVideo = ref(null)
  const currentPlaylist = ref(null)
  const currentIndex = ref(0)
  const isMinimized = ref(false)
  const isVisible = ref(false)

  // Computed
  const hasVideo = computed(() => currentVideo.value !== null)
  const hasPlaylist = computed(() => currentPlaylist.value !== null && currentPlaylist.value.items?.length > 0)

  // Actions
  const playVideo = (videoInfo) => {
    currentVideo.value = videoInfo
    currentPlaylist.value = null
    currentIndex.value = 0
    isPlaying.value = true
    isVisible.value = true
    isMinimized.value = false
  }

  const playPlaylist = (playlist, startIndex = 0) => {
    if (!playlist || !playlist.items || playlist.items.length === 0) {
      return
    }
    currentPlaylist.value = playlist
    currentIndex.value = startIndex
    currentVideo.value = playlist.items[startIndex]
    isPlaying.value = true
    isVisible.value = true
    isMinimized.value = false
  }

  const play = () => {
    isPlaying.value = true
  }

  const pause = () => {
    isPlaying.value = false
  }

  const togglePlay = () => {
    isPlaying.value = !isPlaying.value
  }

  const next = () => {
    if (!hasPlaylist.value) return

    const nextIndex = currentIndex.value + 1
    if (nextIndex < currentPlaylist.value.items.length) {
      currentIndex.value = nextIndex
      currentVideo.value = currentPlaylist.value.items[nextIndex]
      isPlaying.value = true
    } else {
      // Loop to first video
      currentIndex.value = 0
      currentVideo.value = currentPlaylist.value.items[0]
      isPlaying.value = true
    }
  }

  const previous = () => {
    if (!hasPlaylist.value) return

    const prevIndex = currentIndex.value - 1
    if (prevIndex >= 0) {
      currentIndex.value = prevIndex
      currentVideo.value = currentPlaylist.value.items[prevIndex]
      isPlaying.value = true
    } else {
      // Loop to last video
      const lastIndex = currentPlaylist.value.items.length - 1
      currentIndex.value = lastIndex
      currentVideo.value = currentPlaylist.value.items[lastIndex]
      isPlaying.value = true
    }
  }

  const minimize = () => {
    isMinimized.value = true
  }

  const maximize = () => {
    isMinimized.value = false
  }

  const close = () => {
    isVisible.value = false
    isPlaying.value = false
  }

  const clear = () => {
    currentVideo.value = null
    currentPlaylist.value = null
    currentIndex.value = 0
    isPlaying.value = false
    isVisible.value = false
    isMinimized.value = false
  }

  return {
    // State
    isPlaying,
    currentVideo,
    currentPlaylist,
    currentIndex,
    isMinimized,
    isVisible,
    // Computed
    hasVideo,
    hasPlaylist,
    // Actions
    playVideo,
    playPlaylist,
    play,
    pause,
    togglePlay,
    next,
    previous,
    minimize,
    maximize,
    close,
    clear
  }
})
