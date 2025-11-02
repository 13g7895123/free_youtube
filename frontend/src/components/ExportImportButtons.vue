<template>
  <div class="export-import-buttons">
    <BaseButton
      variant="success"
      :icon="ArrowUpTrayIcon"
      :disabled="!canExport"
      :loading="isExporting"
      :title="!canExport ? '沒有項目可以匯出' : ''"
      aria-label="匯出資料"
      @click="handleExport"
    >
      匯出
    </BaseButton>
    <BaseButton
      variant="info"
      :icon="ArrowDownTrayIcon"
      :loading="isImporting"
      aria-label="匯入資料"
      @click="triggerImport"
    >
      匯入
    </BaseButton>
    <input
      ref="fileInput"
      type="file"
      accept=".json"
      @change="handleImport"
      style="display: none"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BaseButton from '@/components/BaseButton.vue'
import { ArrowUpTrayIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  isExporting: {
    type: Boolean,
    default: false
  },
  isImporting: {
    type: Boolean,
    default: false
  },
  canExport: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['export', 'import'])

const fileInput = ref(null)

const handleExport = () => {
  emit('export')
}

const triggerImport = () => {
  fileInput.value.click()
}

const handleImport = (event) => {
  const file = event.target.files[0]
  if (file) {
    emit('import', file)
    // Reset the input so the same file can be selected again
    event.target.value = ''
  }
}
</script>

<style scoped>
.export-import-buttons {
  display: flex;
  gap: var(--space-2);
}
</style>
