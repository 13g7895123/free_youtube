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
     * 取得使用者的所有播放清單
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

            // 計算該使用者的播放清單總數
            $total = $this->model->where('user_id', $userId)->countAllResults(false);
            $playlists = $this->model
                ->where('user_id', $userId)
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
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            // 驗證播放清單是否屬於該使用者
            $playlist = $this->model
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在或無權限存取');
            }

            // 取得播放清單項目
            $playlist = $this->model->getWithItems($id);

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
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            $data = $this->request->getJSON(true);

            // 驗證必填欄位
            if (empty($data['name'])) {
                return $this->fail('缺少必填欄位: name', 422);
            }

            // 自動設定 user_id 和預設值
            $data['user_id'] = $userId;
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
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            // 驗證播放清單是否屬於該使用者
            $playlist = $this->model
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在或無權限修改');
            }

            $data = $this->request->getJSON(true);

            // 防止更新 user_id 和 item_count
            unset($data['user_id']);
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
            $userId = $this->request->userId ?? null;

            if (!$userId) {
                return $this->fail('未登入', 401);
            }

            // 驗證播放清單是否屬於該使用者
            $playlist = $this->model
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$playlist) {
                return $this->failNotFound('播放清單不存在或無權限刪除');
            }

            $this->model->delete($id);

            return $this->respond(api_success(null, '刪除成功'), 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
