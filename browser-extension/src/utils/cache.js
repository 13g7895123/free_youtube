import browser from 'webextension-polyfill';
import { config } from './config.js';

/**
 * 快取管理工具
 * 用於快取播放清單等資料，避免過度 API 呼叫
 */

/**
 * 將資料快取到 browser.storage.local
 * @param {string} key - 快取鍵
 * @param {any} value - 要快取的資料
 * @param {number} ttl - TTL (毫秒)，預設 5 分鐘
 * @returns {Promise<void>}
 */
export async function setCache(key, value, ttl = config.playlistCacheDuration) {
  const cacheData = {
    value,
    expiresAt: Date.now() + ttl
  };

  await browser.storage.local.set({ [key]: cacheData });
}

/**
 * 從快取取得資料
 * @param {string} key - 快取鍵
 * @returns {Promise<any|null>} 快取的資料，或 null 如果不存在或已過期
 */
export async function getCache(key) {
  const result = await browser.storage.local.get(key);
  const cacheData = result[key];

  if (!cacheData) {
    return null;
  }

  // 檢查快取是否已過期
  if (cacheData.expiresAt && cacheData.expiresAt < Date.now()) {
    // 快取已過期，移除
    await browser.storage.local.remove(key);
    return null;
  }

  return cacheData.value;
}

/**
 * 移除快取
 * @param {string} key - 快取鍵
 * @returns {Promise<void>}
 */
export async function removeCache(key) {
  await browser.storage.local.remove(key);
}

/**
 * 清除所有快取
 * @returns {Promise<void>}
 */
export async function clearAllCaches() {
  // 取得所有快取鍵
  const allData = await browser.storage.local.get(null);

  // 過濾出以 'cache_' 開頭的快取鍵
  const cacheKeys = Object.keys(allData).filter(key => key.startsWith('cache_'));

  // 移除所有快取
  if (cacheKeys.length > 0) {
    await browser.storage.local.remove(cacheKeys);
  }
}

/**
 * 檢查快取是否存在且有效
 * @param {string} key - 快取鍵
 * @returns {Promise<boolean>}
 */
export async function isCacheValid(key) {
  const cache = await getCache(key);
  return cache !== null;
}
