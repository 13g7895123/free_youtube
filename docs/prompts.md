1. ✅ FIXED - /api/playlists/1/items API 錯誤已修復

## 問題描述
POST /api/playlists/1/items 會返回 500 錯誤，且沒有錯誤訊息（返回空字串）。

## 根本原因
在 backend/app/Models/PlaylistItemModel.php 的 addVideo() 方法（第66行）：

```php
$maxPosition = $this->where('playlist_id', $playlistId)
                    ->selectMax('position')
                    ->first(); // 返回 Entity 物件

$position = ($maxPosition && $maxPosition['position']) ? ... // 錯誤：試圖以陣列方式存取物件
```

因為 Model 的 returnType 設定為 PlaylistItem Entity，first() 會返回物件而非陣列，但程式碼嘗試以陣列方式存取，導致錯誤：
`Error: Cannot use object of type App\Entities\PlaylistItem as array`

## 解決方案
在 selectMax() 後加上 asArray() 以返回陣列：

```php
$maxPosition = $this->where('playlist_id', $playlistId)
                    ->selectMax('position')
                    ->asArray()  // 新增此行
                    ->first();
```

## 測試結果
- ✅ POST /api/playlists/1/items 現在正常運作
- ✅ 影片成功加入播放清單
- ✅ position 自動遞增（0, 1, 2...）
- ✅ GET /api/playlists/1/items 正確返回所有項目