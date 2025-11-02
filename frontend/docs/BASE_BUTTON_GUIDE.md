# BaseButton 元件使用指南

## 簡介

`BaseButton` 是一個可重複使用的按鈕元件，封裝了所有按鈕樣式和常用功能，提供一致的 API 和使用體驗。

## 基本使用

```vue
<script setup>
import BaseButton from '@/components/BaseButton.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <BaseButton @click="handleClick">
    點擊我
  </BaseButton>
</template>
```

## Props

### variant（按鈕變體）

定義按鈕的視覺樣式：

```vue
<!-- 主要按鈕（紅色） -->
<BaseButton variant="primary">主要操作</BaseButton>

<!-- 次要按鈕（灰色） -->
<BaseButton variant="secondary">次要操作</BaseButton>

<!-- 成功按鈕（綠色） -->
<BaseButton variant="success">確認</BaseButton>

<!-- 危險按鈕（紅色） -->
<BaseButton variant="danger">刪除</BaseButton>

<!-- 資訊按鈕（藍色） -->
<BaseButton variant="info">詳細資訊</BaseButton>

<!-- Outline 變體 -->
<BaseButton variant="outline-primary">Outline</BaseButton>

<!-- Ghost 變體 -->
<BaseButton variant="ghost">Ghost</BaseButton>
```

### size（尺寸）

```vue
<!-- 小尺寸 -->
<BaseButton size="sm">小按鈕</BaseButton>

<!-- 預設尺寸 -->
<BaseButton>標準按鈕</BaseButton>

<!-- 大尺寸 -->
<BaseButton size="lg">大按鈕</BaseButton>
```

### icon（圖示）

```vue
<script setup>
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <!-- 左側圖示（預設） -->
  <BaseButton :icon="PlusIcon">
    新增
  </BaseButton>

  <!-- 右側圖示 -->
  <BaseButton :icon="TrashIcon" icon-position="right">
    刪除
  </BaseButton>

  <!-- 僅圖示 -->
  <BaseButton :icon="PlusIcon" icon-only aria-label="新增項目" />
</template>
```

### loading（載入中）

```vue
<BaseButton :loading="isLoading" @click="handleSubmit">
  提交
</BaseButton>
```

當 `loading` 為 `true` 時：
- 按鈕會顯示旋轉的載入動畫
- 自動禁用點擊事件
- 保持原有的文字和圖示

### disabled（禁用）

```vue
<BaseButton :disabled="!isValid">
  提交表單
</BaseButton>
```

### block（全寬）

```vue
<BaseButton block>
  全寬按鈕
</BaseButton>
```

### type（HTML 類型）

```vue
<!-- 表單提交 -->
<BaseButton type="submit">提交</BaseButton>

<!-- 重置表單 -->
<BaseButton type="reset">重置</BaseButton>

<!-- 一般按鈕（預設） -->
<BaseButton type="button">按鈕</BaseButton>
```

### ariaLabel（無障礙標籤）

```vue
<BaseButton
  :icon="TrashIcon"
  icon-only
  aria-label="刪除此項目"
/>
```

## 完整範例

### 表單提交按鈕

```vue
<script setup>
import { ref } from 'vue'
import BaseButton from '@/components/BaseButton.vue'

const isSubmitting = ref(false)

const handleSubmit = async () => {
  isSubmitting.value = true
  try {
    await submitForm()
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div class="form-actions">
      <BaseButton
        variant="secondary"
        type="button"
        @click="cancel"
      >
        取消
      </BaseButton>
      <BaseButton
        variant="primary"
        type="submit"
        :loading="isSubmitting"
      >
        提交
      </BaseButton>
    </div>
  </form>
</template>
```

### 帶圖示的操作按鈕

```vue
<script setup>
import BaseButton from '@/components/BaseButton.vue'
import { PlusIcon, TrashIcon, PencilIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <div class="actions">
    <BaseButton
      variant="primary"
      :icon="PlusIcon"
      @click="handleAdd"
    >
      新增
    </BaseButton>

    <BaseButton
      variant="secondary"
      :icon="PencilIcon"
      icon-only
      aria-label="編輯"
      @click="handleEdit"
    />

    <BaseButton
      variant="danger"
      :icon="TrashIcon"
      icon-only
      aria-label="刪除"
      @click="handleDelete"
    />
  </div>
</template>
```

### 全寬按鈕（行動版）

```vue
<template>
  <div class="mobile-actions">
    <BaseButton
      variant="primary"
      block
      size="lg"
      @click="handleContinue"
    >
      繼續
    </BaseButton>
  </div>
</template>

<style scoped>
@media (max-width: 640px) {
  .mobile-actions {
    padding: var(--space-4);
  }
}
</style>
```

## 對比：使用前 vs 使用後

### ❌ 使用前（手動管理樣式）

```vue
<button
  type="submit"
  class="btn btn-primary"
  :disabled="isLoading || !isValid"
  @click="handleClick"
>
  <PlusIcon v-if="!isLoading" class="icon" />
  <span v-if="isLoading" class="loading">載入中...</span>
  <span v-else>提交</span>
</button>
```

### ✅ 使用後（使用 BaseButton）

```vue
<BaseButton
  type="submit"
  variant="primary"
  :icon="PlusIcon"
  :loading="isLoading"
  :disabled="!isValid"
  @click="handleClick"
>
  提交
</BaseButton>
```

## 優點

1. **一致性**：所有按鈕使用相同的 API 和樣式
2. **可維護性**：集中管理按鈕邏輯，修改更容易
3. **簡潔性**：減少重複程式碼
4. **功能完整**：內建 loading、disabled、icon 等常用功能
5. **無障礙**：自動處理 ARIA 屬性
6. **型別安全**：Props 帶有驗證器

## 最佳實踐

1. **始終使用 BaseButton**：新的按鈕應該使用 BaseButton 而非原生 `<button>`
2. **提供 ariaLabel**：圖示按鈕務必提供 `aria-label`
3. **使用語義化 variant**：根據操作類型選擇正確的變體
4. **善用 loading 狀態**：非同步操作時啟用 loading
5. **合理使用 disabled**：在表單驗證中使用 disabled 提供即時回饋

## 遷移指南

如需將現有的 `<button>` 遷移至 `BaseButton`：

1. 將 `class="btn btn-primary"` 改為 `variant="primary"`
2. 將圖示從 template 移至 `:icon` prop
3. 將載入中邏輯改為 `:loading` prop
4. 確保添加適當的 `aria-label`（針對圖示按鈕）

## 疑難排解

### Q: 如何自訂按鈕顏色？
**A:** 在 `design-tokens.css` 中修改對應的顏色變數，所有按鈕會自動更新。

### Q: 可以混用 BaseButton 和原生 button 嗎？
**A:** 可以，但建議統一使用 BaseButton 以保持一致性。

### Q: 如何添加自訂樣式？
**A:** 使用 CSS class 覆蓋：
```vue
<BaseButton class="custom-button">按鈕</BaseButton>

<style scoped>
.custom-button {
  /* 自訂樣式 */
}
</style>
```
