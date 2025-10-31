<template>
  <div class="loading-container" :class="sizeClass">
    <div class="spinner-wrapper">
      <!-- YouTube 風格的旋轉圖示 -->
      <svg class="spinner" viewBox="0 0 50 50">
        <circle
          class="spinner-track"
          cx="25"
          cy="25"
          r="20"
          fill="none"
          stroke-width="4"
        ></circle>
        <circle
          class="spinner-path"
          cx="25"
          cy="25"
          r="20"
          fill="none"
          stroke-width="4"
        ></circle>
      </svg>
      <div v-if="showIcon" class="spinner-icon">
        <PlayCircleIcon class="play-icon" />
      </div>
    </div>
    <p v-if="message" class="loading-message">{{ message }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { PlayCircleIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  message: {
    type: String,
    default: '載入中...'
  },
  showIcon: {
    type: Boolean,
    default: true
  }
})

const sizeClass = computed(() => `size-${props.size}`)
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-4);
  padding: var(--space-8);
}

/* Size variants */
.size-small {
  padding: var(--space-4);
  gap: var(--space-2);
}

.size-medium {
  padding: var(--space-8);
  gap: var(--space-4);
}

.size-large {
  padding: var(--space-12);
  gap: var(--space-6);
  min-height: 400px;
}

/* Spinner wrapper */
.spinner-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.size-small .spinner-wrapper {
  width: 32px;
  height: 32px;
}

.size-medium .spinner-wrapper {
  width: 48px;
  height: 48px;
}

.size-large .spinner-wrapper {
  width: 64px;
  height: 64px;
}

/* SVG Spinner */
.spinner {
  width: 100%;
  height: 100%;
  animation: rotate 2s linear infinite;
  transform-origin: center;
}

.spinner-track {
  stroke: var(--color-neutral-200);
}

.spinner-path {
  stroke: var(--color-brand-primary);
  stroke-linecap: round;
  stroke-dasharray: 90, 150;
  stroke-dashoffset: 0;
  animation: dash 1.5s ease-in-out infinite;
}

/* Center Icon */
.spinner-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.play-icon {
  color: var(--color-brand-primary);
  animation: pulse 2s ease-in-out infinite;
}

.size-small .play-icon {
  width: 16px;
  height: 16px;
}

.size-medium .play-icon {
  width: 24px;
  height: 24px;
}

.size-large .play-icon {
  width: 32px;
  height: 32px;
}

/* Loading message */
.loading-message {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  text-align: center;
}

.size-small .loading-message {
  font-size: var(--font-size-sm);
}

.size-large .loading-message {
  font-size: var(--font-size-lg);
}

/* Animations */
@keyframes rotate {
  100% {
    transform: rotate(360deg);
  }
}

@keyframes dash {
  0% {
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -35;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -124;
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.7;
    transform: translate(-50%, -50%) scale(0.95);
  }
}

/* 無障礙：減少動畫 */
@media (prefers-reduced-motion: reduce) {
  .spinner,
  .spinner-path,
  .play-icon {
    animation: none;
  }

  .spinner-path {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: 0;
  }
}
</style>
