import browser from 'webextension-polyfill';
import { config } from '../utils/config.js';
import { retryWithBackoff } from '../utils/retry.js';
import {
  getAuthData,
  isAccessTokenExpired,
  isRefreshTokenExpired,
  updateAccessToken,
  decryptToken,
  clearAuthData
} from '../utils/token-manager.js';

/**
 * 後端 API 通訊層
 */

/**
 * 呼叫後端 API
 * @param {string} endpoint - API 端點路徑（例如：'/auth/line/callback'）
 * @param {Object} options - 請求選項
 * @param {string} options.method - HTTP 方法（GET, POST, PUT, DELETE）
 * @param {Object} options.body - 請求 body（會自動轉為 JSON）
 * @param {Object} options.headers - 額外的 headers
 * @param {boolean} options.requireAuth - 是否需要認證（預設 false）
 * @returns {Promise<Object>} API 回應資料
 */
export async function callBackendAPI(endpoint, options = {}) {
  const {
    method = 'GET',
    body = null,
    headers = {},
    requireAuth = false
  } = options;

  // 如果需要認證，確保 token 有效
  if (requireAuth) {
    await ensureValidAccessToken();
  }

  // 使用 retry 策略呼叫 API
  return retryWithBackoff(async () => {
    const url = `${config.backendUrl}${endpoint}`;

    // 準備 headers
    const requestHeaders = {
      'Content-Type': 'application/json',
      ...headers
    };

    // 如果需要認證，加入 access token
    if (requireAuth) {
      const authData = await getAuthData();
      if (authData && authData.accessToken) {
        const token = await decryptToken(
          authData.accessToken.value.encrypted,
          authData.accessToken.value.iv,
          authData.accessToken.value.key
        );
        requestHeaders['Authorization'] = `Bearer ${token}`;
      }
    }

    // 準備請求選項
    const fetchOptions = {
      method,
      headers: requestHeaders
    };

    // 如果有 body，加入請求
    if (body) {
      fetchOptions.body = JSON.stringify(body);
    }

    // 發送請求
    const response = await fetch(url, fetchOptions);

    // 處理回應
    if (!response.ok) {
      await handleAPIError(response);
    }

    // 解析 JSON 回應
    const data = await response.json();
    return data;
  }, 3, 1000); // 最多重試 3 次，初始延遲 1 秒
}

/**
 * 確保 access token 有效
 * 如果過期，嘗試使用 refresh token 更新
 * @returns {Promise<void>}
 */
async function ensureValidAccessToken() {
  const authData = await getAuthData();

  // 沒有認證資料
  if (!authData) {
    throw new Error('User not authenticated');
  }

  // 檢查 refresh token 是否過期
  if (await isRefreshTokenExpired()) {
    // Refresh token 過期，需要重新登入
    await clearAuthData();
    throw new Error('Refresh token expired. Please login again.');
  }

  // 檢查 access token 是否過期
  if (await isAccessTokenExpired()) {
    // Access token 過期，使用 refresh token 更新
    await refreshAccessToken();
  }
}

/**
 * 使用 refresh token 更新 access token
 * @returns {Promise<void>}
 */
async function refreshAccessToken() {
  try {
    const authData = await getAuthData();

    if (!authData || !authData.refreshToken) {
      throw new Error('No refresh token available');
    }

    // 解密 refresh token
    const refreshToken = await decryptToken(
      authData.refreshToken.value.encrypted,
      authData.refreshToken.value.iv,
      authData.refreshToken.value.key
    );

    // 呼叫後端 API 刷新 token（不使用 requireAuth，因為我們正在刷新 token）
    const response = await fetch(`${config.backendUrl}/auth/refresh`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ refreshToken })
    });

    if (!response.ok) {
      // 刷新失敗，可能是 refresh token 無效
      if (response.status === 401 || response.status === 403) {
        await clearAuthData();
        throw new Error('Refresh token invalid. Please login again.');
      }
      throw new Error(`Failed to refresh token: ${response.statusText}`);
    }

    const data = await response.json();

    // 更新 access token
    await updateAccessToken(data.accessToken, data.expiresIn);

  } catch (error) {
    console.error('Failed to refresh access token:', error);
    throw error;
  }
}

/**
 * 處理 API 錯誤
 * @param {Response} response - Fetch Response 物件
 * @throws {Error} API 錯誤
 */
async function handleAPIError(response) {
  let errorMessage = `API request failed: ${response.status} ${response.statusText}`;

  try {
    // 嘗試解析錯誤訊息
    const errorData = await response.json();
    if (errorData.message) {
      errorMessage = errorData.message;
    } else if (errorData.error) {
      errorMessage = errorData.error;
    }
  } catch (e) {
    // 無法解析 JSON，使用預設錯誤訊息
  }

  // 如果是 401 未授權錯誤，清除認證資料
  if (response.status === 401) {
    await clearAuthData();
    throw new Error('Authentication failed. Please login again.');
  }

  throw new Error(errorMessage);
}

/**
 * 檢查後端 API 健康狀態
 * @returns {Promise<boolean>}
 */
export async function checkAPIHealth() {
  try {
    const response = await fetch(`${config.backendUrl}/health`);
    return response.ok;
  } catch (error) {
    console.error('API health check failed:', error);
    return false;
  }
}

