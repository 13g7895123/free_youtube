// Mock webextension-polyfill is automatically loaded from __mocks__/
jest.mock('webextension-polyfill');

// Import after mocking
import browser, { mockStorageData, resetMockStorage } from 'webextension-polyfill';
import {
  encryptToken,
  decryptToken,
  saveAuthData,
  getAuthData,
  isAccessTokenExpired,
  isRefreshTokenExpired,
  updateAccessToken,
  clearAuthData
} from '../../src/utils/token-manager';

describe('Token Manager', () => {
  beforeEach(() => {
    // 清空 mock storage
    resetMockStorage();
    jest.clearAllMocks();
  });

  describe('encryptToken', () => {
    test('應該加密 token 並回傳加密資料', async () => {
      const token = 'test_access_token';
      const result = await encryptToken(token);

      expect(result).toHaveProperty('encrypted');
      expect(result).toHaveProperty('iv');
      expect(result).toHaveProperty('key');
      expect(typeof result.encrypted).toBe('string');
      expect(typeof result.iv).toBe('string');
      expect(typeof result.key).toBe('string');
    });
  });

  describe('decryptToken', () => {
    test('應該解密 token', async () => {
      const encryptedData = {
        encrypted: 'AQID',
        iv: 'AQID',
        key: JSON.stringify({ kty: 'oct', k: 'test' })
      };

      const result = await decryptToken(
        encryptedData.encrypted,
        encryptedData.iv,
        encryptedData.key
      );

      expect(typeof result).toBe('string');
    });
  });

  describe('saveAuthData', () => {
    test('應該加密並儲存認證資料', async () => {
      const authData = {
        accessToken: 'test_access_token',
        refreshToken: 'test_refresh_token',
        expiresIn: 3600,
        user: {
          lineUserId: 'U1234567890',
          displayName: 'Test User',
          profilePictureUrl: 'https://example.com/profile.jpg'
        }
      };

      await saveAuthData(authData);

      expect(browser.storage.local.set).toHaveBeenCalled();
      const savedData = mockStorageData.auth_data;
      expect(savedData).toBeDefined();
      expect(savedData.user.lineUserId).toBe('U1234567890');
      expect(savedData.accessToken).toHaveProperty('value');
      expect(savedData.accessToken).toHaveProperty('expiresAt');
      expect(savedData.refreshToken).toHaveProperty('value');
      expect(savedData.refreshToken).toHaveProperty('expiresAt');
    });
  });

  describe('getAuthData', () => {
    test('應該取得儲存的認證資料', async () => {
      const testData = {
        accessToken: { value: 'encrypted', expiresAt: Date.now() + 3600000 },
        refreshToken: { value: 'encrypted', expiresAt: Date.now() + 604800000 },
        user: { lineUserId: 'U1234567890' }
      };

      mockStorageData.auth_data = testData;

      const result = await getAuthData();
      expect(result).toEqual(testData);
    });

    test('沒有認證資料時應該回傳 null', async () => {
      const result = await getAuthData();
      expect(result).toBeNull();
    });
  });

  describe('isAccessTokenExpired', () => {
    test('過期的 access token 應該回傳 true', async () => {
      mockStorageData.auth_data = {
        accessToken: { expiresAt: Date.now() - 1000 },
        refreshToken: { expiresAt: Date.now() + 604800000 }
      };

      const result = await isAccessTokenExpired();
      expect(result).toBe(true);
    });

    test('未過期的 access token 應該回傳 false', async () => {
      mockStorageData.auth_data = {
        accessToken: { expiresAt: Date.now() + 3600000 },
        refreshToken: { expiresAt: Date.now() + 604800000 }
      };

      const result = await isAccessTokenExpired();
      expect(result).toBe(false);
    });

    test('沒有認證資料時應該回傳 true', async () => {
      const result = await isAccessTokenExpired();
      expect(result).toBe(true);
    });
  });

  describe('isRefreshTokenExpired', () => {
    test('過期的 refresh token 應該回傳 true', async () => {
      mockStorageData.auth_data = {
        refreshToken: { expiresAt: Date.now() - 1000 }
      };

      const result = await isRefreshTokenExpired();
      expect(result).toBe(true);
    });

    test('未過期的 refresh token 應該回傳 false', async () => {
      mockStorageData.auth_data = {
        refreshToken: { expiresAt: Date.now() + 604800000 }
      };

      const result = await isRefreshTokenExpired();
      expect(result).toBe(false);
    });
  });

  describe('updateAccessToken', () => {
    test('應該更新 access token', async () => {
      mockStorageData.auth_data = {
        accessToken: { value: 'old_token', expiresAt: Date.now() - 1000 },
        refreshToken: { value: 'refresh_token', expiresAt: Date.now() + 604800000 }
      };

      await updateAccessToken('new_access_token', 3600);

      const savedData = mockStorageData.auth_data;
      expect(savedData.accessToken).toHaveProperty('value');
      expect(savedData.accessToken.expiresAt).toBeGreaterThan(Date.now());
    });

    test('沒有認證資料時應該拋出錯誤', async () => {
      await expect(updateAccessToken('new_token', 3600)).rejects.toThrow('No auth data found');
    });
  });

  describe('clearAuthData', () => {
    test('應該清除認證資料', async () => {
      mockStorageData.auth_data = { test: 'data' };

      await clearAuthData();

      expect(browser.storage.local.remove).toHaveBeenCalledWith('auth_data');
      expect(mockStorageData.auth_data).toBeUndefined();
    });
  });
});
