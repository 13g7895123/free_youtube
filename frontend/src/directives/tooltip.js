/**
 * Tooltip 指令
 * 為元素添加懸停提示功能
 *
 * 使用方法：
 * <button v-tooltip="'這是提示文字'">按鈕</button>
 * 或
 * <button v-tooltip="{ text: '提示', position: 'top' }">按鈕</button>
 */

export default {
  mounted(el, binding) {
    // 獲取 tooltip 文字和配置
    let text, position = 'top';

    if (typeof binding.value === 'string') {
      text = binding.value;
    } else if (typeof binding.value === 'object') {
      text = binding.value.text || binding.value.content;
      position = binding.value.position || 'top';
    }

    if (!text) return;

    // 設置 data 屬性
    el.setAttribute('data-tooltip', text);
    el.setAttribute('data-tooltip-position', position);
    el.classList.add('has-tooltip');

    // 為無障礙添加 aria-label（如果元素沒有的話）
    if (!el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby')) {
      el.setAttribute('aria-label', text);
    }
  },

  updated(el, binding) {
    let text, position = 'top';

    if (typeof binding.value === 'string') {
      text = binding.value;
    } else if (typeof binding.value === 'object') {
      text = binding.value.text || binding.value.content;
      position = binding.value.position || 'top';
    }

    if (text) {
      el.setAttribute('data-tooltip', text);
      el.setAttribute('data-tooltip-position', position);

      if (!el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby')) {
        el.setAttribute('aria-label', text);
      }
    }
  },

  unmounted(el) {
    el.removeAttribute('data-tooltip');
    el.removeAttribute('data-tooltip-position');
    el.classList.remove('has-tooltip');
  }
};
