✅ 1. 請檢查一下，/playlists這個路徑的新建清單沒有寫入後端資料庫，幫我同步確認所有功能，另外，item的查看項目按鈕點下去沒有反應 - 已修復

修復內容：
1. **後端資料庫寫入問題**：
   - backend/public/index.php 原本是 mock API，不會實際寫入資料庫
   - 改寫為使用 PDO 直接與 MariaDB 連接的完整實作
   - 所有 CRUD 操作 (playlists, videos, playlist_items) 現在都能正確寫入資料庫
   - 修復 SQL 查詢的 LIMIT/OFFSET 參數綁定問題

2. **查看項目按鈕無反應**：
   - frontend/src/views/PlaylistManager.vue:143-146
   - handleViewItems 函數原本只有 console.log
   - 改為導航到播放清單詳情頁面 `/playlists/${playlist.id}`

3. **驗證測試**：
   - POST /api/playlists 成功建立清單並寫入資料庫 ✓
   - GET /api/playlists 成功取得所有清單 ✓
   - GET /api/playlists/:id 成功取得單一清單詳情 ✓
   - 資料庫確認有實際記錄 ✓

---

✅ 2. 播放器中新增加入影片庫/播放清單功能 - 已完成

新增功能：
1. **取得影片資訊方法**：
   - frontend/src/composables/useYouTubePlayer.js:316-337
   - 新增 getCurrentVideoInfo() 方法，可取得當前播放影片的詳細資訊
   - 包含 videoId, title, author, duration, thumbnail, youtubeUrl

2. **SaveVideoActions 元件**：
   - frontend/src/components/SaveVideoActions.vue
   - 提供「加入影片庫」和「加入播放清單」按鈕
   - 加入影片庫：直接將影片儲存到資料庫
   - 加入播放清單：顯示播放清單選擇對話框
   - 自動檢查影片是否已存在，避免重複新增
   - 顯示操作成功/失敗的 Toast 訊息

3. **後端 API 端點**：
   - POST /api/videos/check：檢查影片是否已存在
   - GET /api/videos/search：搜尋影片
   - POST /api/videos：新增防重複檢查，回傳 409 錯誤碼

4. **整合到播放器**：
   - frontend/src/views/Home.vue:52-56
   - 在播放控制區域下方顯示儲存操作按鈕
   - 只有當播放器就緒且有影片時才顯示

功能測試 ✓：
- 加入影片庫功能正常運作
- 播放清單選擇對話框正常顯示
- 重複新增影片時正確提示
- 成功加入後顯示成功訊息

---

✅ 3. 修復播放清單詳情頁面影片顯示問題 - 已完成

問題：
- /playlists/:id 頁面無法正確顯示影片清單

修復內容：
1. **修正資料載入邏輯**：
   - frontend/src/views/PlaylistDetail.vue:92-105
   - 原本使用不存在的 `fetchPlaylist` 和 `fetchPlaylistItems` 方法
   - 改為使用 `getPlaylist` 方法，直接從回應中取得 items

2. **修正影片資料顯示**：
   - frontend/src/views/PlaylistDetail.vue:57-60
   - 原本使用 `item.video?.title` 和 `item.video?.duration`
   - API 回應的影片資訊是直接在 item 層級
   - 改為使用 `item.title` 和 `item.duration`

3. **修正移除影片功能**：
   - frontend/src/views/PlaylistDetail.vue:134-153
   - 修正參數傳遞，使用 `video_id` 而非 `id`
   - 使用正確的 store 方法 `removeItemFromPlaylist(playlistId, videoId)`
   - 正確更新本地 items 陣列和 playlist.item_count

測試結果 ✓：
- 播放清單詳情頁面正確顯示影片清單
- 影片標題和時長正確顯示
- 移除影片功能正常運作

---

✅ 4. 實作浮動播放器功能 - 已完成

問題：
- PlaylistDetail 頁面點擊播放沒有功能
- 需要在切換頁面時保持播放，顯示浮窗播放器

實作內容：
1. **全局播放器狀態管理**：
   - frontend/src/stores/globalPlayerStore.js
   - 建立全局播放器 store，管理播放狀態
   - 包含 playVideo, playPlaylist, play, pause, togglePlay, next, previous 等方法
   - 支援單一影片和播放清單播放

2. **浮動播放器元件**：
   - frontend/src/components/FloatingPlayer.vue
   - 使用 Teleport 渲染到 body 層級
   - 兩種顯示模式：
     - 最小化模式：顯示縮圖、標題和基本控制按鈕（350px 寬度）
     - 展開模式：顯示完整 YouTube 播放器和控制按鈕（480px 寬度）
   - 整合 YouTube IFrame API
   - 支援播放清單導航（上一首/下一首）
   - 固定在右下角顯示

3. **整合到應用程式**：
   - frontend/src/App.vue:17
   - 在主應用程式中加入 FloatingPlayer 元件
   - 使元件在所有頁面都可用

4. **整合到播放清單詳情頁面**：
   - frontend/src/views/PlaylistDetail.vue:79,84,109-120
   - 匯入並使用 useGlobalPlayerStore
   - selectVideo 函數調用 globalPlayerStore.playPlaylist()
   - playNext, playPrevious, togglePlayback 函數同步使用全局 store
   - 確保播放狀態在頁面間保持一致

功能特點 ✓：
- 點擊播放清單中的影片時，啟動全局播放器
- 切換到其他頁面時，播放器持續顯示在右下角
- 支援最小化/展開切換
- 完整的播放控制（播放/暫停、上一首/下一首）
- 播放清單循環播放功能

5. **修復問題**：
   - 修復 CSS 定位問題：將 bottom/right 移到 .floating-player-container
   - 修復播放器關閉後重開錯誤：添加 isVisible watcher 自動銷毀/重建播放器
   - 修復 initPlayer 函數：添加 DOM 檢查確保播放器正確附加
   - 整合影片庫播放功能：frontend/src/views/VideoLibrary.vue:83,88,117-128
   - 移除調試元素和測試代碼

---

✅ 5. 整合影片庫播放功能 - 已完成

問題：
- /library 頁面點擊播放按鈕沒有反應

修復內容：
- frontend/src/views/VideoLibrary.vue:83,88,117-128
- 引入 useGlobalPlayerStore
- 修改 handlePlayVideo 函數調用 globalPlayerStore.playVideo()
- 傳遞完整的影片資訊給全局播放器

測試結果 ✓：
- 影片庫頁面點擊播放按鈕正常啟動浮動播放器
- 播放器顯示在右下角並開始播放
- 可在影片庫瀏覽時持續播放