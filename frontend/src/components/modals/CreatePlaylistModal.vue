<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>{{ isEditing ? 'Edit Playlist' : 'Create Playlist' }}</h2>
        <BaseButton
          variant="ghost"
          icon-only
          aria-label="關閉"
          @click="closeModal"
        >
          ✕
        </BaseButton>
      </div>

      <form @submit.prevent="savePlaylist" class="modal-form">
        <div class="form-group">
          <label for="name">播放清單名稱 *</label>
          <input
            id="name"
            v-model="formData.name"
            type="text"
            placeholder="請輸入播放清單名稱"
            :class="{ 'input-error': touched.name && errors.name }"
            @blur="handleNameBlur"
            @input="handleNameInput"
          />
          <span v-if="touched.name && errors.name" class="error-message">
            {{ errors.name }}
          </span>
        </div>

        <div class="form-group">
          <label for="description">描述（選填）</label>
          <textarea
            id="description"
            v-model="formData.description"
            placeholder="請輸入播放清單描述"
            rows="4"
          ></textarea>
        </div>

        <div class="modal-actions">
          <BaseButton variant="secondary" type="button" @click="closeModal">
            取消
          </BaseButton>
          <BaseButton variant="primary" type="submit" :disabled="!isFormValid">
            {{ isEditing ? '更新' : '建立' }}
          </BaseButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseButton from '@/components/BaseButton.vue'

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

const errors = ref({
  name: ''
})

const touched = ref({
  name: false
})

const isEditing = computed(() => !!props.playlist)

const isFormValid = computed(() => {
  return formData.value.name.trim().length > 0
})

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

const validateName = () => {
  if (!formData.value.name.trim()) {
    errors.value.name = '播放清單名稱為必填'
    return false
  }
  errors.value.name = ''
  return true
}

const handleNameBlur = () => {
  touched.value.name = true
  validateName()
}

const handleNameInput = () => {
  if (touched.value.name) {
    validateName()
  }
}

const closeModal = () => {
  // 重置表單
  formData.value = { name: '', description: '' }
  errors.value = { name: '' }
  touched.value = { name: false }
  emit('close')
}

const savePlaylist = () => {
  // 標記所有欄位為已觸碰
  touched.value.name = true

  // 驗證
  if (!validateName()) {
    return
  }

  emit('save', {
    name: formData.value.name.trim(),
    description: formData.value.description.trim()
  })

  // 重置表單
  formData.value = { name: '', description: '' }
  errors.value = { name: '' }
  touched.value = { name: false }
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
  border-radius: var(--radius-md);
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

/* 驗證錯誤樣式 */
.input-error {
  border-color: var(--color-error) !important;
}

.input-error:focus {
  box-shadow: 0 0 0 3px var(--color-error-alpha) !important;
}

.error-message {
  display: block;
  margin-top: var(--space-1);
  font-size: var(--font-size-xs);
  color: var(--color-error);
  font-weight: var(--font-weight-medium);
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

/* 按鈕樣式移除，使用全域 .btn .btn-primary .btn-secondary */
</style>
