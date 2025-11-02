import { ref, computed } from 'vue'

export function useYoutubePlayer() {
  const player = ref(null)
  const currentVideoId = ref(null)
  const isPlayerReady = ref(false)
  const isPlaying = ref(false)
  const currentTime = ref(0)
  const duration = ref(0)

  const initPlayer = (elementId, videoId, options = {}) => {
    if (!window.YT) {
      console.error('YouTube IFrame API not loaded')
      return
    }

    currentVideoId.value = videoId

    player.value = new window.YT.Player(elementId, {
      width: '100%',
      height: '400',
      videoId: videoId,
      playerVars: {
        autoplay: options.autoplay ? 1 : 0,
        controls: options.controls !== false ? 1 : 0,
        modestbranding: 1,
        ...options.playerVars
      },
      events: {
        onReady: onPlayerReady,
        onStateChange: onPlayerStateChange,
        onError: onPlayerError
      }
    })
  }

  const onPlayerReady = () => {
    isPlayerReady.value = true
  }

  const onPlayerStateChange = (event) => {
    switch (event.data) {
      case window.YT.PlayerState.PLAYING:
        isPlaying.value = true
        break
      case window.YT.PlayerState.PAUSED:
        isPlaying.value = false
        break
      case window.YT.PlayerState.ENDED:
        isPlaying.value = false
        break
    }
  }

  const onPlayerError = (event) => {
    console.error('YouTube Player Error:', event)
  }

  const playVideo = (videoId) => {
    if (!player.value) return
    if (currentVideoId.value !== videoId) {
      player.value.loadVideoById(videoId)
      currentVideoId.value = videoId
    }
    player.value.playVideo()
  }

  const pauseVideo = () => {
    if (!player.value) return
    player.value.pauseVideo()
  }

  const stopVideo = () => {
    if (!player.value) return
    player.value.stopVideo()
  }

  const play = () => {
    if (!player.value) return
    player.value.playVideo()
  }

  const pause = () => {
    if (!player.value) return
    player.value.pauseVideo()
  }

  const seekTo = (seconds) => {
    if (!player.value) return
    player.value.seekTo(seconds, true)
  }

  const setVolume = (volume) => {
    if (!player.value) return
    player.value.setVolume(Math.max(0, Math.min(100, volume)))
  }

  const getVolume = () => {
    if (!player.value) return 0
    return player.value.getVolume()
  }

  const mute = () => {
    if (!player.value) return
    player.value.mute()
  }

  const unMute = () => {
    if (!player.value) return
    player.value.unMute()
  }

  const getCurrentTime = () => {
    if (!player.value) return 0
    return player.value.getCurrentTime()
  }

  const getDuration = () => {
    if (!player.value) return 0
    return player.value.getDuration()
  }

  const destroyPlayer = () => {
    if (!player.value) return
    player.value.destroy()
    player.value = null
    isPlayerReady.value = false
  }

  return {
    player,
    currentVideoId,
    isPlayerReady,
    isPlaying,
    currentTime,
    duration,
    initPlayer,
    playVideo,
    pauseVideo,
    stopVideo,
    play,
    pause,
    seekTo,
    setVolume,
    getVolume,
    mute,
    unMute,
    getCurrentTime,
    getDuration,
    destroyPlayer
  }
}
