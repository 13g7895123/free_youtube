<template>
  <button
    :type="type"
    :class="buttonClasses"
    :disabled="disabled || loading"
    :aria-label="ariaLabel"
    @click="handleClick"
  >
    <component
      v-if="icon && iconPosition === 'left'"
      :is="icon"
      class="icon"
    />
    <span v-if="loading" class="loading-spinner"></span>
    <span v-if="$slots.default" :class="{ 'sr-only': iconOnly }">
      <slot></slot>
    </span>
    <component
      v-if="icon && iconPosition === 'right'"
      :is="icon"
      class="icon"
    />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // 按鈕類型
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => [
      'primary',
      'secondary',
      'success',
      'danger',
      'info',
      'warning',
      'outline-primary',
      'outline-secondary',
      'ghost'
    ].includes(value)
  },
  // 按鈕尺寸
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['sm', 'default', 'lg'].includes(value)
  },
  // 是否為圖示按鈕（僅圖示，無文字）
  iconOnly: {
    type: Boolean,
    default: false
  },
  // 圖示組件
  icon: {
    type: Object,
    default: null
  },
  // 圖示位置
  iconPosition: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'right'].includes(value)
  },
  // 是否全寬
  block: {
    type: Boolean,
    default: false
  },
  // 是否禁用
  disabled: {
    type: Boolean,
    default: false
  },
  // 是否載入中
  loading: {
    type: Boolean,
    default: false
  },
  // HTML button type
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value)
  },
  // ARIA label
  ariaLabel: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
  const classes = ['btn']

  // 變體樣式
  classes.push(`btn-${props.variant}`)

  // 尺寸
  if (props.size !== 'default') {
    classes.push(`btn-${props.size}`)
  }

  // 圖示按鈕
  if (props.iconOnly) {
    classes.push('btn-icon')
  }

  // 全寬
  if (props.block) {
    classes.push('btn-block')
  }

  return classes
})

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
/* 載入中的旋轉動畫 */
.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: currentColor;
  animation: spin 0.6s linear infinite;
  margin-right: var(--space-2);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* 當按鈕只有圖示時隱藏文字 */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
