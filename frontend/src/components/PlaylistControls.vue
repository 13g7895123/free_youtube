<template>
  <div class="playlist-controls" role="region" aria-label="播放清單控制">
    <div class="controls-info">
      <span class="play-status" aria-live="polite">{{ currentIndex + 1 }} / {{ totalItems }}</span>
      <span v-if="isPlaying" class="status-indicator">
        <PlayIcon class="status-icon" />
        播放中
      </span>
      <span v-else class="status-indicator paused">
        <PauseIcon class="status-icon" />
        已暫停
      </span>
    </div>

    <div class="controls-buttons">
      <button
        @click="$emit('prev')"
        class="btn-control"
        v-tooltip="'上一首'"
        aria-label="上一首"
      >
        <BackwardIcon class="icon" />
      </button>
      <button
        @click="$emit('play')"
        :class="['btn-control', 'btn-play', isPlaying ? 'playing' : '']"
        v-tooltip="isPlaying ? '暫停' : '播放'"
        :aria-label="isPlaying ? '暫停' : '播放'"
        :aria-pressed="isPlaying"
      >
        <PauseIcon v-if="isPlaying" class="icon" />
        <PlayIcon v-else class="icon" />
      </button>
      <button
        @click="$emit('next')"
        class="btn-control"
        v-tooltip="'下一首'"
        aria-label="下一首"
      >
        <ForwardIcon class="icon" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { PlayIcon, PauseIcon, BackwardIcon, ForwardIcon } from '@heroicons/vue/24/solid'

defineProps({
  currentIndex: {
    type: Number,
    required: true
  },
  totalItems: {
    type: Number,
    required: true
  },
  isPlaying: {
    type: Boolean,
    default: false
  }
})

defineEmits(['prev', 'next', 'play'])
</script>

<style scoped>
.playlist-controls {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  padding: var(--space-4);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  align-items: center;
}

.controls-info {
  display: flex;
  align-items: center;
  gap: var(--space-5);
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.play-status {
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-success);
  font-weight: var(--font-weight-medium);
  font-size: var(--font-size-sm);
}

.status-indicator .status-icon {
  width: var(--icon-sm);
  height: var(--icon-sm);
}

.status-indicator.paused {
  color: var(--color-warning);
}

.controls-buttons {
  display: flex;
  gap: var(--space-3);
  width: 100%;
  max-width: 400px;
  justify-content: center;
}

.btn-control {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
  min-height: var(--touch-target-comfortable);
  padding: var(--space-3);
  background: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  color: var(--text-primary);
}

.btn-control:hover {
  background: var(--color-neutral-100);
  border-color: var(--border-color-hover);
}

.btn-control:active {
  transform: scale(0.98);
}

.btn-control .icon {
  width: var(--icon-lg);
  height: var(--icon-lg);
}

.btn-play {
  background: var(--color-info);
  color: white;
  border-color: var(--color-info);
}

.btn-play:hover {
  background: var(--color-info-dark);
  border-color: var(--color-info-dark);
}

.btn-play.playing {
  background: var(--color-warning);
  border-color: var(--color-warning);
}

.btn-play.playing:hover {
  background: var(--color-warning-dark);
  border-color: var(--color-warning-dark);
}
</style>
