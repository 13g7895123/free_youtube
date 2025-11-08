import browser from 'webextension-polyfill';

/**
 * Token 管理工具
 * 負責 Token 的加密、解密、儲存、讀取、驗證與更新
 */

const AUTH_DATA_KEY = 'auth_data';

/**
 * 加密 Token（使用 AES-GCM）
 * @param {string} token - 要加密的 token
 * @returns {Promise<{encrypted: string, iv: string, key: string}>}
 */
export async function encryptToken(token) {
  const encoder = new TextEncoder();
  const data = encoder.encode(token);

  // 生成加密金鑰
  const key = await crypto.subtle.generateKey(
    { name: 'AES-GCM', length: 256 },
    true,
    ['encrypt', 'decrypt']
  );

  // 生成初始化向量（IV）
  const iv = crypto.getRandomValues(new Uint8Array(12));

  // 執行加密
  const encrypted = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv },
    key,
    data
  );

  // 將結果轉換為 base64 以便儲存
  const encryptedBase64 = btoa(String.fromCharCode(...new Uint8Array(encrypted)));
  const ivBase64 = btoa(String.fromCharCode(...iv));
  const keyData = await crypto.subtle.exportKey('jwk', key);

  return {
    encrypted: encryptedBase64,
    iv: ivBase64,
    key: JSON.stringify(keyData)
  };
}

/**
 * 解密 Token
 * @param {string} encryptedBase64 - 加密的 token (base64)
 * @param {string} ivBase64 - 初始化向量 (base64)
 * @param {string} keyJson - 加密金鑰 (JSON string)
 * @returns {Promise<string>} 解密後的 token
 */
export async function decryptToken(encryptedBase64, ivBase64, keyJson) {
  // 將 base64 轉回 ArrayBuffer
  const encrypted = Uint8Array.from(atob(encryptedBase64), c => c.charCodeAt(0));
  const iv = Uint8Array.from(atob(ivBase64), c => c.charCodeAt(0));

  // 匯入金鑰
  const keyData = JSON.parse(keyJson);
  const key = await crypto.subtle.importKey(
    'jwk',
    keyData,
    { name: 'AES-GCM', length: 256 },
    true,
    ['decrypt']
  );

  // 執行解密
  const decrypted = await crypto.subtle.decrypt(
    { name: 'AES-GCM', iv },
    key,
    encrypted
  );

  // 將 ArrayBuffer 轉回字串
  const decoder = new TextDecoder();
  return decoder.decode(decrypted);
}

/**
 * 儲存認證資料
 * @param {Object} authData - 認證資料
 * @param {string} authData.accessToken - Access token
 * @param {string} authData.refreshToken - Refresh token
 * @param {number} authData.expiresIn - Access token 有效秒數
 * @param {Object} authData.user - 使用者資訊
 */
export async function saveAuthData(authData) {
  const { accessToken, refreshToken, expiresIn, user } = authData;

  // 加密 tokens
  const encryptedAccessToken = await encryptToken(accessToken);
  const encryptedRefreshToken = await encryptToken(refreshToken);

  // 計算過期時間
  const now = Date.now();
  const accessTokenExpiresAt = now + (expiresIn * 1000); // 預設 1 小時
  const refreshTokenExpiresAt = now + (7 * 24 * 60 * 60 * 1000); // 7 天

  const data = {
    accessToken: {
      value: encryptedAccessToken.encrypted,
      iv: encryptedAccessToken.iv,
      key: encryptedAccessToken.key,
      expiresAt: accessTokenExpiresAt
    },
    refreshToken: {
      value: encryptedRefreshToken.encrypted,
      iv: encryptedRefreshToken.iv,
      key: encryptedRefreshToken.key,
      expiresAt: refreshTokenExpiresAt
    },
    user: {
      lineUserId: user.lineUserId,
      displayName: user.displayName,
      profilePictureUrl: user.profilePictureUrl
    }
  };

  await browser.storage.local.set({ [AUTH_DATA_KEY]: data });
}

/**
 * 讀取認證資料
 * @returns {Promise<Object|null>} 認證資料或 null
 */
export async function getAuthData() {
  const result = await browser.storage.local.get(AUTH_DATA_KEY);
  return result[AUTH_DATA_KEY] || null;
}

/**
 * 取得解密後的 Access Token
 * @returns {Promise<string|null>}
 */
export async function getAccessToken() {
  const authData = await getAuthData();
  if (!authData || !authData.accessToken) {
    return null;
  }

  const { value, iv, key } = authData.accessToken;
  return await decryptToken(value, iv, key);
}

/**
 * 取得解密後的 Refresh Token
 * @returns {Promise<string|null>}
 */
export async function getRefreshToken() {
  const authData = await getAuthData();
  if (!authData || !authData.refreshToken) {
    return null;
  }

  const { value, iv, key } = authData.refreshToken;
  return await decryptToken(value, iv, key);
}

/**
 * 檢查 Access Token 是否過期
 * @returns {Promise<boolean>}
 */
export async function isAccessTokenExpired() {
  const authData = await getAuthData();
  if (!authData || !authData.accessToken) {
    return true;
  }

  return Date.now() >= authData.accessToken.expiresAt;
}

/**
 * 檢查 Refresh Token 是否過期
 * @returns {Promise<boolean>}
 */
export async function isRefreshTokenExpired() {
  const authData = await getAuthData();
  if (!authData || !authData.refreshToken) {
    return true;
  }

  return Date.now() >= authData.refreshToken.expiresAt;
}

/**
 * 更新 Access Token
 * @param {string} newAccessToken - 新的 access token
 * @param {number} expiresIn - 有效秒數
 */
export async function updateAccessToken(newAccessToken, expiresIn) {
  const authData = await getAuthData();
  if (!authData) {
    throw new Error('No auth data found');
  }

  const encryptedAccessToken = await encryptToken(newAccessToken);
  const accessTokenExpiresAt = Date.now() + (expiresIn * 1000);

  authData.accessToken = {
    value: encryptedAccessToken.encrypted,
    iv: encryptedAccessToken.iv,
    key: encryptedAccessToken.key,
    expiresAt: accessTokenExpiresAt
  };

  await browser.storage.local.set({ [AUTH_DATA_KEY]: authData });
}

/**
 * 清除認證資料（登出）
 */
export async function clearAuthData() {
  await browser.storage.local.remove(AUTH_DATA_KEY);
}
