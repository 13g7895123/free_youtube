<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\PlaylistItemModel;
use App\Models\PlaylistModel;
use App\Models\VideoModel;

class PlaylistItemController extends ResourceController
{
    use ResponseTrait;

    protected $modelName = PlaylistItemModel::class;
    protected $format    = 'json';

    /**
     * GET /api/playlists/:playlist_id/items
     * 取得播放清單中的項目及影片詳情
     */
    public function getItems($playlistId = null)
    {
        try {
            if (!$playlistId) {
                return $this->fail('缺少播放清單 ID', 400);
            }

            // 驗證播放清單是否存在
            $playlistModel = new PlaylistModel();
            $playlist = $playlistModel->find($playlistId);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            $items = $this->model->getPlaylistVideos($playlistId);

            return $this->respond(api_success($items, '取得成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/playlists/:playlist_id/items
     * 新增影片到播放清單
     */
    public function addItem($playlistId = null)
    {
        try {
            if (!$playlistId) {
                return $this->fail('缺少播放清單 ID', 400);
            }

            $data = $this->request->getJSON(true);

            if (empty($data['video_id'])) {
                return $this->fail('缺少 video_id', 422);
            }

            // 驗證播放清單是否存在
            $playlistModel = new PlaylistModel();
            $playlist = $playlistModel->find($playlistId);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            // 驗證影片是否存在
            $videoModel = new VideoModel();
            $video = $videoModel->find($data['video_id']);

            if (!$video) {
                return $this->failNotFound('影片不存在');
            }

            // 檢查影片是否已在播放清單中
            if ($this->model->isVideoInPlaylist($playlistId, $data['video_id'])) {
                return $this->fail('該影片已在播放清單中', 409);
            }

            // 新增項目
            $this->model->addVideo($playlistId, $data['video_id']);

            // 更新播放清單項目計數
            $playlistModel->updateItemCount($playlistId);

            $items = $this->model->getPlaylistVideos($playlistId);

            return $this->respondCreated(api_success($items, '新增成功'), 201);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/playlists/:playlist_id/items/reorder
     * 重新排序播放清單中的項目
     */
    public function reorder($playlistId = null)
    {
        try {
            if (!$playlistId) {
                return $this->fail('缺少播放清單 ID', 400);
            }

            $data = $this->request->getJSON(true);

            if (empty($data['items']) || !is_array($data['items'])) {
                return $this->fail('缺少 items 陣列或格式不正確', 422);
            }

            // 驗證播放清單是否存在
            $playlistModel = new PlaylistModel();
            $playlist = $playlistModel->find($playlistId);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            // 重新排序
            $this->model->reorderItems($playlistId, $data['items']);

            $items = $this->model->getPlaylistVideos($playlistId);

            return $this->respond(api_success($items, '排序成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/playlists/:playlist_id/items/:video_id
     * 從播放清單中移除影片
     */
    public function removeItem($playlistId = null, $videoId = null)
    {
        try {
            if (!$playlistId || !$videoId) {
                return $this->fail('缺少播放清單 ID 或影片 ID', 400);
            }

            // 驗證播放清單是否存在
            $playlistModel = new PlaylistModel();
            $playlist = $playlistModel->find($playlistId);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            // 驗證項目是否存在
            if (!$this->model->isVideoInPlaylist($playlistId, $videoId)) {
                return $this->failNotFound('該影片不在播放清單中');
            }

            // 移除項目
            $this->model->removeVideo($playlistId, $videoId);

            // 更新播放清單項目計數
            $playlistModel->updateItemCount($playlistId);

            return $this->respond(api_success(null, '移除成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/playlists/:playlist_id/items/:item_id/position
     * T070: 更新單一項目的位置
     */
    public function updatePosition($playlistId = null, $itemId = null)
    {
        try {
            if (!$playlistId || !$itemId) {
                return $this->fail('缺少播放清單 ID 或項目 ID', 400);
            }

            $data = $this->request->getJSON(true);

            if (!isset($data['position']) && $data['position'] !== 0) {
                return $this->fail('缺少 position 參數', 422);
            }

            // 驗證播放清單是否存在
            $playlistModel = new PlaylistModel();
            $playlist = $playlistModel->find($playlistId);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            // 驗證項目是否存在
            $item = $this->model->find($itemId);
            if (!$item || $item['playlist_id'] != $playlistId) {
                return $this->failNotFound('項目不存在或不屬於該播放清單');
            }

            // 更新位置
            $this->model->update($itemId, ['position' => $data['position']]);

            $items = $this->model->getPlaylistVideos($playlistId);

            return $this->respond(api_success($items, '位置更新成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
