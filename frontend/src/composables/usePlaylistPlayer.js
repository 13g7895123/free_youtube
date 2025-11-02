import { ref, computed } from 'vue'
import { useYoutubePlayer } from './useYoutubePlayer'

/**
 * T063, T064: Composable for playlist playback with auto-advance and loop
 * Handles playing multiple videos in sequence with auto-advance to next
 */
export function usePlaylistPlayer() {
  const youtubePlayer = useYoutubePlayer()

  const playlistItems = ref([])
  const currentIndex = ref(0)
  const isAutoPlayNext = ref(true)
  const isLoopPlaylist = ref(true)

  const currentItem = computed(() => {
    if (playlistItems.value.length === 0) return null
    return playlistItems.value[currentIndex.value]
  })

  const currentVideo = computed(() => {
    if (!currentItem.value) return null
    return currentItem.value.video
  })

  const totalItems = computed(() => playlistItems.value.length)

  const hasNextItem = computed(() => {
    return currentIndex.value < playlistItems.value.length - 1
  })

  const hasPreviousItem = computed(() => {
    return currentIndex.value > 0
  })

  const playlistProgress = computed(() => {
    if (totalItems.value === 0) return '0/0'
    return `${currentIndex.value + 1}/${totalItems.value}`
  })

  /**
   * Initialize playlist playback
   */
  const initPlaylist = (items, elementId, startIndex = 0) => {
    playlistItems.value = items
    currentIndex.value = startIndex

    if (playlistItems.value.length === 0) return

    youtubePlayer.initPlayer(elementId, playlistItems.value[startIndex].video.youtube_id, {
      autoplay: true,
      playerVars: {
        onended: onVideoEnded
      }
    })
  }

  /**
   * Play current video
   */
  const playCurrent = () => {
    if (!currentVideo.value) return
    youtubePlayer.playVideo(currentVideo.value.youtube_id)
  }

  /**
   * Handle when current video ends
   * T064: Auto-advance with loop support
   */
  const onVideoEnded = () => {
    if (!isAutoPlayNext.value) return

    if (hasNextItem.value) {
      playNext()
    } else if (isLoopPlaylist.value) {
      // T064: Loop back to first video
      currentIndex.value = 0
      playCurrent()
    }
  }

  /**
   * Play next video in playlist
   */
  const playNext = () => {
    if (hasNextItem.value) {
      currentIndex.value++
      playCurrent()
    } else if (isLoopPlaylist.value) {
      currentIndex.value = 0
      playCurrent()
    }
  }

  /**
   * Play previous video in playlist
   */
  const playPrevious = () => {
    if (hasPreviousItem.value) {
      currentIndex.value--
      playCurrent()
    } else if (isLoopPlaylist.value) {
      currentIndex.value = playlistItems.value.length - 1
      playCurrent()
    }
  }

  /**
   * T067: Jump to specific video in playlist
   */
  const jumpToIndex = (index) => {
    if (index < 0 || index >= playlistItems.value.length) return
    currentIndex.value = index
    playCurrent()
  }

  /**
   * Set auto-play next
   */
  const setAutoPlayNext = (value) => {
    isAutoPlayNext.value = value
  }

  /**
   * Set loop playlist
   */
  const setLoopPlaylist = (value) => {
    isLoopPlaylist.value = value
  }

  /**
   * Clear playlist
   */
  const clear = () => {
    playlistItems.value = []
    currentIndex.value = 0
    youtubePlayer.destroyPlayer()
  }

  /**
   * Update playlist items
   */
  const updateItems = (items) => {
    playlistItems.value = items
  }

  return {
    playlistItems,
    currentIndex,
    currentItem,
    currentVideo,
    totalItems,
    hasNextItem,
    hasPreviousItem,
    playlistProgress,
    isAutoPlayNext,
    isLoopPlaylist,
    youtubePlayer,
    initPlaylist,
    playCurrent,
    playNext,
    playPrevious,
    jumpToIndex,
    setAutoPlayNext,
    setLoopPlaylist,
    clear,
    updateItems
  }
}
