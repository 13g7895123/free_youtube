<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Notification extends ResourceController
{
    protected $modelName = 'App\Models\NotificationModel';
    protected $format = 'json';

    /**
     * 建立新通知
     *
     * POST /api/notifications
     *
     * @return ResponseInterface
     */
    public function create()
    {
        $model = new NotificationModel();

        // 取得 POST 資料
        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->fail('無效的 JSON 資料', 400);
        }

        // 準備要插入的資料
        $notificationData = [
            'project' => $data['project'] ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => $data['status'] ?? 0
        ];

        // 驗證並插入
        if (!$model->insert($notificationData)) {
            $errors = $model->errors();
            return $this->fail([
                'success' => false,
                'message' => '資料驗證失敗',
                'errors' => $errors
            ], 400);
        }

        // 取得新建立的通知
        $notificationId = $model->getInsertID();
        $notification = $model->find($notificationId);

        return $this->respondCreated([
            'success' => true,
            'message' => '通知建立成功',
            'data' => $notification
        ]);
    }

    /**
     * 更新通知狀態
     *
     * PATCH /api/notifications/{id}/status
     *
     * @param int $id 通知 ID
     * @return ResponseInterface
     */
    public function updateStatus($id = null)
    {
        if (!$id) {
            return $this->fail('缺少通知 ID', 400);
        }

        $model = new NotificationModel();

        // 檢查通知是否存在
        $notification = $model->find($id);
        if (!$notification) {
            return $this->failNotFound('找不到指定的通知');
        }

        // 取得 PATCH/PUT 資料
        $data = $this->request->getJSON(true);

        if (!isset($data['status'])) {
            return $this->fail('缺少 status 參數', 400);
        }

        $status = (int) $data['status'];

        // 驗證狀態值
        if (!in_array($status, [0, 1], true)) {
            return $this->fail('status 必須為 0 或 1', 400);
        }

        // 更新狀態
        if (!$model->updateNotificationStatus($id, $status)) {
            return $this->fail('更新狀態失敗', 500);
        }

        // 取得更新後的資料
        $updatedNotification = $model->find($id);

        return $this->respond([
            'success' => true,
            'message' => '通知狀態更新成功',
            'data' => [
                'id' => $updatedNotification['id'],
                'status' => $updatedNotification['status'],
                'notified_at' => $updatedNotification['notified_at']
            ]
        ]);
    }

    /**
     * 取得通知列表（選用功能）
     *
     * GET /api/notifications
     *
     * @return ResponseInterface
     */
    public function index()
    {
        $model = new NotificationModel();

        // 取得查詢參數
        $project = $this->request->getGet('project');
        $status = $this->request->getGet('status');
        $limit = (int) ($this->request->getGet('limit') ?: 50);

        if ($project) {
            $notifications = $model->getNotificationsByProject(
                $project,
                $status !== null ? (int) $status : null,
                $limit
            );
        } else {
            $builder = $model;

            if ($status !== null) {
                $builder = $builder->where('status', (int) $status);
            }

            $notifications = $builder->orderBy('created_at', 'DESC')
                                   ->limit($limit)
                                   ->findAll();
        }

        return $this->respond([
            'success' => true,
            'data' => $notifications,
            'count' => count($notifications)
        ]);
    }

    /**
     * 取得單一通知（選用功能）
     *
     * GET /api/notifications/{id}
     *
     * @param int $id 通知 ID
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('缺少通知 ID', 400);
        }

        $model = new NotificationModel();
        $notification = $model->find($id);

        if (!$notification) {
            return $this->failNotFound('找不到指定的通知');
        }

        return $this->respond([
            'success' => true,
            'data' => $notification
        ]);
    }
}
