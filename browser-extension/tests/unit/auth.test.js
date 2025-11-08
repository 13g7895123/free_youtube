// Mock storage data
let mockStorageData = {};

// Mock webextension-polyfill
jest.mock('webextension-polyfill', () => ({
  default: {
    storage: {
      local: {
        get: jest.fn((key) => {
          return Promise.resolve({ [key]: mockStorageData[key] });
        }),
        set: jest.fn((items) => {
          Object.assign(mockStorageData, items);
          return Promise.resolve();
        }),
        remove: jest.fn((key) => {
          delete mockStorageData[key];
          return Promise.resolve();
        })
      }
    },
    identity: {
      launchWebAuthFlow: jest.fn()
    }
  }
}));

// Mock config
jest.mock('../../src/utils/config', () => ({
  config: {
    lineChannelId: 'test_channel_id',
    lineRedirectUri: 'https://example.com/callback',
    lineAuthUrl: 'https://access.line.me/oauth2/v2.1/authorize',
    backendUrl: 'https://api.example.com'
  }
}));

// Mock api.js
jest.mock('../../src/services/api', () => ({
  callBackendAPI: jest.fn()
}));

// Mock Web Crypto API
global.crypto = {
  subtle: {
    generateKey: jest.fn(() => Promise.resolve({ type: 'secret' })),
    encrypt: jest.fn(() => Promise.resolve(new Uint8Array([1, 2, 3]))),
    decrypt: jest.fn(() => Promise.resolve(new Uint8Array([72, 101, 108, 108, 111]))),
    exportKey: jest.fn(() => Promise.resolve({ kty: 'oct', k: 'test' })),
    importKey: jest.fn(() => Promise.resolve({ type: 'secret' }))
  },
  getRandomValues: jest.fn((arr) => {
    for (let i = 0; i < arr.length; i++) {
      arr[i] = Math.floor(Math.random() * 256);
    }
    return arr;
  })
};

// Mock btoa/atob
global.btoa = (str) => Buffer.from(str, 'binary').toString('base64');
global.atob = (b64) => Buffer.from(b64, 'base64').toString('binary');

// Import after mocking
import browser from 'webextension-polyfill';
import {
  loginWithLINE,
  extractAuthorizationCode,
  logout,
  isAuthenticated,
  getCurrentUser
} from '../../src/services/auth';
import { callBackendAPI } from '../../src/services/api';

