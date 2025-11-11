# Git 回退與功能重新套用操作指南

## 執行日期
2025-11-11

## 背景說明
在開發「所有影片」播放清單功能時，需要將程式碼回退到特定 commit（172027b），然後從遠端拉取最新代碼，再重新套用剛開發的功能。

## 執行步驟

### 1. 保存當前修改為 Patch 檔案

```bash
# 生成從 172027b 到 HEAD 的差異檔案
git diff 172027b HEAD > /tmp/fetchAllVideos.patch
```

**目的：** 保存剛才開發的 `fetchAllVideos` 功能修改，以便後續重新套用。

**Patch 內容包含：**
- `frontend/src/stores/videoStore.js` - 新增 `fetchAllVideos()` 函數
- `frontend/src/views/PlaylistManager.vue` - 更新為使用 `fetchAllVideos()`
- `frontend/src/views/PlaylistDetail.vue` - 更新為使用 `fetchAllVideos()`

### 2. 回退到目標 Commit

```bash
# 強制回退到 172027b（會丟棄當前的 commit）
git reset --hard 172027b
```

**結果：**
```
HEAD is now at 172027b feat: 改善 FloatingPlayer 組件的顯示邏輯，新增顯示模式切換功能，並優化嵌入模式的 DOM 結構
```

**注意：** 使用 `--hard` 會丟失本地未 commit 的修改和未推送的 commit（1736d5e）。

### 3. 從遠端拉取最新代碼

```bash
# 拉取遠端 master 分支的最新代碼
git pull origin master
```

**結果：**
```
Updating 172027b..356102f
Fast-forward
 frontend/src/components/FloatingPlayer.vue | 101 +---------
 frontend/src/stores/globalPlayerStore.js   |  15 --
 frontend/src/views/Home.vue                | 310 ++++++++++++++++++-----------
 3 files changed, 205 insertions(+), 221 deletions(-)
```

**拉取的變更：**
- `356102f` - Revert "回退到 commit 293680e 的版本"
- 修改了 FloatingPlayer.vue、globalPlayerStore.js、Home.vue

### 4. 重新套用功能修改

```bash
# 套用之前保存的 patch 檔案
git apply /tmp/fetchAllVideos.patch
```

**結果：** 成功套用，無衝突。

**修改的檔案狀態：**
```
Changes not staged for commit:
	modified:   frontend/src/stores/videoStore.js
	modified:   frontend/src/views/PlaylistDetail.vue
	modified:   frontend/src/views/PlaylistManager.vue
```

### 5. 驗證修改內容

使用 `git diff` 確認修改內容正確：

```bash
git diff frontend/src/stores/videoStore.js
git diff frontend/src/views/PlaylistManager.vue
git diff frontend/src/views/PlaylistDetail.vue
```

## 功能說明：fetchAllVideos

### 問題描述
原本「所有影片」播放清單只會載入影片庫的第一頁（預設 20 筆），導致使用者有超過 20 部影片時，其餘影片無法被載入。

### 解決方案
新增 `fetchAllVideos()` 函數，會循環請求所有分頁，直到獲取完整的影片庫。

### 修改細節

#### 1. videoStore.js - 新增 fetchAllVideos() 函數

```javascript
/**
 * 獲取所有影片（不限於單頁）
 * 會循環請求所有頁面直到獲取完整的影片庫
 */
const fetchAllVideos = async () => {
  loading.value = true
  error.value = null
  let allVideos = []
  let page = 1

  try {
    // 循環請求直到獲取所有頁面
    while (true) {
      const response = await videoService.getVideos(page, perPage.value)
      const pageVideos = response.data.data

      // 如果當前頁沒有資料，代表已經到最後一頁
      if (!pageVideos || pageVideos.length === 0) {
        break
      }

      allVideos = [...allVideos, ...pageVideos]

      // 檢查是否還有下一頁
      const totalCount = response.data.pagination?.total || 0
      const totalPages = Math.ceil(totalCount / perPage.value)

      if (page >= totalPages) {
        break
      }

      page++
    }

    videos.value = allVideos
    total.value = allVideos.length
    currentPage.value = 1 // 重置為第一頁
  } catch (err) {
    error.value = err.message || '無法載入所有影片'
    console.error('Error fetching all videos:', err)
  } finally {
    loading.value = false
  }
}
```

並在 return 區塊中導出：
```javascript
return {
  // ... 其他導出
  fetchVideos,
  fetchAllVideos,  // 新增
  // ... 其他導出
}
```

#### 2. PlaylistManager.vue - 更新為使用 fetchAllVideos()

**修改位置 1：** 第 310 行（handlePlayPlaylist 函數中）

```javascript
// 修改前
await videoStore.fetchVideos()

// 修改後
await videoStore.fetchAllVideos()
```

**修改位置 2：** 第 410 行（onMounted 生命週期中）

