# 浮窗播放器「下一首」無反應問題修復任務清單

> 建立日期：2025-11-04
> 問題描述：浮窗播放器點擊下一首有時沒反應，console 顯示「播放器 not ready」

## 📊 任務總覽

| 優先級 | 任務 | 預估時間 | 狀態 |
|--------|------|----------|------|
| P0 | 修復播放器狀態追蹤 | 30 分鐘 | ⏳ 待處理 |
| P0 | 添加防抖機制 | 20 分鐘 | ⏳ 待處理 |
| P0 | 優化等待邏輯 | 15 分鐘 | ⏳ 待處理 |
| P0 | 改進 next() 函數 | 20 分鐘 | ⏳ 待處理 |
| P1 | 統一 YouTube API 載入 | 45 分鐘 | ⏳ 待處理 |
| P1 | 改進狀態同步 | 30 分鐘 | ⏳ 待處理 |
| P2 | 添加錯誤恢復機制 | 30 分鐘 | ⏳ 待處理 |

## 🚀 階段一：緊急修復（P0 - 立即執行）

### Task 1: 修復播放器狀態追蹤
**檔案**: `frontend/src/components/FloatingPlayer.vue`
**行數**: 240-242

**現有問題**：
```javascript
let ytPlayer = null
let apiReady = false
let playerReady = false  // 使用 let，無法響應式追蹤
```

**修改為**：
```javascript
import { ref } from 'vue'

const ytPlayer = ref(null)
const apiReady = ref(false)
const playerReady = ref(false)  // 改為響應式狀態
```

**注意事項**：
- 需要更新所有使用這些變數的地方，從 `playerReady` 改為 `playerReady.value`
- 確保在 `onPlayerReady` 和 `onPlayerStateChange` 中正確更新狀態

---

### Task 2: 添加防抖機制
**檔案**: `frontend/src/components/FloatingPlayer.vue`
**行數**: 378-439

**實作步驟**：
1. 在元件頂部添加防抖計時器變數
```javascript
let videoChangeTimeout = null
let retryCount = 0
const MAX_RETRIES = 3
```

2. 修改 watch 函數
```javascript
watch(() => playerStore.currentVideo?.video_id, (newVideoId, oldVideoId) => {
  if (newVideoId && newVideoId !== oldVideoId) {
    // 清除之前的計時器
    if (videoChangeTimeout) {
      clearTimeout(videoChangeTimeout)
      videoChangeTimeout = null
    }

    // 重置重試計數
    retryCount = 0

    // 使用防抖處理影片切換
    videoChangeTimeout = setTimeout(() => {
      handleVideoChange(newVideoId)
    }, 100)  // 100ms 防抖延遲
  }
})
```

3. 抽取影片切換邏輯到獨立函數
```javascript
const handleVideoChange = (videoId) => {
  if (!videoId) return

  if (ytPlayer.value && playerReady.value) {
    // 播放器已就緒
    ytPlayer.value.loadVideoById(videoId)
    if (playerStore.isPlaying) {
      ytPlayer.value.playVideo()
    }
  } else if (ytPlayer.value && !playerReady.value && retryCount < MAX_RETRIES) {
    // 播放器存在但未就緒，重試
    console.log(`FloatingPlayer: 播放器未就緒，第 ${retryCount + 1} 次重試...`)
    retryCount++
    setTimeout(() => handleVideoChange(videoId), 300)  // 300ms 後重試
  } else {
    // 超過重試次數或播放器不存在，重新初始化
    console.log('FloatingPlayer: 重新初始化播放器')
    ytPlayer.value = null
    playerReady.value = false
    initPlayer(videoId)
  }
}
```

---

### Task 3: 優化等待邏輯
**檔案**: `frontend/src/components/FloatingPlayer.vue`
**行數**: 410-431

**修改內容**：
- 將等待時間從 1000ms 降至 300ms
- 添加重試次數限制（最多 3 次）
- 改進日誌訊息，顯示重試次數

**實作**：參見 Task 2 的 `handleVideoChange` 函數

---

### Task 4: 改進 next() 函數
**檔案**: `frontend/src/stores/globalPlayerStore.js`
**行數**: 53-92

**修改 next 函數**：
```javascript
const next = async () => {
  if (!hasPlaylist.value) return

  const playlistLength = currentPlaylist.value.items.length

  // 單曲循環模式
  if (loopMode.value === 'single') {
    currentVideo.value = { ...currentPlaylist.value.items[currentIndex.value] }
    // 延遲設置播放狀態
    await nextTick()
    isPlaying.value = true
    return
  }

  // 隨機播放邏輯
  let nextIndex
  if (isShuffled.value) {
    const availableIndices = Array.from({ length: playlistLength }, (_, i) => i)
      .filter(i => i !== currentIndex.value)
    nextIndex = availableIndices[Math.floor(Math.random() * availableIndices.length)]
  } else {
    // 順序播放
    nextIndex = currentIndex.value + 1
    if (nextIndex >= playlistLength) {
      if (loopMode.value === 'all') {
        nextIndex = 0
      } else {
        // 播放完畢
        isPlaying.value = false
        return
      }
    }
  }

  // 先暫停，避免狀態不一致
  isPlaying.value = false

  // 更新當前影片
  currentIndex.value = nextIndex
  currentVideo.value = currentPlaylist.value.items[nextIndex]

  // 使用 nextTick 確保 DOM 更新完成後再設置播放
  await nextTick()
  isPlaying.value = true
}
```

