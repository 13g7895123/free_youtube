import browser from 'webextension-polyfill';
import { config } from '../utils/config.js';
import { saveAuthData, clearAuthData } from '../utils/token-manager.js';
import { callBackendAPI } from './api.js';

/**
 * LINE OAuth 認證服務
 */

/**
 * 開始 LINE OAuth 登入流程
 * @returns {Promise<Object>} 認證結果包含使用者資訊
 */
export async function loginWithLINE() {
  try {
    // 1. 構建 LINE OAuth 授權 URL
    const authUrl = buildLINEAuthURL();

    // 2. 使用 browser.identity API 開啟授權頁面
    const redirectUrl = await browser.identity.launchWebAuthFlow({
      url: authUrl,
      interactive: true
    });

    // 3. 從 redirect URL 解析 authorization code
    const code = extractAuthorizationCode(redirectUrl);

    if (!code) {
      throw new Error('Failed to extract authorization code from redirect URL');
    }

    // 4. 透過後端 API 交換 code 換取 tokens
    const authData = await exchangeCodeForTokens(code);

    // 5. 儲存認證資料
    await saveAuthData({
      accessToken: authData.accessToken,
      refreshToken: authData.refreshToken,
      expiresIn: authData.expiresIn,
      user: authData.user
    });

    return {
      success: true,
      user: authData.user,
      isNewUser: authData.isNewUser || false
    };

  } catch (error) {
    console.error('LINE OAuth login failed:', error);
    throw error;
  }
}

/**
 * 構建 LINE OAuth 授權 URL
 * @returns {string}
 */
function buildLINEAuthURL() {
  const params = new URLSearchParams({
    response_type: 'code',
    client_id: config.lineChannelId,
    redirect_uri: config.lineRedirectUri,
    state: generateState(),
    scope: 'profile openid'
  });

  return `${config.lineAuthUrl}?${params.toString()}`;
}

/**
 * 生成隨機 state 參數（用於防止 CSRF）
 * @returns {string}
 */
function generateState() {
  const array = new Uint8Array(16);
  crypto.getRandomValues(array);
  return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

/**
 * 從 redirect URL 解析 authorization code
 * @param {string} redirectUrl - OAuth redirect URL
 * @returns {string|null} Authorization code
 */
export function extractAuthorizationCode(redirectUrl) {
  try {
    const url = new URL(redirectUrl);
    return url.searchParams.get('code');
  } catch (error) {
    console.error('Failed to parse redirect URL:', error);
    return null;
  }
}

/**
 * 透過後端 API 交換 authorization code 換取 tokens
 * @param {string} code - Authorization code
 * @returns {Promise<Object>} Token 與使用者資訊
 */
async function exchangeCodeForTokens(code) {
  const response = await callBackendAPI('/auth/line/callback', {
    method: 'POST',
    body: {
      code,
      redirectUri: config.lineRedirectUri
    }
  });

  return {
    accessToken: response.accessToken,
    refreshToken: response.refreshToken,
    expiresIn: response.expiresIn,
    user: response.user,
    isNewUser: response.isNewUser
  };
}

/**
 * 登出
 * @returns {Promise<void>}
 */
export async function logout() {
  try {
    // 1. 呼叫後端 API 清除 refresh token
    // 注意：需要先取得當前的 refresh token
    const authData = await browser.storage.local.get('auth_data');

    if (authData && authData.auth_data && authData.auth_data.refreshToken) {
      try {
        await callBackendAPI('/auth/logout', {
          method: 'POST',
          body: {
            refreshToken: authData.auth_data.refreshToken.value
          }
        });
      } catch (error) {
        console.warn('Failed to revoke refresh token on server:', error);
        // 即使後端失敗，仍繼續清除本地資料
      }
    }

    // 2. 清除本地認證資料
    await clearAuthData();

  } catch (error) {
    console.error('Logout failed:', error);
    // 確保本地資料被清除
    await clearAuthData();
    throw error;
  }
}

/**
 * 檢查是否已登入
 * @returns {Promise<boolean>}
 */
export async function isAuthenticated() {
  const authData = await browser.storage.local.get('auth_data');
  return !!(authData && authData.auth_data && authData.auth_data.accessToken);
}

/**
 * 取得當前使用者資訊
 * @returns {Promise<Object|null>}
 */
export async function getCurrentUser() {
  const authData = await browser.storage.local.get('auth_data');

  if (authData && authData.auth_data && authData.auth_data.user) {
    return authData.auth_data.user;
  }

  return null;
}