```javascript
// 修改前
onMounted(async () => {
  await fetchPlaylists()
  // 載入影片數據以顯示「所有影片」播放清單
  if (!videoStore.videos || videoStore.videos.length === 0) {
    await videoStore.fetchVideos()  // ❌ 只載入第一頁
  }
})

// 修改後
onMounted(async () => {
  await fetchPlaylists()
  // 載入所有影片數據以顯示「所有影片」播放清單
  if (!videoStore.videos || videoStore.videos.length === 0) {
    await videoStore.fetchAllVideos()  // ✅ 載入所有頁面
  }
})
```

> **重要：** 這個修改是關鍵！onMounted 時載入的影片數量會直接影響「所有影片」播放清單顯示的 item_count。之前這裡只載入 20 筆，導致即使有 100+ 影片，顯示數量也只有 20。

#### 3. PlaylistDetail.vue - 更新為使用 fetchAllVideos()

**修改位置：** 第 174 行

```javascript
// 修改前
await videoStore.fetchVideos()

// 修改後
await videoStore.fetchAllVideos()
```

## 後端 API 支援確認

後端已完整支援此功能，無需修改：

- **API 端點：** `GET /api/videos`
- **支援參數：**
  - `page` - 頁碼（預設 1）
  - `per_page` - 每頁筆數（預設 20）
- **回應格式：**
  ```json
  {
    "data": [...],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
  ```

## 測試建議

1. 確保影片庫有超過 20 部影片
2. 開啟播放清單頁面（`/playlists`）
3. 點擊「所有影片」播放清單
4. 確認所有影片都被正確載入和顯示
5. 播放「所有影片」清單，確認所有影片都能被播放

## 注意事項

1. **Commit 狀態：** 依照使用者要求，修改完成後並未自動 commit
2. **丟失的 Commit：** 原本的 `1736d5e` commit 已被 `git reset --hard` 移除
3. **Lint 檢查：** ESLint 配置有問題，但不影響代碼功能
4. **無衝突：** Patch 套用過程完全無衝突

## 相關檔案

- `/tmp/fetchAllVideos.patch` - 保存的修改檔案
- `frontend/src/stores/videoStore.js`
- `frontend/src/views/PlaylistManager.vue`
- `frontend/src/views/PlaylistDetail.vue`
- `backend/app/Controllers/Api/VideoController.php` - 後端 API（無需修改）

## Git 歷史

```
* 356102f Revert "回退到 commit 293680e 的版本" (HEAD, origin/master)
* 172027b feat: 改善 FloatingPlayer 組件的顯示邏輯
* 594798f fix: 更新錯誤訊息處理邏輯
* bb2245d feat: 支援根據路由變化自動切換播放器顯示模式
* 6e28b03 feat: 添加路由守衛以處理離開頁面時的播放器狀態轉移至懸浮視窗
```

## 追加修正 #1（2025-11-11）

### 問題發現
在初次實作後發現，「所有影片」播放清單的數量顯示仍然只有 20，並非所有影片庫的總數。

### 根本原因
問題出在 `PlaylistManager.vue` 的 `onMounted` 生命週期函數中（第 410 行），這裡使用的是 `fetchVideos()` 而不是 `fetchAllVideos()`。

**流程說明：**
1. 使用者進入 `/playlists` 頁面
2. `onMounted` 觸發，呼叫 `fetchVideos()` → 只載入 20 筆影片到 videoStore
3. 「所有影片」播放清單的 `item_count` 計算自 `videoStore.videos?.length` → 顯示為 20
4. 即使點擊播放時有正確使用 `fetchAllVideos()`，但顯示的數量已經固定為 20

### 修正方式
將 `onMounted` 中的 `fetchVideos()` 改為 `fetchAllVideos()`：

```javascript
// frontend/src/views/PlaylistManager.vue 第 410 行
onMounted(async () => {
  await fetchPlaylists()
  if (!videoStore.videos || videoStore.videos.length === 0) {
    await videoStore.fetchAllVideos()  // ✅ 改用這個
  }
})
```

### 影響範圍
這個修正確保：
- ✅ 進入播放清單頁面時，就會載入所有影片
- ✅ 「所有影片」的數量顯示正確（例如 100 而不是 20）
- ✅ 點擊播放時不需要再重新載入（因為已經有完整資料）

### API 請求變化
- **修正前：** 進入頁面 → 請求 `/api/videos?page=1&per_page=20` → 只有 20 筆
- **修正後：** 進入頁面 → 循環請求所有頁面 → 獲得完整資料

---

## 追加修正 #2（2025-11-11）

### 問題發現
修正 #1 完成後，發現當使用者從播放清單頁面切換到影片庫頁面再回來時，「所有影片」的數量又會變回 20。

### 根本原因
問題在於 `videoStore.videos` 被多個頁面共用：

**問題流程：**
1. 進入 `/playlists` → `fetchAllVideos()` → `videoStore.videos` = 100 筆 ✅
2. 切換到 `/library` → `fetchVideos(1)` → `videoStore.videos` = 20 筆（第一頁）❌
3. 切回 `/playlists` → `allVideosPlaylist.item_count` = `videoStore.videos.length` = 20 ❌

