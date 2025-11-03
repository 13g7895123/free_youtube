<?php

namespace App\Controllers;

use App\Models\PlaylistModel;
use App\Models\PlaylistItemModel;
use App\Models\VideoLibraryModel;
use CodeIgniter\HTTP\ResponseInterface;

class Playlists extends BaseController
{
    protected $playlistModel;
    protected $playlistItemModel;
    protected $videoLibraryModel;

    public function __construct()
    {
        $this->playlistModel = new PlaylistModel();
        $this->playlistItemModel = new PlaylistItemModel();
        $this->videoLibraryModel = new VideoLibraryModel();
    }

    /**
     * 取得所有播放清單
     *
     * @return ResponseInterface
     */
    public function index()
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        // 取得會員的所有播放清單
        $playlists = $this->playlistModel->getUserPlaylists($userId);

        return $this->respond([
            'success' => true,
            'data' => $playlists
        ]);
    }

    /**
     * 建立播放清單
     *
     * @return ResponseInterface
     */
    public function create()
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        // 取得請求資料
        $name = $this->request->getJSON(true)['name'] ?? '';
        $description = $this->request->getJSON(true)['description'] ?? '';

        if (!$name) {
            return $this->fail('缺少播放清單名稱', 400);
        }

        // 檢查名稱是否重複
        $exists = $this->playlistModel
            ->where('user_id', $userId)
            ->where('name', $name)
            ->first();

        if ($exists) {
            return $this->fail('播放清單名稱已存在', 409);
        }

        // 建立播放清單
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'description' => $description
        ];

        $inserted = $this->playlistModel->insert($data);

        if (!$inserted) {
            return $this->fail('建立播放清單失敗', 500);
        }

        // 取得剛建立的播放清單
        $playlist = $this->playlistModel->find($inserted);

        return $this->respondCreated([
            'success' => true,
            'message' => '播放清單已建立',
            'data' => $playlist
        ]);
    }

    /**
     * 取得播放清單詳情（包含項目）
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$id) {
            return $this->fail('缺少播放清單 ID', 400);
        }

        // 驗證播放清單是否屬於該會員
        $playlist = $this->playlistModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$playlist) {
            return $this->fail('找不到該播放清單或無權限存取', 404);
        }

        // 取得播放清單項目及完整的影片資訊
        $items = $this->playlistItemModel->getPlaylistVideos($id);

        // 使用物件屬性而非陣列語法，並從資料庫實際計算數量
        $playlist->items = $items;
        $playlist->item_count = count($items);

        return $this->respond([
            'success' => true,
            'data' => $playlist
        ]);
    }

    /**
     * 更新播放清單
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$id) {
            return $this->fail('缺少播放清單 ID', 400);
        }

        // 驗證播放清單是否屬於該會員
        $playlist = $this->playlistModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$playlist) {
            return $this->fail('找不到該播放清單或無權限修改', 404);
        }

        // 取得更新資料
        $name = $this->request->getJSON(true)['name'] ?? null;
        $description = $this->request->getJSON(true)['description'] ?? null;

        $updateData = [];

        if ($name !== null) {
            // 檢查名稱是否與其他播放清單重複
            $exists = $this->playlistModel
                ->where('user_id', $userId)
                ->where('name', $name)
                ->where('id !=', $id)
                ->first();

            if ($exists) {
                return $this->fail('播放清單名稱已存在', 409);
            }

            $updateData['name'] = $name;
        }

        if ($description !== null) {
            $updateData['description'] = $description;
        }

        if (empty($updateData)) {
            return $this->fail('沒有要更新的資料', 400);
        }

        // 更新播放清單
        $updated = $this->playlistModel->update($id, $updateData);

        if (!$updated) {
            return $this->fail('更新播放清單失敗', 500);
        }

        // 取得更新後的播放清單
        $playlist = $this->playlistModel->find($id);

        return $this->respond([
            'success' => true,
            'message' => '播放清單已更新',
            'data' => $playlist
        ]);
    }

    /**
     * 刪除播放清單（CASCADE 刪除項目）
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$id) {
            return $this->fail('缺少播放清單 ID', 400);
        }

        // 驗證播放清單是否屬於該會員
        $playlist = $this->playlistModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$playlist) {
            return $this->fail('找不到該播放清單或無權限刪除', 404);
        }

        // 刪除播放清單（會 CASCADE 刪除所有項目）
        $deleted = $this->playlistModel->delete($id);

        if (!$deleted) {
            return $this->fail('刪除播放清單失敗', 500);
        }

        return $this->respond([
            'success' => true,
            'message' => '播放清單已刪除'
        ]);
    }

    /**
     * 新增影片到播放清單
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function addItem($id = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$id) {
            return $this->fail('缺少播放清單 ID', 400);
        }

        // 驗證播放清單是否屬於該會員
        $playlist = $this->playlistModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$playlist) {
            return $this->fail('找不到該播放清單或無權限新增', 404);
        }

        // 取得請求資料
        $videoId = $this->request->getJSON(true)['video_id'] ?? null;

        if (!$videoId) {
            return $this->fail('缺少 video_id 參數', 400);
        }

        // 檢查會員總影片數上限（10000）
        // 使用 countAllResults(false) 避免重置查詢建構器
        $totalVideos = $this->videoLibraryModel->where('user_id', $userId)->countAllResults(false);
        $totalPlaylistItems = $this->playlistItemModel
            ->join('playlists', 'playlists.id = playlist_items.playlist_id')
            ->where('playlists.user_id', $userId)
            ->countAllResults(false);

        if (($totalVideos + $totalPlaylistItems) >= 10000) {
            return $this->fail('影片總數已達上限（10000 部）', 400);
        }

        // 檢查是否已存在
        $exists = $this->playlistItemModel
            ->where('playlist_id', $id)
            ->where('video_id', $videoId)
            ->first();

        if ($exists) {
            return $this->fail('影片已在播放清單中', 409);
        }

        // 取得當前最大 position
        $maxPosition = $this->playlistItemModel
            ->selectMax('position')
            ->where('playlist_id', $id)
            ->asArray()
            ->first();

        $position = ($maxPosition['position'] ?? -1) + 1;

        // 新增項目
        $data = [
            'playlist_id' => $id,
            'video_id' => $videoId,
            'position' => $position
        ];

        $inserted = $this->playlistItemModel->insert($data);

        if (!$inserted) {
            return $this->fail('新增影片失敗', 500);
        }

        // 更新播放清單項目計數
        $this->playlistModel->updateItemCount($id);

        // 取得剛新增的項目
        $item = $this->playlistItemModel->find($inserted);

        return $this->respondCreated([
            'success' => true,
            'message' => '影片已新增到播放清單',
            'data' => $item
        ]);
    }

    /**
     * 從播放清單移除項目（調整 position）
     *
     * @param int $playlistId
     * @param int $itemId
     * @return ResponseInterface
     */
    public function removeItem($playlistId = null, $itemId = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$playlistId || !$itemId) {
            return $this->fail('缺少參數', 400);
        }

        // 驗證播放清單是否屬於該會員
        $playlist = $this->playlistModel
            ->where('id', $playlistId)
            ->where('user_id', $userId)
            ->first();

        if (!$playlist) {
            return $this->fail('找不到該播放清單或無權限移除', 404);
        }

        // 驗證項目是否存在
        $item = $this->playlistItemModel
            ->where('id', $itemId)
            ->where('playlist_id', $playlistId)
            ->first();

        if (!$item) {
            return $this->fail('找不到該項目', 404);
        }

        $removedPosition = $item->position;

        // 刪除項目
        $deleted = $this->playlistItemModel->delete($itemId);

        if (!$deleted) {
            return $this->fail('移除項目失敗', 500);
        }

        // 調整後續項目的 position
        $this->playlistItemModel
            ->where('playlist_id', $playlistId)
            ->where('position >', $removedPosition)
            ->set('position', 'position - 1', false)
            ->update();

        // 更新播放清單項目計數
        $this->playlistModel->updateItemCount($playlistId);

        return $this->respond([
            'success' => true,
            'message' => '項目已移除'
        ]);
    }

    /**
     * 重新排序播放清單
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function reorder($id = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$id) {
            return $this->fail('缺少播放清單 ID', 400);
        }

        // 驗證播放清單是否屬於該會員
        $playlist = $this->playlistModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$playlist) {
            return $this->fail('找不到該播放清單或無權限重新排序', 404);
        }

        // 取得新的順序（項目 ID 陣列）
        $itemIds = $this->request->getJSON(true)['item_ids'] ?? [];

        if (!is_array($itemIds) || empty($itemIds)) {
            return $this->fail('缺少 item_ids 參數', 400);
        }

        // 批次更新 position
        foreach ($itemIds as $position => $itemId) {
            $this->playlistItemModel
                ->where('id', $itemId)
                ->where('playlist_id', $id)
                ->set('position', $position)
                ->update();
        }

        return $this->respond([
            'success' => true,
            'message' => '播放清單已重新排序'
        ]);
    }
}
