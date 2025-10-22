import { describe, it, expect, beforeEach } from 'vitest'
import { useUrlParser } from '@/composables/useUrlParser'

/**
 * useUrlParser Composable 單元測試
 * 測試 URL 解析邏輯，包含影片 ID 和播放清單 ID 的提取
 */

describe('useUrlParser', () => {
  let parser

  beforeEach(() => {
    parser = useUrlParser()
  })

  describe('初始化', () => {
    it('應該返回正確的響應式屬性', () => {
      expect(parser.videoId).toBeDefined()
      expect(parser.playlistId).toBeDefined()
      expect(parser.isValid).toBeDefined()
      expect(parser.errorMessage).toBeDefined()
    })

    it('初始狀態應該是空的', () => {
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBe('')
    })

    it('應該提供 parseUrl 方法', () => {
      expect(parser.parseUrl).toBeDefined()
      expect(typeof parser.parseUrl).toBe('function')
    })

    it('應該提供 reset 方法', () => {
      expect(parser.reset).toBeDefined()
      expect(typeof parser.reset).toBe('function')
    })
  })

  describe('解析標準 YouTube 網址', () => {
    it('應該正確解析標準影片網址', () => {
      // Arrange
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBe('dQw4w9WgXcQ')
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(true)
      expect(parser.errorMessage.value).toBe('')
    })

    it('應該正確解析短網址', () => {
      // Arrange
      const url = 'https://youtu.be/dQw4w9WgXcQ'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBe('dQw4w9WgXcQ')
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(true)
      expect(parser.errorMessage.value).toBe('')
    })

    it('應該正確解析嵌入網址', () => {
      // Arrange
      const url = 'https://www.youtube.com/embed/dQw4w9WgXcQ'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBe('dQw4w9WgXcQ')
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(true)
      expect(parser.errorMessage.value).toBe('')
    })
  })

  describe('解析播放清單網址', () => {
    it('應該正確解析純播放清單網址', () => {
      // Arrange
      const url = 'https://www.youtube.com/playlist?list=PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.playlistId.value).toBe('PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf')
      expect(parser.isValid.value).toBe(true)
      expect(parser.errorMessage.value).toBe('')
    })

    it('應該正確解析帶有播放清單的影片網址', () => {
      // Arrange
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLtest123'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBe('dQw4w9WgXcQ')
      expect(parser.playlistId.value).toBe('PLtest123')
      expect(parser.isValid.value).toBe(true)
      expect(parser.errorMessage.value).toBe('')
    })

    it('應該優先處理播放清單（當同時存在影片和播放清單時）', () => {
      // Arrange
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLtest123'

      // Act
      parser.parseUrl(url)

      // Assert
      // 根據 spec，播放清單模式下應該播放整個播放清單
      expect(parser.playlistId.value).toBe('PLtest123')
      expect(parser.isValid.value).toBe(true)
    })
  })

  describe('錯誤處理', () => {
    it('應該拒絕非 YouTube 網址', () => {
      // Arrange
      const url = 'https://www.google.com'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBeTruthy()
      expect(parser.errorMessage.value).toContain('YouTube')
    })

    it('應該拒絕無效網址', () => {
      // Arrange
      const url = 'not a valid url'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBeTruthy()
    })

    it('應該處理空字串', () => {
      // Arrange
      const url = ''

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBeTruthy()
    })

    it('應該處理 null 輸入', () => {
      // Arrange
      const url = null

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBeTruthy()
    })

    it('應該處理 undefined 輸入', () => {
      // Arrange
      const url = undefined

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBeTruthy()
    })
  })

  describe('reset 方法', () => {
    it('應該重置所有狀態', () => {
      // Arrange - 先解析一個有效 URL
      parser.parseUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
      expect(parser.videoId.value).toBe('dQw4w9WgXcQ')
      expect(parser.isValid.value).toBe(true)

      // Act
      parser.reset()

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBeNull()
      expect(parser.isValid.value).toBe(false)
      expect(parser.errorMessage.value).toBe('')
    })

    it('應該能夠在 reset 後重新解析', () => {
      // Arrange
      parser.parseUrl('https://www.youtube.com/watch?v=first123')
      parser.reset()

      // Act
      parser.parseUrl('https://www.youtube.com/watch?v=second456')

      // Assert
      expect(parser.videoId.value).toBe('second456')
      expect(parser.isValid.value).toBe(true)
    })
  })

  describe('多次解析', () => {
    it('應該正確處理連續解析不同的 URL', () => {
      // Arrange & Act & Assert
      parser.parseUrl('https://www.youtube.com/watch?v=first123')
      expect(parser.videoId.value).toBe('first123')

      parser.parseUrl('https://www.youtube.com/watch?v=second456')
      expect(parser.videoId.value).toBe('second456')

      parser.parseUrl('https://youtu.be/third789')
      expect(parser.videoId.value).toBe('third789')
    })

    it('應該在解析無效 URL 後保持錯誤狀態', () => {
      // Arrange & Act
      parser.parseUrl('https://www.youtube.com/watch?v=valid123')
      expect(parser.isValid.value).toBe(true)

      parser.parseUrl('invalid url')

      // Assert
      expect(parser.isValid.value).toBe(false)
      expect(parser.videoId.value).toBeNull()
      expect(parser.errorMessage.value).toBeTruthy()
    })
  })

  describe('邊界情況', () => {
    it('應該處理帶有額外參數的 URL', () => {
      // Arrange
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s&feature=share'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBe('dQw4w9WgXcQ')
      expect(parser.isValid.value).toBe(true)
    })

    it('應該處理不同的域名變體', () => {
      // Arrange & Act & Assert
      parser.parseUrl('https://youtube.com/watch?v=test123')
      expect(parser.videoId.value).toBe('test123')
      expect(parser.isValid.value).toBe(true)

      parser.parseUrl('https://www.youtube.com/watch?v=test456')
      expect(parser.videoId.value).toBe('test456')
      expect(parser.isValid.value).toBe(true)

      parser.parseUrl('https://m.youtube.com/watch?v=test789')
      expect(parser.videoId.value).toBe('test789')
      expect(parser.isValid.value).toBe(true)
    })

    it('應該處理沒有影片 ID 的播放清單', () => {
      // Arrange
      const url = 'https://www.youtube.com/playlist?list=PLtest123'

      // Act
      parser.parseUrl(url)

      // Assert
      expect(parser.videoId.value).toBeNull()
      expect(parser.playlistId.value).toBe('PLtest123')
      expect(parser.isValid.value).toBe(true)
    })
  })
})
