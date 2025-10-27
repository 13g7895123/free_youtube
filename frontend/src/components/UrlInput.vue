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
        <button
          type="submit"
          class="submit-button"
          :disabled="!urlInput.trim() || isLoading"
          aria-label="載入影片"
        >
          {{ isLoading ? '載入中...' : '播放' }}
        </button>
      </div>
      <p v-if="validationError" class="validation-error" role="alert">
        {{ validationError }}
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'

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
  padding: 0.75rem 1rem;
  font-size: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.url-input:focus {
  border-color: #ff0000;
  box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.1);
}

.url-input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.submit-button {
  padding: 0.75rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  color: white;
  background-color: #ff0000;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
  white-space: nowrap;
}

.submit-button:hover:not(:disabled) {
  background-color: #cc0000;
}

.submit-button:active:not(:disabled) {
  transform: scale(0.98);
}

.submit-button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.validation-error {
  margin: 0;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  color: #d32f2f;
  background-color: #ffebee;
  border-left: 4px solid #d32f2f;
  border-radius: 4px;
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
