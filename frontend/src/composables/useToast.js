/**
 * Toast 通知系統 Composable
 * 提供簡潔的 API 來顯示通知訊息
 *
 * 使用方法：
 * import { useToast } from '@/composables/useToast'
 *
 * const toast = useToast()
 * toast.success('操作成功！')
 * toast.error('發生錯誤', '錯誤詳情...')
 */

export function useToast() {
  const showToast = (options) => {
    // 發送自定義事件給 Toast 組件
    const event = new CustomEvent('show-toast', { detail: options });
    window.dispatchEvent(event);
  };

  return {
    /**
     * 顯示通知
     * @param {string|object} options - 訊息文字或配置對象
     */
    show: (options) => {
      if (typeof options === 'string') {
        showToast({ message: options, type: 'info' });
      } else {
        showToast(options);
      }
    },

    /**
     * 顯示成功訊息
     * @param {string} message - 訊息內容
     * @param {string} [title] - 標題（可選）
     * @param {number} [duration=3000] - 顯示時長（毫秒）
     */
    success: (message, title, duration = 3000) => {
      showToast({ type: 'success', message, title, duration });
    },

    /**
     * 顯示錯誤訊息
     * @param {string} message - 訊息內容
     * @param {string} [title] - 標題（可選）
     * @param {number} [duration=5000] - 顯示時長（毫秒）
     */
    error: (message, title, duration = 5000) => {
      showToast({ type: 'error', message, title, duration });
    },

    /**
     * 顯示警告訊息
     * @param {string} message - 訊息內容
     * @param {string} [title] - 標題（可選）
     * @param {number} [duration=4000] - 顯示時長（毫秒）
     */
    warning: (message, title, duration = 4000) => {
      showToast({ type: 'warning', message, title, duration });
    },

    /**
     * 顯示資訊訊息
     * @param {string} message - 訊息內容
     * @param {string} [title] - 標題（可選）
     * @param {number} [duration=3000] - 顯示時長（毫秒）
     */
    info: (message, title, duration = 3000) => {
      showToast({ type: 'info', message, title, duration });
    }
  };
}

// 全局可用的 toast（適用於非 Vue 組件使用）
export const toast = {
  show: (options) => {
    if (window.$toast) {
      window.$toast.show(options);
    } else {
      const event = new CustomEvent('show-toast', { detail: typeof options === 'string' ? { message: options } : options });
      window.dispatchEvent(event);
    }
  },
  success: (message, title) => {
    toast.show({ type: 'success', message, title });
  },
  error: (message, title) => {
    toast.show({ type: 'error', message, title, duration: 5000 });
  },
  warning: (message, title) => {
    toast.show({ type: 'warning', message, title, duration: 4000 });
  },
  info: (message, title) => {
    toast.show({ type: 'info', message, title });
  }
};
