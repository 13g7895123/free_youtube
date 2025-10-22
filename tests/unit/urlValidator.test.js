import { describe, it, expect } from 'vitest'
import { isValidYouTubeUrl, extractVideoId, extractPlaylistId } from '@/utils/urlValidator'

describe('urlValidator', () => {
  describe('isValidYouTubeUrl', () => {
    it('應該驗證標準 YouTube 網址', () => {
      expect(isValidYouTubeUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')).toBe(true)
      expect(isValidYouTubeUrl('https://youtube.com/watch?v=dQw4w9WgXcQ')).toBe(true)
    })

    it('應該驗證短網址', () => {
      expect(isValidYouTubeUrl('https://youtu.be/dQw4w9WgXcQ')).toBe(true)
    })

    it('應該驗證嵌入網址', () => {
      expect(isValidYouTubeUrl('https://www.youtube.com/embed/dQw4w9WgXcQ')).toBe(true)
    })

    it('應該驗證播放清單網址', () => {
      expect(isValidYouTubeUrl('https://www.youtube.com/playlist?list=PLtest123')).toBe(true)
    })

    it('應該拒絕無效網址', () => {
      expect(isValidYouTubeUrl('https://www.google.com')).toBe(false)
      expect(isValidYouTubeUrl('not a url')).toBe(false)
      expect(isValidYouTubeUrl('')).toBe(false)
    })
  })

  describe('extractVideoId', () => {
    it('應該從標準網址提取影片 ID', () => {
      expect(extractVideoId('https://www.youtube.com/watch?v=dQw4w9WgXcQ')).toBe('dQw4w9WgXcQ')
    })

    it('應該從短網址提取影片 ID', () => {
      expect(extractVideoId('https://youtu.be/dQw4w9WgXcQ')).toBe('dQw4w9WgXcQ')
    })

    it('應該從嵌入網址提取影片 ID', () => {
      expect(extractVideoId('https://www.youtube.com/embed/dQw4w9WgXcQ')).toBe('dQw4w9WgXcQ')
    })

    it('應該對無效網址返回 null', () => {
      expect(extractVideoId('https://www.google.com')).toBeNull()
      expect(extractVideoId('invalid')).toBeNull()
    })
  })

  describe('extractPlaylistId', () => {
    it('應該從播放清單網址提取播放清單 ID', () => {
      const url = 'https://www.youtube.com/playlist?list=PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf'
      expect(extractPlaylistId(url)).toBe('PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf')
    })

    it('應該從影片網址中提取播放清單 ID', () => {
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLtest123'
      expect(extractPlaylistId(url)).toBe('PLtest123')
    })

    it('應該對沒有播放清單的網址返回 null', () => {
      expect(extractPlaylistId('https://www.youtube.com/watch?v=dQw4w9WgXcQ')).toBeNull()
    })

    it('應該對無效網址返回 null', () => {
      expect(extractPlaylistId('invalid url')).toBeNull()
    })
  })
})
