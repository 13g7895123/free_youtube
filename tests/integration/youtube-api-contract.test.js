import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

/**
 * YouTube IFrame API 合約測試
 * 驗證與 YouTube IFrame Player API 的互動符合規範
 *
 * 參考文件: https://developers.google.com/youtube/iframe_api_reference
 */

describe('YouTube IFrame API Contract', () => {
  let mockPlayer
  let onReadyCallback
  let onStateChangeCallback
  let onErrorCallback

  beforeEach(() => {
    // 模擬 YouTube IFrame API
    global.YT = {
      Player: vi.fn(function (elementId, config) {
        mockPlayer = {
          playVideo: vi.fn(),
          pauseVideo: vi.fn(),
          stopVideo: vi.fn(),
          seekTo: vi.fn(),
          loadVideoById: vi.fn(),
          cueVideoById: vi.fn(),
          getPlayerState: vi.fn(),
          getCurrentTime: vi.fn(),
          getDuration: vi.fn(),
          getVolume: vi.fn(),
          setVolume: vi.fn(),
          mute: vi.fn(),
          unMute: vi.fn(),
          isMuted: vi.fn(),
          destroy: vi.fn()
        }

        // 保存回調函數以便測試
        if (config.events) {
          onReadyCallback = config.events.onReady
          onStateChangeCallback = config.events.onStateChange
          onErrorCallback = config.events.onError
        }

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
  })

  afterEach(() => {
    delete global.YT
    vi.clearAllMocks()
  })

  describe('T025: onReady Event Contract', () => {
    it('應該在播放器準備完成時觸發 onReady 回調', () => {
      // Arrange
      const onReady = vi.fn()

      // Act - 建立播放器
      new global.YT.Player('player', {
        events: { onReady }
      })

      // 模擬 YouTube API 觸發 onReady
      const event = { target: mockPlayer }
      onReadyCallback(event)

      // Assert
      expect(onReady).toHaveBeenCalledWith(event)
      expect(onReady).toHaveBeenCalledTimes(1)
    })

    it('onReady 事件應該包含有效的播放器實例', () => {
      // Arrange
      const onReady = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onReady }
      })

      const event = { target: mockPlayer }
      onReadyCallback(event)

      // Assert
      expect(event.target).toBeDefined()
      expect(event.target.playVideo).toBeDefined()
      expect(typeof event.target.playVideo).toBe('function')
    })

    it('應該能夠在 onReady 後呼叫播放器方法', () => {
      // Arrange
      let playerInstance

      // Act
      new global.YT.Player('player', {
        events: {
          onReady: event => {
            playerInstance = event.target
            playerInstance.playVideo()
          }
        }
      })

      onReadyCallback({ target: mockPlayer })

      // Assert
      expect(mockPlayer.playVideo).toHaveBeenCalled()
    })
  })

  describe('T026: onStateChange Event Contract', () => {
    it('應該在播放狀態改變時觸發 onStateChange 回調', () => {
      // Arrange
      const onStateChange = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onStateChange }
      })

      const event = { data: global.YT.PlayerState.PLAYING, target: mockPlayer }
      onStateChangeCallback(event)

      // Assert
      expect(onStateChange).toHaveBeenCalledWith(event)
      expect(onStateChange).toHaveBeenCalledTimes(1)
    })

    it('應該正確識別 PLAYING 狀態 (1)', () => {
      // Arrange
      const onStateChange = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onStateChange }
      })

      const event = { data: global.YT.PlayerState.PLAYING, target: mockPlayer }
      onStateChangeCallback(event)

      // Assert
      const receivedEvent = onStateChange.mock.calls[0][0]
      expect(receivedEvent.data).toBe(1)
      expect(receivedEvent.data).toBe(global.YT.PlayerState.PLAYING)
    })

    it('應該正確識別 PAUSED 狀態 (2)', () => {
      // Arrange
      const onStateChange = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onStateChange }
      })

      const event = { data: global.YT.PlayerState.PAUSED, target: mockPlayer }
      onStateChangeCallback(event)

      // Assert
      const receivedEvent = onStateChange.mock.calls[0][0]
      expect(receivedEvent.data).toBe(2)
      expect(receivedEvent.data).toBe(global.YT.PlayerState.PAUSED)
    })

    it('應該正確識別 ENDED 狀態 (0)', () => {
      // Arrange
      const onStateChange = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onStateChange }
      })

      const event = { data: global.YT.PlayerState.ENDED, target: mockPlayer }
      onStateChangeCallback(event)

      // Assert
      const receivedEvent = onStateChange.mock.calls[0][0]
      expect(receivedEvent.data).toBe(0)
      expect(receivedEvent.data).toBe(global.YT.PlayerState.ENDED)
    })

    it('應該正確識別 BUFFERING 狀態 (3)', () => {
      // Arrange
      const onStateChange = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onStateChange }
      })

      const event = { data: global.YT.PlayerState.BUFFERING, target: mockPlayer }
      onStateChangeCallback(event)

      // Assert
      const receivedEvent = onStateChange.mock.calls[0][0]
      expect(receivedEvent.data).toBe(3)
      expect(receivedEvent.data).toBe(global.YT.PlayerState.BUFFERING)
    })

    it('應該正確識別 CUED 狀態 (5)', () => {
      // Arrange
      const onStateChange = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onStateChange }
      })

      const event = { data: global.YT.PlayerState.CUED, target: mockPlayer }
      onStateChangeCallback(event)

      // Assert
      const receivedEvent = onStateChange.mock.calls[0][0]
      expect(receivedEvent.data).toBe(5)
      expect(receivedEvent.data).toBe(global.YT.PlayerState.CUED)
    })
  })

  describe('T027: onError Event Contract', () => {
    it('應該在發生錯誤時觸發 onError 回調', () => {
      // Arrange
      const onError = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onError }
      })

      const event = { data: 2, target: mockPlayer }
      onErrorCallback(event)

      // Assert
      expect(onError).toHaveBeenCalledWith(event)
      expect(onError).toHaveBeenCalledTimes(1)
    })

    it('應該處理錯誤碼 2 (無效的影片 ID)', () => {
      // Arrange
      const onError = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onError }
      })

      const event = { data: 2, target: mockPlayer }
      onErrorCallback(event)

      // Assert
      const receivedEvent = onError.mock.calls[0][0]
      expect(receivedEvent.data).toBe(2)
    })

    it('應該處理錯誤碼 5 (HTML5 播放器錯誤)', () => {
      // Arrange
      const onError = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onError }
      })

      const event = { data: 5, target: mockPlayer }
      onErrorCallback(event)

      // Assert
      const receivedEvent = onError.mock.calls[0][0]
      expect(receivedEvent.data).toBe(5)
    })

    it('應該處理錯誤碼 100 (影片不存在或已被移除)', () => {
      // Arrange
      const onError = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onError }
      })

      const event = { data: 100, target: mockPlayer }
      onErrorCallback(event)

      // Assert
      const receivedEvent = onError.mock.calls[0][0]
      expect(receivedEvent.data).toBe(100)
    })

    it('應該處理錯誤碼 101/150 (嵌入受限)', () => {
      // Arrange
      const onError = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onError }
      })

      // Test error code 101
      let event = { data: 101, target: mockPlayer }
      onErrorCallback(event)

      expect(onError.mock.calls[0][0].data).toBe(101)

      // Test error code 150
      event = { data: 150, target: mockPlayer }
      onErrorCallback(event)

      expect(onError.mock.calls[1][0].data).toBe(150)
    })

    it('onError 事件應該包含有效的播放器實例', () => {
      // Arrange
      const onError = vi.fn()

      // Act
      new global.YT.Player('player', {
        events: { onError }
      })

      const event = { data: 2, target: mockPlayer }
      onErrorCallback(event)

      // Assert
      const receivedEvent = onError.mock.calls[0][0]
      expect(receivedEvent.target).toBeDefined()
      expect(receivedEvent.target).toBe(mockPlayer)
    })
  })
})
