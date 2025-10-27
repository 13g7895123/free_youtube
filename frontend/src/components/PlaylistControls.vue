<template>
  <div class="playlist-controls">
    <div class="controls-info">
      <span class="play-status">{{ currentIndex + 1 }} / {{ totalItems }}</span>
      <span v-if="isPlaying" class="status-indicator">● Playing</span>
      <span v-else class="status-indicator paused">⏸ Paused</span>
    </div>

    <div class="controls-buttons">
      <button @click="$emit('prev')" class="btn-control" title="Previous">
        ⏮ Prev
      </button>
      <button @click="$emit('play')" :class="['btn-control', 'btn-play', isPlaying ? 'playing' : '']" title="Play/Pause">
        {{ isPlaying ? '⏸ Pause' : '▶ Play' }}
      </button>
      <button @click="$emit('next')" class="btn-control" title="Next">
        Next ⏭
      </button>
    </div>
  </div>
</template>

<script setup>
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
  gap: 16px;
  padding: 16px;
  background: #f9f9f9;
  border-radius: 4px;
  align-items: center;
}

.controls-info {
  display: flex;
  align-items: center;
  gap: 20px;
  font-size: 14px;
  color: #666;
}

.play-status {
  font-weight: 600;
  color: #333;
}

.status-indicator {
  color: #4caf50;
  font-weight: 500;
}

.status-indicator.paused {
  color: #ff9800;
}

.controls-buttons {
  display: flex;
  gap: 12px;
  width: 100%;
  max-width: 400px;
  justify-content: center;
}

.btn-control {
  flex: 1;
  padding: 10px 16px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-control:hover {
  background: #f0f0f0;
  border-color: #999;
}

.btn-play {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
}

.btn-play:hover {
  background: #1565c0;
  border-color: #1565c0;
}

.btn-play.playing {
  background: #ff9800;
  border-color: #ff9800;
}
</style>
