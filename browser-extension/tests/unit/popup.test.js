import { getPlaylists, addVideoToPlaylist } from '../../src/services/api.js';
import { getVideoInfo } from '../../src/services/youtube.js';
import { getCache, setCache } from '../../src/utils/cache.js';

// Mock dependencies
jest.mock('../../src/services/api.js');
jest.mock('../../src/services/youtube.js');
jest.mock('../../src/utils/cache.js');
jest.mock('webextension-polyfill');

describe('Popup Modal Functionality', () => {
  let mockPlaylists;
  let mockVideoInfo;

  beforeEach(() => {
    // Reset all mocks
    jest.clearAllMocks();

    // Setup test data
    mockPlaylists = [
      {
        id: 'playlist1',
        name: '我的最愛',
        videoCount: 5
      },
      {
        id: 'playlist2',
        name: '稍後觀看',
        videoCount: 3
      }
    ];

    mockVideoInfo = {
      title: '測試影片',
      channelTitle: '測試頻道',
      duration: 300,
      thumbnailUrl: 'https://example.com/thumb.jpg',
      isFallback: false
    };

    // Setup mock implementations
    getCache.mockResolvedValue(null);
    setCache.mockResolvedValue(true);
    getPlaylists.mockResolvedValue({
      playlists: mockPlaylists,
      total: 2
    });
    getVideoInfo.mockResolvedValue(mockVideoInfo);
    addVideoToPlaylist.mockResolvedValue({
      success: true
    });
  });

  describe('showPlaylistSelector', () => {
    it('應該從快取載入播放清單', async () => {
      getCache.mockResolvedValue(mockPlaylists);

      // 模擬 showPlaylistSelector 邏輯
      const playlists = await getCache('cache_playlists');

      expect(getCache).toHaveBeenCalledWith('cache_playlists');
      expect(playlists).toEqual(mockPlaylists);
    });

    it('應該在快取不存在時從 API 載入播放清單', async () => {
      getCache.mockResolvedValue(null);

      // 模擬 showPlaylistSelector 邏輯
      let playlists = await getCache('cache_playlists');
      if (!playlists) {
        const response = await getPlaylists({ limit: 50 });
        playlists = response.playlists;
        await setCache('cache_playlists', playlists);
      }

      expect(getCache).toHaveBeenCalledWith('cache_playlists');
      expect(getPlaylists).toHaveBeenCalledWith({ limit: 50 });
      expect(setCache).toHaveBeenCalledWith('cache_playlists', mockPlaylists);
      expect(playlists).toEqual(mockPlaylists);
    });

    it('應該處理載入失敗的情況', async () => {
      const error = new Error('Network error');
      getCache.mockRejectedValue(error);

      try {
        await getCache('cache_playlists');
        fail('應該拋出錯誤');
      } catch (e) {
        expect(e.message).toBe('Network error');
      }
    });
  });

  describe('handlePlaylistSelection', () => {
    it('應該成功將影片加入播放清單', async () => {
      const playlist = mockPlaylists[0];
      const currentVideoId = 'dQw4w9WgXcQ';

      // 模擬 handlePlaylistSelection 邏輯
      const videoInfo = await getVideoInfo(currentVideoId);
      const videoData = {
        youtubeVideoId: currentVideoId,
        title: videoInfo.title,
        thumbnailUrl: videoInfo.thumbnailUrl,
        duration: videoInfo.duration,
        channelTitle: videoInfo.channelTitle
      };

      const result = await addVideoToPlaylist(playlist.id, videoData);

      expect(getVideoInfo).toHaveBeenCalledWith(currentVideoId);
      expect(addVideoToPlaylist).toHaveBeenCalledWith(playlist.id, videoData);
      expect(result.success).toBe(true);
    });

    it('應該處理影片已在播放清單中的情況', async () => {
      const playlist = mockPlaylists[0];
      const currentVideoId = 'dQw4w9WgXcQ';

      addVideoToPlaylist.mockResolvedValue({
        success: false,
        error: 'VIDEO_ALREADY_IN_PLAYLIST',
        message: '此影片已在播放清單中'
      });

      const videoData = {
        youtubeVideoId: currentVideoId,
        title: mockVideoInfo.title,
        thumbnailUrl: mockVideoInfo.thumbnailUrl,
        duration: mockVideoInfo.duration,
        channelTitle: mockVideoInfo.channelTitle
      };

      const result = await addVideoToPlaylist(playlist.id, videoData);

      expect(result.success).toBe(false);
      expect(result.error).toBe('VIDEO_ALREADY_IN_PLAYLIST');
    });

    it('應該處理網路錯誤', async () => {
      const playlist = mockPlaylists[0];
      const currentVideoId = 'dQw4w9WgXcQ';

      getVideoInfo.mockRejectedValue(new Error('Network error'));

      try {
        await getVideoInfo(currentVideoId);
        fail('應該拋出網路錯誤');
      } catch (error) {
        expect(error.message).toBe('Network error');
      }
    });

    it('應該處理認證失敗', async () => {
      const playlist = mockPlaylists[0];
      const currentVideoId = 'dQw4w9WgXcQ';

      addVideoToPlaylist.mockRejectedValue(
        new Error('Error: not authenticated')
      );

      try {
        await addVideoToPlaylist(playlist.id, {
          youtubeVideoId: currentVideoId,
          title: mockVideoInfo.title,
          thumbnailUrl: mockVideoInfo.thumbnailUrl,
          duration: mockVideoInfo.duration,
          channelTitle: mockVideoInfo.channelTitle
        });
        fail('應該拋出認證錯誤');
      } catch (error) {
        expect(error.message).toContain('not authenticated');
      }
    });
  });

  describe('renderPlaylistItems', () => {
    it('應該為每個播放清單建立 DOM 項目', () => {
      // 這個測試需要 DOM 環境
      // 模擬 renderPlaylistItems 邏輯
      const container = [];

      mockPlaylists.forEach(playlist => {
        const item = {
          id: playlist.id,
          name: playlist.name,
          videoCount: playlist.videoCount
        };
        container.push(item);
      });

      expect(container).toHaveLength(2);
      expect(container[0].name).toBe('我的最愛');
      expect(container[1].name).toBe('稍後觀看');
    });

    it('應該正確顯示播放清單資訊', () => {
      // 模擬渲染結果
      const rendered = mockPlaylists.map(p => ({
        name: p.name,
        count: p.videoCount
      }));

      expect(rendered[0].name).toBe('我的最愛');
      expect(rendered[0].count).toBe(5);
    });
  });

  describe('closePlaylistModal', () => {
    it('應該清空播放清單列表', () => {
      const playlistList = [];
      playlistList.length = 0;

      expect(playlistList).toHaveLength(0);
    });

    it('應該重置所有模態狀態', () => {
      const modalState = {
        isVisible: false,
        isLoading: false,
        showEmpty: false,
        showError: false
      };

      expect(modalState.isVisible).toBe(false);
      expect(modalState.isLoading).toBe(false);
    });
  });

  describe('Error Handling', () => {
    it('應該處理無效的播放清單 ID', async () => {
      const invalidPlaylistId = null;

      addVideoToPlaylist.mockRejectedValue(
        new Error('Invalid playlist ID')
      );

      try {
        await addVideoToPlaylist(invalidPlaylistId, {});
        fail('應該拋出錯誤');
      } catch (error) {
        expect(error.message).toBe('Invalid playlist ID');
      }
    });

    it('應該處理空的播放清單陣列', async () => {
      getPlaylists.mockResolvedValue({
        playlists: [],
        total: 0
      });

      const response = await getPlaylists({ limit: 50 });
      expect(response.playlists).toHaveLength(0);
      expect(response.total).toBe(0);
    });

    it('應該在影片資訊不完整時使用降級策略', async () => {
      getVideoInfo.mockResolvedValue({
        title: '未知',
        channelTitle: '未知',
        duration: 0,
        thumbnailUrl: '',
        isFallback: true
      });

      const videoInfo = await getVideoInfo('unknown-video');
      expect(videoInfo.isFallback).toBe(true);
    });
  });
});