---

## 🔧 階段二：架構優化（P1 - 完成緊急修復後執行）

### Task 5: 統一 YouTube API 載入
**新檔案**: `frontend/src/services/youtubeApiService.js`

**實作內容**：
```javascript
// YouTube API 載入服務（單例模式）
class YouTubeApiService {
  constructor() {
    this.apiReady = false
    this.loadPromise = null
  }

  loadApi() {
    // 如果已經載入，返回現有 Promise
    if (this.loadPromise) {
      return this.loadPromise
    }

    // 如果 API 已就緒，直接返回
    if (this.apiReady && window.YT) {
      return Promise.resolve()
    }

    // 創建新的載入 Promise
    this.loadPromise = new Promise((resolve, reject) => {
      // 檢查是否已經載入
      if (window.YT && window.YT.Player) {
        this.apiReady = true
        resolve()
        return
      }

      // 設置全域回調
      window.onYouTubeIframeAPIReady = () => {
        this.apiReady = true
        resolve()
      }

      // 載入 API
      const tag = document.createElement('script')
      tag.src = 'https://www.youtube.com/iframe_api'
      tag.onerror = reject
      const firstScriptTag = document.getElementsByTagName('script')[0]
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag)
    })

    return this.loadPromise
  }

  isReady() {
    return this.apiReady && window.YT && window.YT.Player
  }
}

export default new YouTubeApiService()
```

**修改現有檔案**：
- 更新 `FloatingPlayer.vue` 和 `Home.vue`，使用統一的服務載入 API
- 移除重複的 API 載入程式碼

---

### Task 6: 改進狀態同步
**檔案**: `frontend/src/stores/globalPlayerStore.js`

**新增狀態**：
```javascript
const playerStatus = ref({
  state: 'UNINITIALIZED', // UNINITIALIZED, LOADING, READY, ERROR
  error: null,
  retryCount: 0
})

// 狀態更新函數
const updatePlayerStatus = (state, error = null) => {
  playerStatus.value = {
    state,
    error,
    retryCount: state === 'ERROR' ? playerStatus.value.retryCount + 1 : 0
  }
}

// 導出給元件使用
const isPlayerReady = computed(() => playerStatus.value.state === 'READY')
```

---

## 🛡️ 階段三：錯誤恢復機制（P2 - 選擇性實作）

### Task 7: 添加錯誤恢復機制
**檔案**: `frontend/src/components/FloatingPlayer.vue`

**實作內容**：
1. 添加錯誤處理和自動重試
2. 提供手動重新初始化按鈕
3. 顯示錯誤訊息給使用者

```javascript
// 錯誤處理
const handlePlayerError = (error) => {
  console.error('播放器錯誤:', error)

  // 更新狀態
  playerStore.updatePlayerStatus('ERROR', error.message)

  // 自動重試邏輯
  if (playerStore.playerStatus.retryCount < 3) {
    setTimeout(() => {
      console.log(`嘗試恢復播放器 (第 ${playerStore.playerStatus.retryCount + 1} 次)`)
      reinitializePlayer()
    }, 2000)
  } else {
    // 顯示錯誤訊息給使用者
    showErrorMessage('播放器載入失敗，請重新整理頁面或稍後再試')
  }
}

// 手動重新初始化
const reinitializePlayer = () => {
  ytPlayer.value = null
  playerReady.value = false
  apiReady.value = false
  initPlayer(playerStore.currentVideo?.video_id)
}
```

---

## 📝 測試檢查清單

完成修復後，請進行以下測試：

- [ ] **快速點擊測試**：連續快速點擊「下一首」10 次
- [ ] **播放器未就緒測試**：重新載入頁面後立即點擊「下一首」
- [ ] **網路延遲測試**：使用 Chrome DevTools 模擬慢速網路
- [ ] **播放清單切換測試**：快速切換不同播放清單
- [ ] **最小化/最大化測試**：在切換視窗狀態時測試播放控制
- [ ] **錯誤恢復測試**：斷網後恢復，檢查播放器是否自動恢復

## 🎯 預期成果

### 修復前問題：
- ❌ 點擊「下一首」有時無反應
- ❌ Console 顯示「播放器 not ready」
- ❌ 快速點擊造成播放器狀態混亂

### 修復後預期：
- ✅ 「下一首」按鈕 100% 有反應
- ✅ 無「播放器 not ready」錯誤訊息
- ✅ 快速操作不會造成問題
- ✅ 播放器狀態同步準確
- ✅ 錯誤時能自動恢復

## 📅 時程規劃

| 階段 | 預估時間 | 備註 |
|------|----------|------|
| 階段一（P0） | 1.5 小時 | 緊急修復，優先執行 |
| 階段二（P1） | 1.5 小時 | 架構優化，提升穩定性 |
| 階段三（P2） | 0.5 小時 | 選擇性實作 |
| 測試驗證 | 0.5 小時 | 完整測試所有場景 |
| **總計** | **4 小時** | |

---

*最後更新：2025-11-04*