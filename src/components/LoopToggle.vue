<template>
  <div class="loop-toggle-container">
    <button
      type="button"
      class="loop-toggle-button"
      :class="{ 'is-enabled': isEnabled }"
      :aria-pressed="isEnabled"
      aria-label="切換循環播放"
      @click="handleToggle"
    >
      <!-- 循環圖示 -->
      <svg
        class="loop-icon"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <polyline points="17 1 21 5 17 9"></polyline>
        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
        <polyline points="7 23 3 19 7 15"></polyline>
        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
      </svg>

      <span class="loop-text">
        {{ isEnabled ? '循環播放：開啟' : '循環播放：關閉' }}
      </span>

      <!-- 狀態指示器 -->
      <div class="toggle-indicator" :class="{ 'is-on': isEnabled }">
        <div class="toggle-slider"></div>
      </div>
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  isEnabled: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['toggle'])

function handleToggle() {
  emit('toggle', !props.isEnabled)
}
</script>

<style scoped>
.loop-toggle-container {
  display: flex;
  justify-content: center;
  margin: 1rem 0;
}

.loop-toggle-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  background-color: #ffffff;
  border: 2px solid #e0e0e0;
  border-radius: 50px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #616161;
}

.loop-toggle-button:hover {
  border-color: #ff0000;
  background-color: #fff5f5;
}

.loop-toggle-button.is-enabled {
  border-color: #ff0000;
  background-color: #fff5f5;
  color: #ff0000;
}

.loop-toggle-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.1);
}

.loop-toggle-button:active {
  transform: scale(0.98);
}

.loop-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.loop-text {
  flex-shrink: 0;
}

/* 切換指示器 */
.toggle-indicator {
  position: relative;
  width: 44px;
  height: 24px;
  background-color: #e0e0e0;
  border-radius: 12px;
  transition: background-color 0.2s ease;
  flex-shrink: 0;
}

.toggle-indicator.is-on {
  background-color: #ff0000;
}

.toggle-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background-color: white;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s ease;
}

.toggle-indicator.is-on .toggle-slider {
  transform: translateX(20px);
}

/* 響應式設計 */
@media (max-width: 640px) {
  .loop-toggle-button {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    gap: 0.625rem;
  }

  .loop-icon {
    width: 18px;
    height: 18px;
  }

  .toggle-indicator {
    width: 40px;
    height: 22px;
  }

  .toggle-slider {
    width: 18px;
    height: 18px;
  }

  .toggle-indicator.is-on .toggle-slider {
    transform: translateX(18px);
  }
}

/* 簡化版本（僅圖示） */
@media (max-width: 480px) {
  .loop-text {
    display: none;
  }

  .loop-toggle-button {
    padding: 0.625rem 1rem;
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .loop-toggle-button,
  .toggle-indicator,
  .toggle-slider {
    transition: none;
  }

  .loop-toggle-button:active {
    transform: none;
  }
}
</style>
