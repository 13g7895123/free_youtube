// Mock config
jest.mock('../../src/utils/config', () => ({
  config: {
    youtubeApiKey: 'test_api_key'
  }
}));

// Mock fetch
global.fetch = jest.fn();

// Import after mocking
import { getVideoInfo, formatDuration, getBatchVideoInfo } from '../../src/services/youtube';

describe('YouTube Service', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getVideoInfo', () => {
    test('成功取得影片資訊', async () => {
      const mockResponse = {
        items: [
          {
            snippet: {
              title: 'Test Video',
              description: 'Test Description',
              channelTitle: 'Test Channel',
              publishedAt: '2025-01-01T00:00:00Z',
              thumbnails: {
                medium: { url: 'https://example.com/medium.jpg' },
                high: { url: 'https://example.com/high.jpg' }
              }
            },
            contentDetails: {
              duration: 'PT5M30S'
            }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getVideoInfo('test_video_id');

      expect(result.title).toBe('Test Video');
      expect(result.description).toBe('Test Description');
      expect(result.channelTitle).toBe('Test Channel');
      expect(result.thumbnailUrl).toBe('https://example.com/medium.jpg');
      expect(result.duration).toBe(330); // 5 分 30 秒 = 330 秒
      expect(result.isFallback).toBeUndefined();
    });

    test('使用 medium 縮圖', async () => {
      const mockResponse = {
        items: [
          {
            snippet: {
              title: 'Test',
              thumbnails: {
                medium: { url: 'https://example.com/medium.jpg' },
                high: { url: 'https://example.com/high.jpg' }
              }
            },
            contentDetails: {
              duration: 'PT1M'
            }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getVideoInfo('test_video_id');
      expect(result.thumbnailUrl).toBe('https://example.com/medium.jpg');
    });

    test('沒有 medium 時使用 high 縮圖', async () => {
      const mockResponse = {
        items: [
          {
            snippet: {
              title: 'Test',
              thumbnails: {
                high: { url: 'https://example.com/high.jpg' },
                default: { url: 'https://example.com/default.jpg' }
              }
            },
            contentDetails: {
              duration: 'PT1M'
            }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getVideoInfo('test_video_id');
      expect(result.thumbnailUrl).toBe('https://example.com/high.jpg');
    });

    test('YouTube API 配額不足時使用降級策略', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 403,
        statusText: 'Forbidden'
      });

      const result = await getVideoInfo('test_video_id');

      expect(result.title).toBeNull();
      expect(result.duration).toBeNull();
      expect(result.thumbnailUrl).toBe('https://img.youtube.com/vi/test_video_id/mqdefault.jpg');
      expect(result.isFallback).toBe(true);
    });

    test('影片不存在時使用降級策略', async () => {
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ items: [] })
      });

      // 由於找不到影片，會觸發錯誤並使用降級策略
      await expect(getVideoInfo('invalid_video_id')).rejects.toThrow('Video not found');
    });

    test('網路錯誤時拋出錯誤', async () => {
      global.fetch.mockRejectedValue(new Error('Network error'));

      await expect(getVideoInfo('test_video_id')).rejects.toThrow('Network error');
    });
  });

  describe('formatDuration', () => {
    test('格式化秒數為 MM:SS', () => {
      expect(formatDuration(330)).toBe('5:30');
      expect(formatDuration(65)).toBe('1:05');
      expect(formatDuration(10)).toBe('0:10');
    });

    test('格式化秒數為 HH:MM:SS', () => {
      expect(formatDuration(3665)).toBe('1:01:05');
      expect(formatDuration(7200)).toBe('2:00:00');
    });

    test('處理 0 秒', () => {
      expect(formatDuration(0)).toBe('0:00');
      expect(formatDuration(null)).toBe('0:00');
      expect(formatDuration(undefined)).toBe('0:00');
    });
  });

  describe('parseDuration (via getVideoInfo)', () => {
    test('解析 PT5M30S (5 分 30 秒)', async () => {
      const mockResponse = {
        items: [
          {
            snippet: { title: 'Test', thumbnails: { medium: { url: 'http://example.com/img.jpg' } } },
            contentDetails: { duration: 'PT5M30S' }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getVideoInfo('test_id');
      expect(result.duration).toBe(330);
    });

    test('解析 PT1H2M10S (1 小時 2 分 10 秒)', async () => {
      const mockResponse = {
        items: [
          {
            snippet: { title: 'Test', thumbnails: { medium: { url: 'http://example.com/img.jpg' } } },
            contentDetails: { duration: 'PT1H2M10S' }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getVideoInfo('test_id');
      expect(result.duration).toBe(3730); // 1*3600 + 2*60 + 10
    });

    test('解析 PT45S (45 秒)', async () => {
      const mockResponse = {
        items: [
          {
            snippet: { title: 'Test', thumbnails: { medium: { url: 'http://example.com/img.jpg' } } },
            contentDetails: { duration: 'PT45S' }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getVideoInfo('test_id');
      expect(result.duration).toBe(45);
    });
  });

  describe('getBatchVideoInfo', () => {
    test('批次取得多個影片資訊', async () => {
      const mockResponse = {
        items: [
          {
            snippet: {
              title: 'Video 1',
              thumbnails: { medium: { url: 'https://example.com/1.jpg' } }
            },
            contentDetails: { duration: 'PT5M' }
          },
          {
            snippet: {
              title: 'Video 2',
              thumbnails: { medium: { url: 'https://example.com/2.jpg' } }
            },
            contentDetails: { duration: 'PT10M' }
          }
        ]
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await getBatchVideoInfo(['id1', 'id2']);

      expect(result.length).toBe(2);
      expect(result[0].title).toBe('Video 1');
      expect(result[1].title).toBe('Video 2');
      // 檢查 URL 包含兩個 ID (可能被 URL 編碼為 id1%2Cid2)
      const callUrl = global.fetch.mock.calls[0][0];
      expect(callUrl).toContain('id1');
      expect(callUrl).toContain('id2');
    });

    test('空陣列回傳空結果', async () => {
      const result = await getBatchVideoInfo([]);
      expect(result).toEqual([]);
      expect(global.fetch).not.toHaveBeenCalled();
    });

    test('超過 50 個影片時拋出錯誤', async () => {
      const videoIds = Array(51).fill('id');
      await expect(getBatchVideoInfo(videoIds)).rejects.toThrow('Maximum 50 video IDs');
    });

    test('配額不足時使用降級策略', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 403
      });

      const result = await getBatchVideoInfo(['id1', 'id2']);

      expect(result.length).toBe(2);
      expect(result[0].isFallback).toBe(true);
      expect(result[1].isFallback).toBe(true);
    });
  });
});
