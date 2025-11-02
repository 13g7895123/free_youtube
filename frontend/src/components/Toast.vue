<template>
  <Teleport to="body">
    <div class="toast-container" role="region" aria-label="通知訊息">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['toast', `toast-${toast.type}`]"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        >
          <div class="toast-icon">
            <CheckCircleIcon v-if="toast.type === 'success'" class="icon" />
            <ExclamationCircleIcon v-else-if="toast.type === 'error'" class="icon" />
            <InformationCircleIcon v-else-if="toast.type === 'info'" class="icon" />
            <ExclamationTriangleIcon v-else-if="toast.type === 'warning'" class="icon" />
          </div>
          <div class="toast-content">
            <div v-if="toast.title" class="toast-title">{{ toast.title }}</div>
            <div class="toast-message">{{ toast.message }}</div>
          </div>
          <button
            class="toast-close"
            @click="removeToast(toast.id)"
            aria-label="關閉通知"
          >
            <XMarkIcon class="icon-close" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';

const toasts = ref([]);
let toastId = 0;

const addToast = (toast) => {
  const id = toastId++;
  const duration = toast.duration || 3000;

  toasts.value.push({
    id,
    type: toast.type || 'info',
    title: toast.title,
    message: toast.message,
    duration
  });

  // 自動移除
  if (duration > 0) {
    setTimeout(() => {
      removeToast(id);
    }, duration);
  }

  return id;
};

const removeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index !== -1) {
    toasts.value.splice(index, 1);
  }
};

// 暴露方法給 composable 使用
const show = (options) => {
  if (typeof options === 'string') {
    return addToast({ message: options });
  }
  return addToast(options);
};

const success = (message, title) => {
  return addToast({ type: 'success', message, title });
};

const error = (message, title) => {
  return addToast({ type: 'error', message, title });
};

const warning = (message, title) => {
  return addToast({ type: 'warning', message, title });
};

const info = (message, title) => {
  return addToast({ type: 'info', message, title });
};

// 監聽自定義事件
const handleToastEvent = (event) => {
  const { detail } = event;
  if (detail) {
    addToast(detail);
  }
};

onMounted(() => {
  window.addEventListener('show-toast', handleToastEvent);

  // 將方法掛載到 window 以便全局使用（可選）
  window.$toast = { show, success, error, warning, info, remove: removeToast };
});

onUnmounted(() => {
  window.removeEventListener('show-toast', handleToastEvent);
  if (window.$toast) {
    delete window.$toast;
  }
});

defineExpose({ show, success, error, warning, info, removeToast });
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: var(--space-6);
  right: var(--space-6);
  z-index: var(--z-modal);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  max-width: 400px;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border-left: 4px solid;
  pointer-events: auto;
  min-width: 300px;
  max-width: 100%;
}

.toast-success {
  border-left-color: var(--color-success);
}

.toast-error {
  border-left-color: var(--color-error);
}

.toast-warning {
  border-left-color: var(--color-warning);
}

.toast-info {
  border-left-color: var(--color-info);
}

.toast-icon {
  flex-shrink: 0;
  width: var(--icon-lg);
  height: var(--icon-lg);
}

.toast-success .icon {
  color: var(--color-success);
}

.toast-error .icon {
  color: var(--color-error);
}

.toast-warning .icon {
  color: var(--color-warning);
}

.toast-info .icon {
  color: var(--color-info);
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-sm);
  color: var(--text-primary);
  margin-bottom: var(--space-1);
}

.toast-message {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: var(--line-height-normal);
  word-break: break-word;
}

.toast-close {
  flex-shrink: 0;
  padding: var(--space-1);
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-tertiary);
  transition: color var(--transition-fast);
  border-radius: var(--radius-sm);
}

.toast-close:hover {
  color: var(--text-primary);
  background: var(--color-neutral-100);
}

.icon-close {
  width: var(--icon-md);
  height: var(--icon-md);
}

/* Toast 動畫 */
.toast-enter-active,
.toast-leave-active {
  transition: all var(--transition-base);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(50%) scale(0.8);
}

.toast-move {
  transition: transform var(--transition-base);
}

/* 響應式 */
@media (max-width: 768px) {
  .toast-container {
    top: var(--space-4);
    right: var(--space-4);
    left: var(--space-4);
    max-width: none;
  }

  .toast {
    min-width: 0;
  }
}
</style>