**衝突原因：**
- `PlaylistManager.vue` 需要所有影片（用於顯示總數）
- `VideoLibrary.vue` 需要分頁影片（用於瀏覽和分頁）
- 兩者共用同一個 `videoStore.videos`，導致互相覆蓋

### 解決方案
採用**方案 B：videoStore 維護兩個獨立狀態**

在 `videoStore` 中新增 `allVideos` 狀態，與原有的 `videos` 分開管理：
- `videos`：分頁影片資料（供 VideoLibrary 使用）
- `allVideos`：所有影片資料（供 PlaylistManager 使用）

### 修改細節

#### 1. videoStore.js - 新增 allVideos 狀態

```javascript
// State
const videos = ref([]) // 分頁影片（用於 VideoLibrary）
const allVideos = ref([]) // 所有影片（用於播放清單）
```

#### 2. videoStore.js - 修改 fetchAllVideos 儲存位置

```javascript
const fetchAllVideos = async () => {
  // ... 循環請求邏輯 ...

  // 儲存到 allVideos，不影響 videos
  allVideos.value = tempAllVideos  // ✅ 改為儲存到 allVideos
}
```

並在 return 中導出：
```javascript
return {
  videos,
  allVideos,  // ✅ 新增導出
  // ... 其他導出
}
```

#### 3. PlaylistManager.vue - 使用 allVideos

**三處修改：**

1. `allVideosPlaylist` computed（第 241 行）：
```javascript
const allVideosPlaylist = computed(() => ({
  // ...
  item_count: videoStore.allVideos?.length || 0,  // ✅ 改用 allVideos
}))
```

2. `allPlaylists` computed（第 249 行）：
```javascript
const allPlaylists = computed(() => {
  const hasVideos = videoStore.allVideos && videoStore.allVideos.length > 0  // ✅
  // ...
})
```

3. `handlePlayPlaylist` 函數（第 309-314 行）：
```javascript
if (playlist.id === 'all-videos') {
  if (!videoStore.allVideos || videoStore.allVideos.length === 0) {  // ✅
    await videoStore.fetchAllVideos()
  }
  const items = (videoStore.allVideos || []).map(...)  // ✅
}
```

4. `onMounted` 生命週期（第 409 行）：
```javascript
if (!videoStore.allVideos || videoStore.allVideos.length === 0) {  // ✅
  await videoStore.fetchAllVideos()
}
```

#### 4. PlaylistDetail.vue - 使用 allVideos

**兩處修改：**

1. playlist.value（第 180 行）：
```javascript
playlist.value = {
  // ...
  item_count: videoStore.allVideos?.length || 0,  // ✅
}
```

2. items.value（第 187 行）：
```javascript
items.value = (videoStore.allVideos || []).map(...)  // ✅
```

### 修正效果

**現在的資料流：**
- `/playlists` 頁面 → 使用 `videoStore.allVideos`（完整資料）
- `/library` 頁面 → 使用 `videoStore.videos`（分頁資料）
- 兩者互不干擾 ✅

**測試場景：**
1. 進入 `/playlists` → 顯示 100 筆 ✅
2. 切換到 `/library` → 顯示第 1 頁（20 筆）✅
3. 切回 `/playlists` → 仍然顯示 100 筆 ✅
4. 在 `/library` 切換到第 2 頁 → 顯示第 2 頁（20 筆）✅
5. 再切回 `/playlists` → 仍然顯示 100 筆 ✅

### 優勢
- ✅ 資料不會互相覆蓋
- ✅ 各頁面功能獨立運作
- ✅ 無需重複請求 API
- ✅ 程式碼清晰易維護

## 總結

成功完成以下操作：
- ✅ 保存修改為 patch 檔案
- ✅ 回退到指定 commit（172027b）
- ✅ 拉取遠端最新代碼（356102f）
- ✅ 重新套用 fetchAllVideos 功能
- ✅ 修正 #1：onMounted 中的 API 調用
- ✅ 修正 #2：實作 videos/allVideos 雙狀態系統
- ✅ 驗證修改內容正確

### 最終修改檔案
- `frontend/src/stores/videoStore.js` - 新增 allVideos 狀態和修改 fetchAllVideos
- `frontend/src/views/PlaylistManager.vue` - 改用 allVideos（4 處修改）
- `frontend/src/views/PlaylistDetail.vue` - 改用 allVideos（2 處修改）

所有步驟執行順利，無衝突，功能已準備好進行測試和 commit。

### 測試檢查清單
- [ ] 進入播放清單頁面，確認「所有影片」數量正確
- [ ] 切換到影片庫頁面，確認分頁功能正常
- [ ] 切回播放清單頁面，確認「所有影片」數量不變
- [ ] 點擊播放「所有影片」，確認所有影片都能播放
- [ ] 在影片庫切換頁面，確認不影響播放清單