/**
 * 加入影片到播放庫
 * @param {Object} videoData - 影片資料
 * @param {string} videoData.youtubeVideoId - YouTube 影片 ID
 * @param {string} videoData.title - 影片標題（可為 null）
 * @param {string} videoData.thumbnailUrl - 縮圖 URL
 * @param {number} videoData.duration - 影片時長（秒，可為 null）
 * @param {string} videoData.channelTitle - 頻道名稱（可為 null）
 * @returns {Promise<Object>} 加入結果
 */
export async function addVideoToLibrary(videoData) {
  try {
    const response = await callBackendAPI('/library/videos', {
      method: 'POST',
      requireAuth: true,
      body: videoData
    });

    return {
      success: true,
      video: response.video
    };

  } catch (error) {
    // 處理影片已存在的情況 (HTTP 409 Conflict)
    if (error.message.includes('409') || error.message.includes('already exists')) {
      return {
        success: false,
        error: 'VIDEO_ALREADY_EXISTS',
        message: '影片已存在於播放庫中'
      };
    }

    // 其他錯誤
    throw error;
  }
}

/**
 * 從播放庫取得影片列表
 * @param {Object} options - 查詢選項
 * @param {number} options.page - 頁碼（從 1 開始）
 * @param {number} options.limit - 每頁數量
 * @returns {Promise<Object>} 影片列表與分頁資訊
 */
export async function getLibraryVideos(options = {}) {
  const { page = 1, limit = 20 } = options;

  const response = await callBackendAPI(
    `/library/videos?page=${page}&limit=${limit}`,
    {
      method: 'GET',
      requireAuth: true
    }
  );

  return {
    videos: response.videos,
    pagination: response.pagination
  };
}

/**
 * 從播放庫移除影片
 * @param {string} videoId - 影片 ID（後端資料庫 ID）
 * @returns {Promise<Object>} 移除結果
 */
export async function removeVideoFromLibrary(videoId) {
  await callBackendAPI(`/library/videos/${videoId}`, {
    method: 'DELETE',
    requireAuth: true
  });

  return { success: true };
}

/**
 * 取得使用者的所有播放清單
 * @param {Object} options - 查詢選項
 * @param {number} options.page - 頁碼（從 1 開始）
 * @param {number} options.limit - 每頁數量
 * @returns {Promise<Object>} 播放清單列表與分頁資訊
 */
export async function getPlaylists(options = {}) {
  const { page = 1, limit = 50 } = options;

  const response = await callBackendAPI(
    `/playlists?page=${page}&limit=${limit}`,
    {
      method: 'GET',
      requireAuth: true
    }
  );

  return {
    playlists: response.playlists,
    pagination: response.pagination
  };
}

/**
 * 取得播放清單的詳細資訊
 * @param {string} playlistId - 播放清單 ID
 * @returns {Promise<Object>} 播放清單詳細資訊
 */
export async function getPlaylistDetails(playlistId) {
  const response = await callBackendAPI(`/playlists/${playlistId}`, {
    method: 'GET',
    requireAuth: true
  });

  return response;
}

/**
 * 加入影片到播放清單
 * @param {string} playlistId - 播放清單 ID
 * @param {Object} videoData - 影片資料
 * @param {string} videoData.youtubeVideoId - YouTube 影片 ID
 * @param {string} videoData.title - 影片標題（可為 null）
 * @param {string} videoData.thumbnailUrl - 縮圖 URL
 * @param {number} videoData.duration - 影片時長（秒，可為 null）
 * @returns {Promise<Object>} 加入結果
 */
export async function addVideoToPlaylist(playlistId, videoData) {
  try {
    const response = await callBackendAPI(`/playlists/${playlistId}/videos`, {
      method: 'POST',
      requireAuth: true,
      body: videoData
    });

    return {
      success: true,
      video: response.video
    };

  } catch (error) {
    // 處理影片已在播放清單中的情況
    if (error.message.includes('409') || error.message.includes('already in')) {
      return {
        success: false,
        error: 'VIDEO_ALREADY_IN_PLAYLIST',
        message: '影片已在播放清單中'
      };
    }

    throw error;
  }
}

/**
 * 從播放清單移除影片
 * @param {string} playlistId - 播放清單 ID
 * @param {string} videoId - 影片 ID（後端資料庫 ID）
 * @returns {Promise<Object>} 移除結果
 */
export async function removeVideoFromPlaylist(playlistId, videoId) {
  await callBackendAPI(`/playlists/${playlistId}/videos/${videoId}`, {
    method: 'DELETE',
    requireAuth: true
  });

  return { success: true };
}

/**
 * 建立新的播放清單
 * @param {Object} playlistData - 播放清單資料
 * @param {string} playlistData.name - 播放清單名稱
 * @param {string} playlistData.description - 播放清單描述（可選）
 * @param {boolean} playlistData.isPublic - 是否公開（預設 false）
 * @returns {Promise<Object>} 新建的播放清單資訊
 */
export async function createPlaylist(playlistData) {
  const response = await callBackendAPI('/playlists', {
    method: 'POST',
    requireAuth: true,
    body: playlistData
  });

  return {
    success: true,
    playlist: response.playlist
  };
}
