<template>
  <transition name="error-fade">
    <div
      v-if="message"
      class="error-message"
      role="alert"
      aria-live="assertive"
    >
      <div class="error-content">
        <!-- 錯誤圖示 -->
        <div class="error-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>

        <!-- 錯誤訊息 -->
        <div class="error-text">
          <p class="error-message-text">{{ message }}</p>
        </div>

        <!-- 關閉按鈕 -->
        <button
          v-if="dismissible"
          type="button"
          class="error-close"
          aria-label="關閉錯誤訊息"
          @click="handleClose"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup>
const props = defineProps({
  message: {
    type: String,
    default: ''
  },
  dismissible: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close'])

function handleClose() {
  emit('close')
}
</script>

<style scoped>
.error-message {
  width: 100%;
  max-width: 800px;
  margin: 1rem auto;
  padding: 1rem;
  background-color: #ffebee;
  border-left: 4px solid #d32f2f;
  border-radius: var(--radius-md);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.error-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.error-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  color: #d32f2f;
}

.error-icon svg {
  width: 100%;
  height: 100%;
}

.error-text {
  flex: 1;
  min-width: 0;
}

.error-message-text {
  margin: 0;
  font-size: 0.9375rem;
  line-height: 1.5;
  color: #c62828;
  word-wrap: break-word;
}

.error-close {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  padding: 0;
  background: none;
  border: none;
  color: #d32f2f;
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.error-close:hover {
  background-color: rgba(211, 47, 47, 0.1);
}

.error-close:active {
  transform: scale(0.95);
}

.error-close svg {
  width: 100%;
  height: 100%;
}

/* 過渡動畫 */
.error-fade-enter-active,
.error-fade-leave-active {
  transition: all 0.3s ease;
}

.error-fade-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.error-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* 響應式設計 */
@media (max-width: 640px) {
  .error-message {
    margin: 0.75rem;
    padding: 0.875rem;
  }

  .error-content {
    gap: 0.75rem;
  }

  .error-message-text {
    font-size: 0.875rem;
  }

  .error-icon,
  .error-close {
    width: 20px;
    height: 20px;
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .error-fade-enter-active,
  .error-fade-leave-active {
    transition: none;
  }

  .error-fade-enter-from,
  .error-fade-leave-to {
    transform: none;
  }
}
</style>