describe('Auth Service', () => {
  beforeEach(() => {
    // 清空 mock storage
    mockStorageData = {};
    jest.clearAllMocks();
  });

  describe('extractAuthorizationCode', () => {
    test('應該從 redirect URL 提取 authorization code', () => {
      const redirectUrl = 'https://example.com/callback?code=test_auth_code&state=abc123';
      const code = extractAuthorizationCode(redirectUrl);
      expect(code).toBe('test_auth_code');
    });

    test('沒有 code 參數時應該回傳 null', () => {
      const redirectUrl = 'https://example.com/callback?error=access_denied';
      const code = extractAuthorizationCode(redirectUrl);
      expect(code).toBeNull();
    });

    test('無效 URL 應該回傳 null', () => {
      const redirectUrl = 'not-a-valid-url';
      const code = extractAuthorizationCode(redirectUrl);
      expect(code).toBeNull();
    });
  });

  describe('loginWithLINE', () => {
    test('成功登入應該儲存認證資料並回傳使用者資訊', async () => {
      // Mock LINE OAuth flow
      const mockRedirectUrl = 'https://example.com/callback?code=test_code&state=abc123';
      browser.identity.launchWebAuthFlow.mockResolvedValue(mockRedirectUrl);

      // Mock backend API response
      const mockAuthData = {
        accessToken: 'test_access_token',
        refreshToken: 'test_refresh_token',
        expiresIn: 3600,
        user: {
          lineUserId: 'U1234567890',
          displayName: 'Test User',
          profilePictureUrl: 'https://example.com/profile.jpg'
        },
        isNewUser: false
      };
      callBackendAPI.mockResolvedValue(mockAuthData);

      // 執行登入
      const result = await loginWithLINE();

      // 驗證結果
      expect(result.success).toBe(true);
      expect(result.user.lineUserId).toBe('U1234567890');
      expect(result.isNewUser).toBe(false);

      // 驗證 launchWebAuthFlow 被呼叫
      expect(browser.identity.launchWebAuthFlow).toHaveBeenCalledWith({
        url: expect.stringContaining('https://access.line.me/oauth2/v2.1/authorize'),
        interactive: true
      });

      // 驗證後端 API 被呼叫
      expect(callBackendAPI).toHaveBeenCalledWith('/auth/line/callback', {
        method: 'POST',
        body: {
          code: 'test_code',
          redirectUri: 'https://example.com/callback'
        }
      });

      // 驗證認證資料被儲存
      expect(browser.storage.local.set).toHaveBeenCalled();
    });

    test('使用者取消授權應該拋出錯誤', async () => {
      browser.identity.launchWebAuthFlow.mockRejectedValue(
        new Error('User cancelled authorization')
      );

      await expect(loginWithLINE()).rejects.toThrow();
    });

    test('無法提取 authorization code 應該拋出錯誤', async () => {
      // Mock redirect URL 沒有 code 參數
      const mockRedirectUrl = 'https://example.com/callback?error=access_denied';
      browser.identity.launchWebAuthFlow.mockResolvedValue(mockRedirectUrl);

      await expect(loginWithLINE()).rejects.toThrow(
        'Failed to extract authorization code from redirect URL'
      );
    });

    test('後端 API 失敗應該拋出錯誤', async () => {
      const mockRedirectUrl = 'https://example.com/callback?code=test_code&state=abc123';
      browser.identity.launchWebAuthFlow.mockResolvedValue(mockRedirectUrl);

      callBackendAPI.mockRejectedValue(new Error('Network error'));

      await expect(loginWithLINE()).rejects.toThrow('Network error');
    });
  });

  describe('logout', () => {
    test('成功登出應該呼叫後端 API 並清除本地資料', async () => {
      // 設定已登入狀態
      mockStorageData.auth_data = {
        accessToken: { value: 'encrypted', expiresAt: Date.now() + 3600000 },
        refreshToken: { value: 'encrypted', expiresAt: Date.now() + 604800000 },
        user: { lineUserId: 'U1234567890' }
      };

      callBackendAPI.mockResolvedValue({ success: true });

      await logout();

      // 驗證後端 API 被呼叫
      expect(callBackendAPI).toHaveBeenCalledWith('/auth/logout', {
        method: 'POST',
        body: {
          refreshToken: 'encrypted'
        }
      });

      // 驗證本地資料被清除
      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });

    test('後端 API 失敗時仍應清除本地資料', async () => {
      mockStorageData.auth_data = {
        refreshToken: { value: 'encrypted' }
      };

      callBackendAPI.mockRejectedValue(new Error('Server error'));

      await expect(logout()).rejects.toThrow();

      // 驗證本地資料仍被清除
      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });
  });

  describe('isAuthenticated', () => {
    test('有 access token 時應該回傳 true', async () => {
      mockStorageData.auth_data = {
        accessToken: { value: 'encrypted', expiresAt: Date.now() + 3600000 }
      };

      const result = await isAuthenticated();
      expect(result).toBe(true);
    });

    test('沒有認證資料時應該回傳 false', async () => {
      const result = await isAuthenticated();
      expect(result).toBe(false);
    });

    test('認證資料不完整時應該回傳 false', async () => {
      mockStorageData.auth_data = {
        user: { lineUserId: 'U1234567890' }
        // 缺少 accessToken
      };

      const result = await isAuthenticated();
      expect(result).toBe(false);
    });
  });

  describe('getCurrentUser', () => {
    test('應該回傳當前使用者資訊', async () => {
      const mockUser = {
        lineUserId: 'U1234567890',
        displayName: 'Test User',
        profilePictureUrl: 'https://example.com/profile.jpg'
      };

      mockStorageData.auth_data = {
        user: mockUser
      };

      const user = await getCurrentUser();
      expect(user).toEqual(mockUser);
    });

    test('沒有認證資料時應該回傳 null', async () => {
      const user = await getCurrentUser();
      expect(user).toBeNull();
    });

    test('沒有使用者資訊時應該回傳 null', async () => {
      mockStorageData.auth_data = {
        accessToken: { value: 'encrypted' }
        // 缺少 user
      };

      const user = await getCurrentUser();
      expect(user).toBeNull();
    });
  });
});
