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

    /**
     * GET /api/videos
     * 取得所有影片列表 (分頁)
     */
    public function index()
    {
        try {
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 20;

            $total = $this->model->countAllResults();
            $videos = $this->model
                ->orderBy('created_at', 'DESC')
                ->paginate($perPage, 'default', $page - 1);

            return $this->respond(api_paginated($videos, $page, $perPage, $total), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/videos/search
     * 搜尋影片
     */
    public function search()
    {
        try {
            $query = $this->request->getVar('q') ?? '';

            if (strlen($query) < 2) {
                return $this->fail('搜尋詞至少 2 個字元', 400);
            }

            $videos = $this->model->search($query);

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
            $video = $this->model->find($id);

            if (!$video) {
                return $this->failNotFound('影片不存在');
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
            $data = $this->request->getJSON(true);

            // 驗證必填欄位
            if (empty($data['video_id']) || empty($data['title']) || empty($data['youtube_url'])) {
                return $this->fail('缺少必填欄位: video_id, title, youtube_url', 422);
            }

            // 檢查影片是否已存在
            if ($this->model->findByYoutubeId($data['video_id'])) {
                return $this->fail('該 YouTube 影片已存在', 409);
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
            $video = $this->model->find($id);

            if (!$video) {
                return $this->failNotFound('影片不存在');
            }

            $data = $this->request->getJSON(true);

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
            $video = $this->model->find($id);

            if (!$video) {
                return $this->failNotFound('影片不存在');
            }

            $this->model->delete($id);

            return $this->respond(api_success(null, '刪除成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/videos/check
     * 檢查影片是否存在
     */
    public function check()
    {
        try {
            $videoId = $this->request->getVar('video_id');

            if (!$videoId) {
                return $this->fail('缺少 video_id 參數', 400);
            }

            $exists = $this->model->exists($videoId);

            return $this->respond(api_success(['exists' => $exists], '檢查完成'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
