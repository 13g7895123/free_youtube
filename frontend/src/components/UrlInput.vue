<template>
  <div class="url-input-container">
    <form @submit.prevent="handleSubmit" class="url-input-form">
      <div class="input-group">
        <input
          v-model="urlInput"
          type="text"
          class="url-input"
          placeholder="貼上 YouTube 影片或播放清單網址..."
          aria-label="YouTube 網址輸入"
          :disabled="isLoading"
        />
        <BaseButton
          variant="primary"
          type="submit"
          :disabled="!urlInput.trim()"
          :loading="isLoading"
          aria-label="載入影片"
        >
          播放
        </BaseButton>
      </div>
      <p v-if="validationError" class="validation-error" role="alert">
        {{ validationError }}
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BaseButton from '@/components/BaseButton.vue'

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false
  },
  validationError: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['submit'])

const urlInput = ref('')

function handleSubmit() {
  const url = urlInput.value.trim()
  if (url) {
    emit('submit', url)
  }
}
</script>

<style scoped>
.url-input-container {
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
}

.url-input-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-group {
  display: flex;
  gap: 0.5rem;
}

.url-input {
  flex: 1;
  /* 使用統一的 input 樣式，額外的自訂在這裡覆蓋 */
  font-size: var(--font-size-base);
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  transition: border-color 0.2s ease;
}

.url-input:focus {
  outline: none;
  border-color: #ff0000;
  box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.1);
}

.validation-error {
  margin: 0;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  color: #d32f2f;
  background-color: #ffebee;
  border-left: 4px solid #d32f2f;
  border-radius: var(--radius-sm);
}

@media (max-width: 640px) {
  .input-group {
    flex-direction: column;
  }

  .submit-button {
    width: 100%;
  }
}
</style>
