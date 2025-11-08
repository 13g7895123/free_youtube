/**
 * 認證流程整合測試
 * 模擬完整的登入、使用 API、刷新 token、登出流程
 */

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

// Mock Web Crypto API
global.crypto = {
  subtle: {
    generateKey: jest.fn(() => Promise.resolve({ type: 'secret' })),
    encrypt: jest.fn((algo, key, data) => {
      return Promise.resolve(new Uint8Array([1, 2, 3]));
    }),
    decrypt: jest.fn((algo, key, data) => {
      // 回傳可以轉換成 "test_token" 的 buffer
      return Promise.resolve(new TextEncoder().encode('test_token'));
    }),
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

// Mock fetch
global.fetch = jest.fn();

// Import after mocking
import browser from 'webextension-polyfill';
import { loginWithLINE, logout, isAuthenticated, getCurrentUser } from '../../src/services/auth';
import { callBackendAPI } from '../../src/services/api';

describe('Authentication Integration Tests', () => {
  beforeEach(() => {
    // 清空 mock storage
    mockStorageData = {};
    jest.clearAllMocks();

    // 重置 fetch mock
    global.fetch.mockReset();
  });

  describe('完整登入流程', () => {
    test('應該完成完整的登入、使用 API、登出流程', async () => {
      // === 1. 登入 ===

      // Mock LINE OAuth redirect
      const mockRedirectUrl = 'https://example.com/callback?code=auth_code_123&state=abc';
      browser.identity.launchWebAuthFlow.mockResolvedValue(mockRedirectUrl);

      // Mock backend auth callback
      const mockAuthResponse = {
        accessToken: 'access_token_123',
        refreshToken: 'refresh_token_123',
        expiresIn: 3600,
        user: {
          lineUserId: 'U1234567890',
          displayName: 'Test User',
          profilePictureUrl: 'https://example.com/profile.jpg'
        },
        isNewUser: false
      };

      global.fetch.mockImplementation((url) => {
        if (url.includes('/auth/line/callback')) {
          return Promise.resolve({
            ok: true,
            json: () => Promise.resolve(mockAuthResponse)
          });
        }
      });

      // 執行登入
      const loginResult = await loginWithLINE();

      // 驗證登入成功
      expect(loginResult.success).toBe(true);
      expect(loginResult.user.lineUserId).toBe('U1234567890');

      // 驗證認證資料被儲存
      expect(browser.storage.local.set).toHaveBeenCalled();

      // === 2. 檢查登入狀態 ===

      const authenticated = await isAuthenticated();
      expect(authenticated).toBe(true);

      const currentUser = await getCurrentUser();
      expect(currentUser.lineUserId).toBe('U1234567890');

      // === 3. 使用需要認證的 API ===

      global.fetch.mockImplementation((url) => {
        if (url.includes('/api/videos')) {
          return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ videos: [] })
          });
        }
      });

      const apiResult = await callBackendAPI('/api/videos', { requireAuth: true });
      expect(apiResult.videos).toBeDefined();

      // 驗證 Authorization header 被附加
      expect(global.fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            'Authorization': expect.stringContaining('Bearer ')
          })
        })
      );

      // === 4. 登出 ===

      global.fetch.mockImplementation((url) => {
        if (url.includes('/auth/logout')) {
          return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ success: true })
          });
        }
      });

      await logout();

      // 驗證登出 API 被呼叫
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/auth/logout'),
        expect.any(Object)
      );

      // 驗證認證資料被清除
      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');

      // === 5. 驗證登出後狀態 ===

      const authenticatedAfterLogout = await isAuthenticated();
      expect(authenticatedAfterLogout).toBe(false);

      const userAfterLogout = await getCurrentUser();
      expect(userAfterLogout).toBeNull();
    });
  });

  describe('Token 刷新流程', () => {
    test('應該在 access token 過期時自動刷新並繼續 API 呼叫', async () => {
      // === 1. 設定過期的 access token ===

      mockStorageData.auth_data = {
        accessToken: {
          value: {
            encrypted: 'AQID',
            iv: 'AQID',
            key: JSON.stringify({ kty: 'oct', k: 'test' })
          },
          expiresAt: Date.now() - 1000 // 已過期
        },
        refreshToken: {
          value: {
            encrypted: 'AQID',
            iv: 'AQID',
            key: JSON.stringify({ kty: 'oct', k: 'test' })
          },
          expiresAt: Date.now() + 604800000 // 未過期
        },
        user: {
          lineUserId: 'U1234567890',
          displayName: 'Test User'
        }
      };

      // === 2. Mock refresh token API ===

      let refreshCalled = false;
      global.fetch.mockImplementation((url) => {
        if (url.includes('/auth/refresh')) {
          refreshCalled = true;
          return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({
              accessToken: 'new_access_token',
              expiresIn: 3600
            })
          });
        }
        if (url.includes('/api/videos')) {
          return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ videos: [] })
          });
        }
      });

      // === 3. 呼叫需要認證的 API ===

      const result = await callBackendAPI('/api/videos', { requireAuth: true });

      // === 4. 驗證 ===

      // 驗證 refresh token API 被呼叫
      expect(refreshCalled).toBe(true);

      // 驗證 access token 被更新
      expect(browser.storage.local.set).toHaveBeenCalled();

      // 驗證 API 呼叫成功
      expect(result.videos).toBeDefined();
    });

    test('refresh token 過期時應該要求重新登入', async () => {
      // === 1. 設定過期的 tokens ===

      mockStorageData.auth_data = {
        accessToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() - 1000 // 過期
        },
        refreshToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() - 1000 // 也過期
        }
      };

      // === 2. 呼叫 API 應該失敗 ===

      await expect(
        callBackendAPI('/api/videos', { requireAuth: true })
      ).rejects.toThrow('Refresh token expired');

      // === 3. 驗證認證資料被清除 ===

      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });
  });

  describe('錯誤處理流程', () => {
    test('使用者取消授權應該不改變登入狀態', async () => {
      // 初始狀態：未登入
      expect(await isAuthenticated()).toBe(false);

      // Mock 使用者取消授權
      browser.identity.launchWebAuthFlow.mockRejectedValue(
        new Error('User cancelled authorization')
      );

      // 嘗試登入
      await expect(loginWithLINE()).rejects.toThrow();

      // 驗證仍未登入
      expect(await isAuthenticated()).toBe(false);
    });

    test('後端錯誤應該保持原有登入狀態', async () => {
      // === 1. 先登入 ===

      mockStorageData.auth_data = {
        accessToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() + 3600000
        },
        refreshToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() + 604800000
        },
        user: { lineUserId: 'U1234567890' }
      };

      expect(await isAuthenticated()).toBe(true);

      // === 2. API 呼叫失敗（非 401） ===

      global.fetch.mockResolvedValue({
        ok: false,
        status: 500,
        statusText: 'Internal Server Error',
        json: () => Promise.resolve({ message: 'Server error' })
      });

      await expect(
        callBackendAPI('/api/videos', { requireAuth: true })
      ).rejects.toThrow();

      // === 3. 驗證仍保持登入狀態（因為不是 401） ===

      expect(await isAuthenticated()).toBe(true);
    });

    test('401 錯誤應該清除登入狀態', async () => {
      // === 1. 先登入 ===

      mockStorageData.auth_data = {
        accessToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() + 3600000
        },
        user: { lineUserId: 'U1234567890' }
      };

      expect(await isAuthenticated()).toBe(true);

      // === 2. 收到 401 錯誤 ===

      global.fetch.mockResolvedValue({
        ok: false,
        status: 401,
        statusText: 'Unauthorized',
        json: () => Promise.resolve({})
      });

      await expect(callBackendAPI('/api/videos')).rejects.toThrow();

      // === 3. 驗證登入狀態被清除 ===

      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });
  });
});
