<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\PlaylistModel;

class PlaylistController extends ResourceController
{
    use ResponseTrait;

    protected $modelName = PlaylistModel::class;
    protected $format    = 'json';
    protected $helpers   = ['response'];

    public function __construct()
    {
        helper('response');
    }

    /**
     * GET /api/playlists
     * 取得所有播放清單
     */
    public function index()
    {
        try {
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 20;

            $total = $this->model->countAllResults();
            $playlists = $this->model
                ->orderBy('created_at', 'DESC')
                ->paginate($perPage, 'default', $page - 1);

            return $this->respond(api_paginated($playlists, $page, $perPage, $total), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/playlists/:id
     * 取得播放清單詳情及其項目
     */
    public function show($id = null)
    {
        try {
            $playlist = $this->model->getWithItems($id);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            return $this->respond(api_success($playlist, '取得成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/playlists
     * 建立新播放清單
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // 驗證必填欄位
            if (empty($data['name'])) {
                return $this->fail('缺少必填欄位: name', 422);
            }

            // 設定預設值
            $data['is_active'] = $data['is_active'] ?? true;
            $data['item_count'] = 0;

            if (!$this->model->insert($data)) {
                return $this->fail('建立失敗: ' . implode(', ', $this->model->errors()), 400);
            }

            $id = $this->model->getInsertID();
            $playlist = $this->model->find($id);

            return $this->respondCreated(api_success($playlist, '建立成功'), 201);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/playlists/:id
     * 更新播放清單
     */
    public function update($id = null)
    {
        try {
            $playlist = $this->model->find($id);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            $data = $this->request->getJSON(true);

            // 防止更新 item_count (應由 PlaylistItemController 更新)
            unset($data['item_count']);

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
     * DELETE /api/playlists/:id
     * 刪除播放清單
     */
    public function delete($id = null)
    {
        try {
            $playlist = $this->model->find($id);

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在');
            }

            $this->model->delete($id);

            return $this->respond(api_success(null, '刪除成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
