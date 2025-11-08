/**
 * 錯誤處理與重試機制
 * 使用指數退避策略（Exponential Backoff）
 */

/**
 * 使用指數退避策略重試函式
 * @param {Function} fn - 要執行的非同步函式
 * @param {number} maxRetries - 最大重試次數（預設 3 次）
 * @param {number} baseDelay - 基礎延遲時間（毫秒，預設 1000ms）
 * @returns {Promise<any>} 函式執行結果
 */
export async function retryWithBackoff(fn, maxRetries = 3, baseDelay = 1000) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      // 如果是最後一次重試，則拋出錯誤
      if (i === maxRetries - 1) {
        console.error(`Failed after ${maxRetries} retries:`, error);
        throw error;
      }

      // 計算延遲時間：2^i * baseDelay（指數退避）
      // 第 1 次：1000ms，第 2 次：2000ms，第 3 次：4000ms
      const delay = Math.pow(2, i) * baseDelay;

      console.warn(`Retry attempt ${i + 1}/${maxRetries} after ${delay}ms:`, error.message);

      // 等待後重試
      await sleep(delay);
    }
  }
}

/**
 * 延遲函式
 * @param {number} ms - 延遲毫秒數
 * @returns {Promise<void>}
 */
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * 判斷錯誤是否可重試
 * @param {Error} error - 錯誤物件
 * @returns {boolean}
 */
export function isRetryableError(error) {
  // 網路錯誤
  if (error.message && error.message.includes('network')) {
    return true;
  }

  // HTTP 5xx 錯誤（伺服器錯誤）
  if (error.status && error.status >= 500 && error.status < 600) {
    return true;
  }

  // HTTP 429 錯誤（Too Many Requests）
  if (error.status === 429) {
    return true;
  }

  // 逾時錯誤
  if (error.message && error.message.includes('timeout')) {
    return true;
  }

  return false;
}

/**
 * 條件性重試（僅在可重試錯誤時重試）
 * @param {Function} fn - 要執行的非同步函式
 * @param {number} maxRetries - 最大重試次數
 * @returns {Promise<any>}
 */
export async function retryOnRetryableError(fn, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      // 檢查是否為可重試錯誤
      if (!isRetryableError(error)) {
        // 不可重試的錯誤，直接拋出
        throw error;
      }

      // 如果是最後一次重試，則拋出錯誤
      if (i === maxRetries - 1) {
        throw error;
      }

      // 計算延遲時間
      const delay = Math.pow(2, i) * 1000;
      console.warn(`Retryable error, attempt ${i + 1}/${maxRetries} after ${delay}ms:`, error.message);

      await sleep(delay);
    }
  }
}
