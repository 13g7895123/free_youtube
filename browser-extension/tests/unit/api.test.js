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
    }
  }
}));

// Mock config
jest.mock('../../src/utils/config', () => ({
  config: {
    backendUrl: 'https://api.example.com'
  }
}));

// Mock Web Crypto API
global.crypto = {
  subtle: {
    generateKey: jest.fn(() => Promise.resolve({ type: 'secret' })),
    encrypt: jest.fn(() => Promise.resolve(new Uint8Array([1, 2, 3]))),
    decrypt: jest.fn(() => Promise.resolve(new TextEncoder().encode('decrypted_token'))),
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
import { callBackendAPI, checkAPIHealth } from '../../src/services/api';

describe('API Service', () => {
  beforeEach(() => {
    // 清空 mock storage
    mockStorageData = {};
    jest.clearAllMocks();
  });

  describe('callBackendAPI', () => {
    test('成功呼叫 API 應該回傳資料', async () => {
      const mockResponse = { success: true, data: 'test data' };
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await callBackendAPI('/test', {
        method: 'GET'
      });

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        'https://api.example.com/test',
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            'Content-Type': 'application/json'
          })
        })
      );
    });

    test('POST 請求應該包含 body', async () => {
      const mockResponse = { success: true };
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const requestBody = { key: 'value' };
      await callBackendAPI('/test', {
        method: 'POST',
        body: requestBody
      });

      expect(global.fetch).toHaveBeenCalledWith(
        'https://api.example.com/test',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(requestBody)
        })
      );
    });

    test('requireAuth 為 true 時應該附加 Authorization header', async () => {
      // 設定已登入狀態
      mockStorageData.auth_data = {
        accessToken: {
          value: {
            encrypted: 'AQID',
            iv: 'AQID',
            key: JSON.stringify({ kty: 'oct', k: 'test' })
          },
          expiresAt: Date.now() + 3600000
        },
        refreshToken: {
          value: {
            encrypted: 'AQID',
            iv: 'AQID',
            key: JSON.stringify({ kty: 'oct', k: 'test' })
          },
          expiresAt: Date.now() + 604800000
        }
      };

      const mockResponse = { success: true };
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      await callBackendAPI('/test', {
        method: 'GET',
        requireAuth: true
      });

      expect(global.fetch).toHaveBeenCalledWith(
        'https://api.example.com/test',
        expect.objectContaining({
          headers: expect.objectContaining({
            'Authorization': expect.stringContaining('Bearer ')
          })
        })
      );
    });

    test('API 回應錯誤應該拋出錯誤', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 400,
        statusText: 'Bad Request',
        json: () => Promise.resolve({ message: 'Invalid request' })
      });

      await expect(callBackendAPI('/test')).rejects.toThrow('Invalid request');
    });

    test('401 錯誤應該清除認證資料', async () => {
      mockStorageData.auth_data = { test: 'data' };

      global.fetch.mockResolvedValue({
        ok: false,
        status: 401,
        statusText: 'Unauthorized',
        json: () => Promise.resolve({})
      });

      await expect(callBackendAPI('/test')).rejects.toThrow();

      // 驗證認證資料被清除
      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });

    test('網路錯誤應該重試並最終拋出錯誤', async () => {
      global.fetch.mockRejectedValue(new Error('Network error'));

      await expect(callBackendAPI('/test')).rejects.toThrow('Network error');

      // 驗證重試了 3 次（初始 + 3 次重試 = 4 次呼叫）
      // 注意：retryWithBackoff 預設最多重試 3 次，總共會呼叫 4 次
      expect(global.fetch).toHaveBeenCalledTimes(4);
    }, 10000); // 增加 timeout 因為有重試延遲

    test('requireAuth 但未登入應該拋出錯誤', async () => {
      await expect(
        callBackendAPI('/test', { requireAuth: true })
      ).rejects.toThrow('User not authenticated');
    });
  });

  describe('Token Refresh', () => {
    test('access token 過期時應該自動刷新', async () => {
      // 設定過期的 access token
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
          expiresAt: Date.now() + 604800000
        }
      };

      // Mock refresh token API response
      global.fetch.mockImplementation((url) => {
        if (url.includes('/auth/refresh')) {
          return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({
              accessToken: 'new_access_token',
              expiresIn: 3600
            })
          });
        }
        // 正常 API 呼叫
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ success: true })
        });
      });

      await callBackendAPI('/test', { requireAuth: true });

      // 驗證 refresh API 被呼叫
      expect(global.fetch).toHaveBeenCalledWith(
        'https://api.example.com/auth/refresh',
        expect.any(Object)
      );

      // 驗證 access token 被更新
      expect(browser.storage.local.set).toHaveBeenCalled();
    });

    test('refresh token 過期時應該清除認證資料', async () => {
      // 設定過期的 refresh token
      mockStorageData.auth_data = {
        accessToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() - 1000
        },
        refreshToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() - 1000 // 也過期
        }
      };

      await expect(
        callBackendAPI('/test', { requireAuth: true })
      ).rejects.toThrow('Refresh token expired');

      // 驗證認證資料被清除
      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });

    test('refresh token API 失敗應該清除認證資料', async () => {
      mockStorageData.auth_data = {
        accessToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() - 1000
        },
        refreshToken: {
          value: { encrypted: 'AQID', iv: 'AQID', key: JSON.stringify({ kty: 'oct' }) },
          expiresAt: Date.now() + 604800000
        }
      };

      // Mock refresh API 失敗
      global.fetch.mockImplementation((url) => {
        if (url.includes('/auth/refresh')) {
          return Promise.resolve({
            ok: false,
            status: 401,
            statusText: 'Unauthorized'
          });
        }
      });

      await expect(
        callBackendAPI('/test', { requireAuth: true })
      ).rejects.toThrow();

      // 驗證認證資料被清除
      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
    });
  });

  describe('checkAPIHealth', () => {
    test('API 健康時應該回傳 true', async () => {
      global.fetch.mockResolvedValue({ ok: true });

      const result = await checkAPIHealth();
      expect(result).toBe(true);
      expect(global.fetch).toHaveBeenCalledWith('https://api.example.com/health');
    });

    test('API 不健康時應該回傳 false', async () => {
      global.fetch.mockResolvedValue({ ok: false });

      const result = await checkAPIHealth();
      expect(result).toBe(false);
    });

    test('網路錯誤時應該回傳 false', async () => {
      global.fetch.mockRejectedValue(new Error('Network error'));

      const result = await checkAPIHealth();
      expect(result).toBe(false);
    });
  });
});
