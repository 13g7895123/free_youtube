<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>{{ isEditing ? 'Edit Playlist' : 'Create Playlist' }}</h2>
        <button @click="closeModal" class="btn-close">✕</button>
      </div>

      <form @submit.prevent="savePlaylist" class="modal-form">
        <div class="form-group">
          <label for="name">Playlist Name *</label>
          <input
            id="name"
            v-model="formData.name"
            type="text"
            required
            placeholder="Enter playlist name"
          />
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea
            id="description"
            v-model="formData.description"
            placeholder="Enter playlist description (optional)"
            rows="4"
          ></textarea>
        </div>

        <div class="modal-actions">
          <button type="button" @click="closeModal" class="btn btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            {{ isEditing ? 'Update' : 'Create' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  playlist: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['save', 'close'])

const formData = ref({
  name: '',
  description: ''
})

const isEditing = computed(() => !!props.playlist)

watch(() => props.playlist, (newPlaylist) => {
  if (newPlaylist) {
    formData.value = {
      name: newPlaylist.name || '',
      description: newPlaylist.description || ''
    }
  } else {
    formData.value = {
      name: '',
      description: ''
    }
  }
}, { immediate: true })

const closeModal = () => {
  emit('close')
}

const savePlaylist = () => {
  if (!formData.value.name.trim()) {
    alert('Please enter a playlist name')
    return
  }
  emit('save', {
    name: formData.value.name,
    description: formData.value.description
  })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 500px;
  padding: 24px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-form {
  margin: 0;
}

/* form-group 和 form-input 樣式移除，使用全域樣式 */

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

/* 按鈕樣式移除，使用全域 .btn .btn-primary .btn-secondary */
</style>
