<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\VideoModel;

class VideoController extends ResourceController
{
    use ResponseTrait;

    protected $modelName = VideoModel::class;
    protected $format    = 'json';
    protected $helpers   = ['response'];

    public function __construct()
    {
        helper('response');
    }

    /**
     * GET /api/videos
     * 取得使用者的影片列表 (分頁)
     */
    public function index()
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 20;

            // 計算該使用者的影片總數
            $total = $this->model->where('user_id', $userId)->countAllResults(false);
            $videos = $this->model
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->paginate($perPage, 'default', $page - 1);

            return $this->respond(api_paginated($videos, $page, $perPage, $total), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/videos/search
     * 搜尋使用者的影片
     */
    public function search()
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $query = $this->request->getVar('q') ?? '';

            if (strlen($query) < 2) {
                return $this->fail('搜尋詞至少 2 個字元', 400);
            }

            $videos = $this->model->search($query, $userId);

            return $this->respond(api_success($videos, '搜尋成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/videos/:id
     * 取得單一影片詳情
     */
    public function show($id = null)
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $video = $this->model
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$video) {
                return $this->failNotFound('影片不存在或無權限存取');
            }

            return $this->respond(api_success($video, '取得成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/videos
     * 建立新影片
     */
    public function create()
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $data = $this->request->getJSON(true);

            // 驗證必填欄位
            if (empty($data['video_id']) || empty($data['title']) || empty($data['youtube_url'])) {
                return $this->fail('缺少必填欄位: video_id, title, youtube_url', 422);
            }

            // 自動設定 user_id
            $data['user_id'] = $userId;

            // 檢查該使用者是否已有此影片
            if ($this->model->findByYoutubeId($data['video_id'], $userId)) {
                return $this->fail('該 YouTube 影片已存在於您的影片庫', 409);
            }

            if (!$this->model->insert($data)) {
                return $this->fail('建立失敗: ' . implode(', ', $this->model->errors()), 400);
            }

            $id = $this->model->getInsertID();
            $video = $this->model->find($id);

            return $this->respondCreated(api_success($video, '建立成功'), 201);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/videos/:id
     * 更新影片
     */
    public function update($id = null)
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $video = $this->model
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$video) {
                return $this->failNotFound('影片不存在或無權限修改');
            }

            $data = $this->request->getJSON(true);

            // 防止修改 user_id
            unset($data['user_id']);

            if (!$this->model->update($id, $data)) {
                return $this->fail('更新失敗: ' . implode(', ', $this->model->errors()), 400);
            }

            $updated = $this->model->find($id);

            return $this->respond(api_success($updated, '更新成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/videos/:id
     * 刪除影片
     */
    public function delete($id = null)
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $video = $this->model
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$video) {
                return $this->failNotFound('影片不存在或無權限刪除');
            }

            $this->model->delete($id);

            return $this->respond(api_success(null, '刪除成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/videos/check
     * 檢查影片是否存在於使用者的影片庫
     */
    public function check()
    {
        try {
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $videoId = $this->request->getVar('video_id');

            if (!$videoId) {
                return $this->fail('缺少 video_id 參數', 400);
            }

            $video = $this->model->findByYoutubeId($videoId, $userId);
            $exists = $video !== null;

            return $this->respond(api_success([
                'exists' => $exists,
                'video' => $video
            ], '檢查完成'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
