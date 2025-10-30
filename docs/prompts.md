1. 幫我於播放清單的浮窗，加入兩顆按鈕，一顆是循環播放，預設清單，每點一下會有不同模式，清單循環、單曲循環，另一顆是隨機播放的按鈕，點下去的話下一首歌會隨機選擇，選擇清單中非目前這首歌以外的歌曲，目前兩顆按鈕點下去都沒有反應，循環播放要顯示目前是甚麼循環
2. 幫我加入匯出匯入的按鈕，可以匯出影片庫的資料與播放清單的資料，且匯入要可以用
3. 幫我建立cicd flow，用github action
4. 幫我在deploy.sh檔案中，執行後端部屬的時候，先確認沒有被git追蹤的檔案與資料夾，例如vendor、writable、.env，.env看是不是加入從.env.example複製，另外兩個如果要composer安裝就安裝，writable應該可以直接mkdir，幫我完整看過一遍再進行調整修正
5. 承4，📦 Step 1: Installing/updating frontend dependencies...
./deploy.sh: line 143: npm: command not found
⚠️  npm ci failed, trying npm install...
./deploy.sh: line 145: npm: command not found
6. 為甚麼CICD會出現以下錯誤，前端的docker我不是改22版的node了嗎
npm warn EBADENGINE Unsupported engine {
npm warn EBADENGINE   package: 'whatwg-url@15.1.0',
npm warn EBADENGINE   required: { node: '>=20' },
npm warn EBADENGINE   current: { node: 'v18.20.8', npm: '10.8.2' }
npm warn EBADENGINE }
7. 你幫我另外寫好了，針對正式環境，完整寫一個docker與部署相關的部分，目前的部分我在wsl還可以用，就不要調整了