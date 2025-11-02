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
  const loopMode = ref('playlist') // 'playlist' | 'single'
  const shuffleEnabled = ref(false)

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

    const playlistLength = currentPlaylist.value.items.length

    // 如果是單曲循環模式，重播當前歌曲
    if (loopMode.value === 'single') {
      currentVideo.value = { ...currentPlaylist.value.items[currentIndex.value] }
      isPlaying.value = true
      return
    }

    let nextIndex

    // 如果啟用隨機播放
    if (shuffleEnabled.value) {
      // 選擇一首不是當前歌曲的隨機歌曲
      const availableIndices = Array.from({ length: playlistLength }, (_, i) => i)
        .filter(i => i !== currentIndex.value)

      if (availableIndices.length === 0) {
        // 如果播放清單只有一首歌，就重播
        nextIndex = currentIndex.value
      } else {
        // 隨機選擇
        nextIndex = availableIndices[Math.floor(Math.random() * availableIndices.length)]
      }
    } else {
      // 正常順序播放
      nextIndex = currentIndex.value + 1
      if (nextIndex >= playlistLength) {
        // 播放清單循環模式：回到第一首
        nextIndex = 0
      }
    }

    currentIndex.value = nextIndex
    currentVideo.value = currentPlaylist.value.items[nextIndex]
    isPlaying.value = true
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

  const toggleLoopMode = () => {
    const oldMode = loopMode.value
    loopMode.value = loopMode.value === 'playlist' ? 'single' : 'playlist'
    console.log('toggleLoopMode: changed from', oldMode, 'to', loopMode.value)
  }

  const toggleShuffle = () => {
    const oldValue = shuffleEnabled.value
    shuffleEnabled.value = !shuffleEnabled.value
    console.log('toggleShuffle: changed from', oldValue, 'to', shuffleEnabled.value)
  }

  return {
    // State
    isPlaying,
    currentVideo,
    currentPlaylist,
    currentIndex,
    isMinimized,
    isVisible,
    loopMode,
    shuffleEnabled,
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
    clear,
    toggleLoopMode,
    toggleShuffle
  }
})
