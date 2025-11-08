import { parseYouTubeURL, isYouTubeURL, isYouTubeVideoURL } from '../../src/utils/url-parser';

describe('YouTube URL Parser', () => {
  describe('parseYouTubeURL', () => {
    test('解析標準 youtube.com URL', () => {
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('解析 youtube.com URL (不含 www)', () => {
      const url = 'https://youtube.com/watch?v=dQw4w9WgXcQ';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('解析 youtu.be 短網址', () => {
      const url = 'https://youtu.be/dQw4w9WgXcQ';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('解析 m.youtube.com 行動版網址', () => {
      const url = 'https://m.youtube.com/watch?v=dQw4w9WgXcQ';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('解析包含播放清單的 URL，僅提取影片 ID', () => {
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('解析包含時間參數的 URL', () => {
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('解析包含多個參數的 URL', () => {
      const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLtest&index=1&t=10s';
      expect(parseYouTubeURL(url)).toBe('dQw4w9WgXcQ');
    });

    test('非 YouTube URL 回傳 null', () => {
      const url = 'https://example.com';
      expect(parseYouTubeURL(url)).toBeNull();
    });

    test('YouTube 首頁 URL 回傳 null', () => {
      const url = 'https://www.youtube.com/';
      expect(parseYouTubeURL(url)).toBeNull();
    });

    test('YouTube 搜尋頁面回傳 null', () => {
      const url = 'https://www.youtube.com/results?search_query=test';
      expect(parseYouTubeURL(url)).toBeNull();
    });

    test('無效的 URL 回傳 null', () => {
      const url = 'not-a-valid-url';
      expect(parseYouTubeURL(url)).toBeNull();
    });

    test('null 輸入回傳 null', () => {
      expect(parseYouTubeURL(null)).toBeNull();
    });

    test('undefined 輸入回傳 null', () => {
      expect(parseYouTubeURL(undefined)).toBeNull();
    });

    test('空字串輸入回傳 null', () => {
      expect(parseYouTubeURL('')).toBeNull();
    });

    test('youtu.be 沒有影片 ID 回傳 null', () => {
      const url = 'https://youtu.be/';
      expect(parseYouTubeURL(url)).toBeNull();
    });
  });

  describe('isYouTubeURL', () => {
    test('youtube.com 是 YouTube URL', () => {
      expect(isYouTubeURL('https://www.youtube.com/watch?v=test')).toBe(true);
    });

    test('youtu.be 是 YouTube URL', () => {
      expect(isYouTubeURL('https://youtu.be/test')).toBe(true);
    });

    test('m.youtube.com 是 YouTube URL', () => {
      expect(isYouTubeURL('https://m.youtube.com/watch?v=test')).toBe(true);
    });

    test('example.com 不是 YouTube URL', () => {
      expect(isYouTubeURL('https://example.com')).toBe(false);
    });

    test('無效 URL 回傳 false', () => {
      expect(isYouTubeURL('not-a-url')).toBe(false);
    });

    test('null 回傳 false', () => {
      expect(isYouTubeURL(null)).toBe(false);
    });
  });

  describe('isYouTubeVideoURL', () => {
    test('有效的影片 URL 回傳 true', () => {
      expect(isYouTubeVideoURL('https://www.youtube.com/watch?v=dQw4w9WgXcQ')).toBe(true);
    });

    test('YouTube 首頁回傳 false', () => {
      expect(isYouTubeVideoURL('https://www.youtube.com/')).toBe(false);
    });

    test('沒有影片 ID 回傳 false', () => {
      expect(isYouTubeVideoURL('https://www.youtube.com/watch')).toBe(false);
    });

    test('非 YouTube URL 回傳 false', () => {
      expect(isYouTubeVideoURL('https://example.com')).toBe(false);
    });
  });
});
