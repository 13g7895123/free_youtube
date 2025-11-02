# 按鈕與表單樣式指南

## 📋 目錄
1. [按鈕樣式](#按鈕樣式)
2. [表單輸入樣式](#表單輸入樣式)
3. [使用範例](#使用範例)
4. [自訂指南](#自訂指南)

---

## 按鈕樣式

### 基本按鈕類別

所有按鈕都使用 `.btn` 基礎類別，搭配變體類別：

#### 主要按鈕變體

```html
<!-- Primary - 主要操作（紅色） -->
<button class="btn btn-primary">主要按鈕</button>

<!-- Secondary - 次要操作（灰色） -->
<button class="btn btn-secondary">次要按鈕</button>

<!-- Success - 成功/確認操作（綠色） -->
<button class="btn btn-success">成功按鈕</button>

<!-- Danger - 危險/刪除操作（紅色） -->
<button class="btn btn-danger">危險按鈕</button>

<!-- Info - 資訊操作（藍色） -->
<button class="btn btn-info">資訊按鈕</button>
```

#### Outline 變體（透明背景，有邊框）

```html
<button class="btn btn-outline-primary">Outline Primary</button>
<button class="btn btn-outline-secondary">Outline Secondary</button>
```

#### Ghost 變體（完全透明）

```html
<button class="btn btn-ghost">Ghost 按鈕</button>
```

### 按鈕尺寸

```html
<!-- 小尺寸 -->
<button class="btn btn-primary btn-sm">小按鈕</button>

<!-- 標準尺寸（預設） -->
<button class="btn btn-primary">標準按鈕</button>

<!-- 大尺寸 -->
<button class="btn btn-primary btn-lg">大按鈕</button>

<!-- 全寬按鈕 -->
<button class="btn btn-primary btn-block">全寬按鈕</button>
```

### 圖示按鈕

```html
<!-- 帶圖示的按鈕 -->
<button class="btn btn-primary">
  <PlusIcon class="icon" />
  <span>新增</span>
</button>

<!-- 只有圖示（無文字） -->
<button class="btn btn-icon btn-primary">
  <PlusIcon class="icon" />
</button>
```

### 按鈕狀態

```html
<!-- 禁用狀態 -->
<button class="btn btn-primary" disabled>禁用按鈕</button>

<!-- 載入中狀態（需要自訂實作） -->
<button class="btn btn-primary" disabled>
  <LoadingIcon class="icon" />
  <span>處理中...</span>
</button>
```

---

## 表單輸入樣式

### 基本輸入框

所有表單輸入框自動套用統一樣式：

```html
<!-- 文字輸入 -->
<input type="text" placeholder="輸入文字" />

<!-- 數字輸入 -->
<input type="number" placeholder="輸入數字" />

<!-- 電子郵件 -->
<input type="email" placeholder="輸入信箱" />

<!-- 網址 -->
<input type="url" placeholder="輸入網址" />

<!-- 搜尋 -->
<input type="search" placeholder="搜尋..." />

<!-- 多行文字 -->
<textarea placeholder="輸入多行文字"></textarea>

<!-- 下拉選單 -->
<select>
  <option>選項 1</option>
  <option>選項 2</option>
</select>
```

### 特點

✅ **自動套用的樣式**：
- 灰色邊框（`1px solid var(--border-color)`）
- Hover 時邊框變深
- Focus 時紅色邊框 + 外圈光暈
- 圓角 8px
- 統一內邊距
- 禁用狀態自動變灰

### 表單群組

```html
<div class="form-group">
  <label>標籤文字</label>
  <input type="text" placeholder="輸入內容" />
  <span class="form-hint">這是提示文字</span>
</div>

<div class="form-group">
  <label>錯誤範例</label>
  <input type="text" class="error" />
  <span class="form-error">這是錯誤訊息</span>
</div>
```

### 帶圖示的輸入框

```html
<div class="input-group">
  <MagnifyingGlassIcon class="input-icon" />
  <input type="search" placeholder="搜尋..." />
</div>
```

### Checkbox 和 Radio

```html
<label>
  <input type="checkbox" />
  我同意條款
</label>

<label>
  <input type="radio" name="option" />
  選項 1
</label>
```

---

## 使用範例

### 實際應用案例

#### 1. 匯入匯出按鈕組

```vue
<div class="export-import-buttons">
  <button class="btn btn-success" @click="handleExport">
    <ArrowUpTrayIcon class="icon" />
    <span>匯出</span>
  </button>
  <button class="btn btn-info" @click="handleImport">
    <ArrowDownTrayIcon class="icon" />
    <span>匯入</span>
  </button>
</div>
```

```css
.export-import-buttons {
  display: flex;
  gap: var(--space-2);
}
```

#### 2. 表單提交

```vue
<form @submit.prevent="handleSubmit">
  <div class="form-group">
    <label>影片網址</label>
    <input type="url" v-model="url" placeholder="貼上 YouTube 網址" />
  </div>

  <div class="form-actions">
    <button type="button" class="btn btn-secondary" @click="cancel">
      取消
    </button>
    <button type="submit" class="btn btn-primary">
      確認
    </button>
  </div>
</form>
```

#### 3. Modal 對話框按鈕

```vue
<div class="modal-footer">
  <button class="btn btn-secondary" @click="close">
    取消
  </button>
  <button class="btn btn-danger" @click="confirm">
    刪除
  </button>
</div>
```

---

## 自訂指南

### 何時需要自訂樣式？

✅ **這些情況使用全域樣式**：
- 標準按鈕操作
- 表單輸入
- Modal 對話框
- 通用 UI 元件

❌ **這些情況可能需要自訂**：
- 特殊的視覺效果（如播放控制按鈕）
- 動畫效果
- 懸浮視窗
- 品牌特色元件

### 如何覆蓋樣式？

```vue
<style scoped>
/* 在組件內覆蓋特定屬性 */
.btn-custom {
  /* 繼承 .btn 的基礎樣式 */
  border-radius: 50%; /* 只覆蓋圓角 */
}
</style>
```

### 最佳實踐

1. **優先使用全域樣式類別**
   - 保持一致性
   - 減少程式碼重複
   - 方便維護

2. **組合使用類別**
   ```html
   <button class="btn btn-primary btn-lg">
   ```

3. **使用 Design Tokens**
   ```css
   padding: var(--space-3);
   color: var(--text-primary);
   border-radius: var(--radius-md);
   ```

4. **避免硬編碼顏色值**
   ```css
   /* ❌ 不好 */
   background: #ff0000;

   /* ✅ 好 */
   background: var(--color-brand-primary);
   ```

---

## 樣式變數參考

### 常用 Design Tokens

```css
/* 顏色 - 按鈕使用 -light 變體作為預設色 */
--color-brand-primary-light: #FF5252; /* 按鈕預設 */
--color-brand-primary: #FF0000; /* 按鈕 hover */

--color-success-light: #81C784; /* 按鈕預設 */
--color-success: #4CAF50; /* 按鈕 hover */

--color-error-light: #E57373; /* 按鈕預設 */
--color-error: #F44336; /* 按鈕 hover */

--color-info-light: #64B5F6; /* 按鈕預設 */
--color-info: #2196F3; /* 按鈕 hover */

/* 間距 */
--space-2: 8px;
--space-3: 12px;
--space-4: 16px;

/* 圓角 */
--radius-sm: 4px;
--radius-md: 8px;
--radius-lg: 12px;

/* 圖示尺寸 */
--icon-sm: 16px;
--icon-md: 20px;
--icon-lg: 24px;

/* 邊框 */
--border-color: #E0E0E0;
--border-color-hover: #BDBDBD;
```

---

## 無障礙考量

所有按鈕和表單元件都包含：

✅ Focus 可見指示器
✅ 適當的觸控目標大小（最小 44px）
✅ 鍵盤導航支援
✅ 減少動畫模式支援
✅ 高對比度文字
✅ 語意化 HTML

---

## 疑難排解

### Q: 按鈕樣式沒有生效？
**A:** 確認 `button-styles.css` 已在 `style.css` 中導入。

### Q: 需要不同的按鈕顏色？
**A:** 使用現有的變體類別（primary, success, danger, info），或在 Design Tokens 中新增。

### Q: Input 框沒有灰色邊框？
**A:** 確認 input 的 type 屬性正確，統一樣式會自動套用。

### Q: 如何製作圓形按鈕？
**A:** 使用 `.btn-icon` 類別，並在自訂樣式中加入 `border-radius: 50%`。

---

## 更新日誌

- **2025-01-XX**: 初始版本，統一所有按鈕和表單樣式
- 建立全域 `button-styles.css`
- 移除各組件中的重複樣式
- 統一 input 灰色邊框樣式
