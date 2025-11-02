<template>
  <div class="playlist-card">
    <div class="card-header">
      <h3 class="playlist-title">{{ playlist.name }}</h3>
      <div class="card-actions">
        <button @click="editPlaylist" class="btn-icon" title="Edit">
          ✎
        </button>
        <button @click="deletePlaylist" class="btn-icon btn-delete" title="Delete">
          ✕
        </button>
      </div>
    </div>

    <div class="card-body">
      <p class="playlist-description">{{ playlist.description || 'No description' }}</p>
      <div class="playlist-meta">
        <span class="video-count">
          {{ playlist.videos_count || 0 }} video(s)
        </span>
        <span class="created-date">
          {{ formatDate(playlist.created_at) }}
        </span>
      </div>
    </div>

    <div class="card-footer">
      <button @click="viewPlaylist" class="btn btn-primary">
        View →
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  playlist: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['edit', 'delete', 'view'])

const editPlaylist = () => {
  emit('edit', props.playlist)
}

const deletePlaylist = () => {
  if (confirm(`Delete playlist "${props.playlist.name}"?`)) {
    emit('delete', props.playlist.id)
  }
}

const viewPlaylist = () => {
  emit('view', props.playlist)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}
</script>

<style scoped>
.playlist-card {
  background: white;
  border: 1px solid #ddd;
  border-radius: var(--radius-md);
  padding: 16px;
  margin-bottom: 12px;
  transition: box-shadow 0.2s;
}

.playlist-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 12px;
}

.playlist-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.card-actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: #666;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  transition: background 0.2s;
}

.btn-icon:hover {
  background: #f0f0f0;
}

.btn-icon.btn-delete:hover {
  background: #fee;
  color: #d32f2f;
}

.card-body {
  margin-bottom: 12px;
}

.playlist-description {
  margin: 0 0 8px 0;
  color: #666;
  font-size: 14px;
}

.playlist-meta {
  display: flex;
  gap: 16px;
  font-size: 12px;
  color: #999;
}

.card-footer {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.btn-primary {
  background: #1976d2;
  color: white;
}

.btn-primary:hover {
  background: #1565c0;
}
</style>
