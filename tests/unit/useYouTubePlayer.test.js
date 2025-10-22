import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { nextTick } from 'vue'
import { useYouTubePlayer } from '@/composables/useYouTubePlayer'

/**
 * useYouTubePlayer Composable 單元測試
 * 測試 YouTube 播放器管理邏輯（使用 mock API）
 */

describe('useYouTubePlayer', () => {
  let mockPlayer
  let mockYT

  beforeEach(() => {
    // Mock YouTube IFrame API
    mockPlayer = {
      playVideo: vi.fn(),
      pauseVideo: vi.fn(),
      stopVideo: vi.fn(),
      seekTo: vi.fn(),
      loadVideoById: vi.fn(),
      cueVideoById: vi.fn(),
      getPlayerState: vi.fn(() => -1),
      getCurrentTime: vi.fn(() => 0),
      getDuration: vi.fn(() => 0),
      getVolume: vi.fn(() => 100),
      setVolume: vi.fn(),
      mute: vi.fn(),
      unMute: vi.fn(),
      isMuted: vi.fn(() => false),
      destroy: vi.fn()
    }

    mockYT = {
      Player: vi.fn(function (elementId, config) {
        // 模擬非同步初始化
        setTimeout(() => {
          if (config.events?.onReady) {
            config.events.onReady({ target: mockPlayer })
          }
        }, 0)
        return mockPlayer
      }),
      PlayerState: {
        UNSTARTED: -1,
        ENDED: 0,
        PLAYING: 1,
        PAUSED: 2,
        BUFFERING: 3,
        CUED: 5
      }
    }

    global.YT = mockYT
  })

  afterEach(() => {
    delete global.YT
    vi.clearAllMocks()
  })

  describe('初始化', () => {
    it('應該返回正確的響應式屬性', () => {
      // Arrange & Act
      const player = useYouTubePlayer('player-container')

      // Assert
      expect(player.isReady).toBeDefined()
      expect(player.isPlaying).toBeDefined()
      expect(player.isPaused).toBeDefined()
      expect(player.isBuffering).toBeDefined()
      expect(player.hasError).toBeDefined()
      expect(player.errorMessage).toBeDefined()
      expect(player.currentTime).toBeDefined()
      expect(player.duration).toBeDefined()
    })

    it('應該提供播放器控制方法', () => {
      // Arrange & Act
      const player = useYouTubePlayer('player-container')

      // Assert
      expect(player.loadVideo).toBeDefined()
      expect(player.play).toBeDefined()
      expect(player.pause).toBeDefined()
      expect(player.seekTo).toBeDefined()
      expect(player.destroy).toBeDefined()
      expect(typeof player.loadVideo).toBe('function')
      expect(typeof player.play).toBe('function')
      expect(typeof player.pause).toBe('function')
      expect(typeof player.seekTo).toBe('function')
      expect(typeof player.destroy).toBe('function')
    })

    it('初始狀態應該是未就緒', () => {
      // Arrange & Act
      const player = useYouTubePlayer('player-container')

      // Assert
      expect(player.isReady.value).toBe(false)
      expect(player.isPlaying.value).toBe(false)
      expect(player.isPaused.value).toBe(false)
      expect(player.hasError.value).toBe(false)
      expect(player.errorMessage.value).toBe('')
    })

    it('應該使用提供的容器 ID 建立播放器', () => {
      // Arrange
      const containerId = 'my-player-container'

      // Act
      useYouTubePlayer(containerId)

      // Assert
      expect(mockYT.Player).toHaveBeenCalledWith(
        containerId,
        expect.objectContaining({
          events: expect.any(Object)
        })
      )
    })
  })

  describe('播放器就緒', () => {
    it('應該在播放器就緒時設置 isReady 為 true', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      expect(player.isReady.value).toBe(false)

      // Act - 等待 onReady 事件
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Assert
      expect(player.isReady.value).toBe(true)
    })

    it('應該在播放器就緒前拒絕播放操作', () => {
      // Arrange
      const player = useYouTubePlayer('player-container')

      // Act
      player.play()

      // Assert
      expect(mockPlayer.playVideo).not.toHaveBeenCalled()
    })

    it('應該在播放器就緒後允許播放操作', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.play()

      // Assert
      expect(mockPlayer.playVideo).toHaveBeenCalled()
    })
  })

  describe('載入影片', () => {
    it('應該使用影片 ID 載入影片', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.loadVideo('dQw4w9WgXcQ')

      // Assert
      expect(mockPlayer.loadVideoById).toHaveBeenCalledWith('dQw4w9WgXcQ')
    })

    it('應該在播放器未就緒時不載入影片', () => {
      // Arrange
      const player = useYouTubePlayer('player-container')

      // Act
      player.loadVideo('dQw4w9WgXcQ')

      // Assert
      expect(mockPlayer.loadVideoById).not.toHaveBeenCalled()
    })

    it('應該拒絕無效的影片 ID', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.loadVideo('')

      // Assert
      expect(mockPlayer.loadVideoById).not.toHaveBeenCalled()
    })

    it('應該拒絕 null 影片 ID', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.loadVideo(null)

      // Assert
      expect(mockPlayer.loadVideoById).not.toHaveBeenCalled()
    })
  })

  describe('播放控制', () => {
    it('應該能夠播放影片', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.play()

      // Assert
      expect(mockPlayer.playVideo).toHaveBeenCalled()
    })

    it('應該能夠暫停影片', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.pause()

      // Assert
      expect(mockPlayer.pauseVideo).toHaveBeenCalled()
    })

    it('應該能夠跳轉到指定時間', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.seekTo(42)

      // Assert
      expect(mockPlayer.seekTo).toHaveBeenCalledWith(42, true)
    })

    it('應該拒絕負數的跳轉時間', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.seekTo(-10)

      // Assert
      expect(mockPlayer.seekTo).not.toHaveBeenCalled()
    })

    it('應該拒絕非數字的跳轉時間', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.seekTo('invalid')

      // Assert
      expect(mockPlayer.seekTo).not.toHaveBeenCalled()
    })
  })

  describe('播放器狀態', () => {
    it('應該在播放時更新 isPlaying 狀態', async () => {
      // Arrange
      let onStateChangeCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onStateChangeCallback = config.events?.onStateChange
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      onStateChangeCallback({ data: mockYT.PlayerState.PLAYING, target: mockPlayer })
      await nextTick()

      // Assert
      expect(player.isPlaying.value).toBe(true)
      expect(player.isPaused.value).toBe(false)
    })

    it('應該在暫停時更新 isPaused 狀態', async () => {
      // Arrange
      let onStateChangeCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onStateChangeCallback = config.events?.onStateChange
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      onStateChangeCallback({ data: mockYT.PlayerState.PAUSED, target: mockPlayer })
      await nextTick()

      // Assert
      expect(player.isPaused.value).toBe(true)
      expect(player.isPlaying.value).toBe(false)
    })

    it('應該在緩衝時更新 isBuffering 狀態', async () => {
      // Arrange
      let onStateChangeCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onStateChangeCallback = config.events?.onStateChange
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      onStateChangeCallback({ data: mockYT.PlayerState.BUFFERING, target: mockPlayer })
      await nextTick()

      // Assert
      expect(player.isBuffering.value).toBe(true)
    })

    it('應該在影片結束時更新狀態', async () => {
      // Arrange
      let onStateChangeCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onStateChangeCallback = config.events?.onStateChange
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      onStateChangeCallback({ data: mockYT.PlayerState.ENDED, target: mockPlayer })
      await nextTick()

      // Assert
      expect(player.isPlaying.value).toBe(false)
      expect(player.isPaused.value).toBe(false)
    })
  })

  describe('錯誤處理', () => {
    it('應該在發生錯誤時設置錯誤狀態', async () => {
      // Arrange
      let onErrorCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onErrorCallback = config.events?.onError
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      onErrorCallback({ data: 2, target: mockPlayer })
      await nextTick()

      // Assert
      expect(player.hasError.value).toBe(true)
      expect(player.errorMessage.value).toBeTruthy()
    })

    it('應該針對不同錯誤碼顯示不同錯誤訊息', async () => {
      // Arrange
      let onErrorCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onErrorCallback = config.events?.onError
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act - 錯誤碼 100 (影片不存在)
      onErrorCallback({ data: 100, target: mockPlayer })
      await nextTick()

      // Assert
      expect(player.hasError.value).toBe(true)
      expect(player.errorMessage.value).toContain('影片')
    })

    it('應該能夠清除錯誤狀態', async () => {
      // Arrange
      let onErrorCallback
      mockYT.Player = vi.fn(function (elementId, config) {
        onErrorCallback = config.events?.onError
        setTimeout(() => config.events?.onReady({ target: mockPlayer }), 0)
        return mockPlayer
      })

      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      onErrorCallback({ data: 2, target: mockPlayer })
      await nextTick()
      expect(player.hasError.value).toBe(true)

      // Act - 載入新影片應該清除錯誤
      player.loadVideo('newVideo123')
      await nextTick()

      // Assert
      expect(player.hasError.value).toBe(false)
      expect(player.errorMessage.value).toBe('')
    })
  })

  describe('時間追蹤', () => {
    it('應該提供 getCurrentTime 方法', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))
      mockPlayer.getCurrentTime.mockReturnValue(42)

      // Act
      const time = player.getCurrentTime()

      // Assert
      expect(time).toBe(42)
      expect(mockPlayer.getCurrentTime).toHaveBeenCalled()
    })

    it('應該提供 getDuration 方法', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))
      mockPlayer.getDuration.mockReturnValue(180)

      // Act
      const duration = player.getDuration()

      // Assert
      expect(duration).toBe(180)
      expect(mockPlayer.getDuration).toHaveBeenCalled()
    })
  })

  describe('清理', () => {
    it('應該提供 destroy 方法來清理播放器', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.destroy()

      // Assert
      expect(mockPlayer.destroy).toHaveBeenCalled()
    })

    it('destroy 後應該重置所有狀態', async () => {
      // Arrange
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.destroy()
      await nextTick()

      // Assert
      expect(player.isReady.value).toBe(false)
    })
  })

  describe('播放清單支援', () => {
    it('應該能夠載入播放清單', async () => {
      // Arrange
      mockPlayer.loadPlaylist = vi.fn()
      const player = useYouTubePlayer('player-container')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 10))

      // Act
      player.loadPlaylist('PLtest123')

      // Assert
      expect(mockPlayer.loadPlaylist).toHaveBeenCalledWith({
        list: 'PLtest123',
        listType: 'playlist'
      })
    })

    it('應該在播放器未就緒時不載入播放清單', () => {
      // Arrange
      mockPlayer.loadPlaylist = vi.fn()
      const player = useYouTubePlayer('player-container')

      // Act
      player.loadPlaylist('PLtest123')

      // Assert
      expect(mockPlayer.loadPlaylist).not.toHaveBeenCalled()
    })
  })
})
