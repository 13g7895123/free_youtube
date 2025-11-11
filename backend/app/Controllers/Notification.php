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

    /**
     * 建立單筆測試通知資料
     *
     * GET /api/notifications/create-test
     *
     * @return ResponseInterface
     */
    public function createTest()
    {
        $model = new NotificationModel();

        // 隨機選擇一種測試通知類型
        $testNotifications = [
            [
                'title' => '✅ 正式環境部署成功',
                'message' => "free_youtube 專案已成功部署至正式環境\n分支: master\n提交: " . substr(md5(time()), 0, 12) . "\n部署者: GitHub Actions\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '✅ 開發環境部署成功',
                'message' => "free_youtube 專案已成功部署至開發環境\n分支: develop\n提交: " . substr(md5(time()), 0, 12) . "\n部署者: GitHub Actions\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '❌ 正式環境部署失敗',
                'message' => "free_youtube 專案部署至正式環境時發生錯誤\n分支: master\n提交: " . substr(md5(time()), 0, 12) . "\n部署者: GitHub Actions\n錯誤: Docker build failed\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '🔄 系統更新通知',
                'message' => "free_youtube 專案進行系統維護更新\n更新項目: 資料庫遷移\n預計時間: 30 分鐘\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '⚠️ 效能警告',
                'message' => "free_youtube 專案效能監控警告\nCPU 使用率: " . rand(70, 95) . "%\n記憶體使用: " . rand(60, 85) . "%\n建議: 檢查背景任務\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '📦 依賴套件更新',
                'message' => "free_youtube 專案依賴套件已更新\n更新套件數: " . rand(5, 20) . "\n安全性更新: " . rand(1, 5) . "\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '🔒 安全性掃描完成',
                'message' => "free_youtube 專案安全性掃描已完成\n發現問題: 0\n掃描項目: " . rand(100, 200) . "\n狀態: 通過\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '💾 資料庫備份完成',
                'message' => "free_youtube 專案資料庫備份成功\n備份大小: " . number_format(rand(800, 2000) / 1000, 1) . " GB\n備份位置: /backup/" . date('Y-m-d') . "\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '🚀 新功能上線',
                'message' => "free_youtube 專案新功能已上線\n功能: 播放清單分享\n版本: v" . rand(2, 3) . "." . rand(0, 5) . "." . rand(0, 10) . "\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '🐛 錯誤修復',
                'message' => "free_youtube 專案錯誤已修復\n問題: 播放器初始化失敗\n影響範圍: 浮動播放器\n修復版本: v2.0." . rand(1, 10) . "\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ]
        ];

        // 隨機選擇一個測試通知
        $randomIndex = array_rand($testNotifications);
        $testData = $testNotifications[$randomIndex];

        $notificationData = [
            'project' => 'free_youtube',
            'title' => $testData['title'],
            'message' => $testData['message'],
            'status' => $testData['status']
        ];

        if (!$model->insert($notificationData)) {
            return $this->fail([
                'success' => false,
                'message' => '測試通知建立失敗',
                'errors' => $model->errors()
            ], 500);
        }

        $notificationId = $model->getInsertID();
        $notification = $model->find($notificationId);

        return $this->respondCreated([
            'success' => true,
            'message' => '測試通知建立成功',
            'data' => $notification
        ]);
    }

    /**
     * 建立測試通知資料
     *
     * POST /api/notifications/test-data
     *
     * @return ResponseInterface
     */
    public function createTestData()
    {
        $model = new NotificationModel();

        // 取得請求參數
        $data = $this->request->getJSON(true);
        $count = isset($data['count']) ? (int) $data['count'] : 5;
        $count = min(max($count, 1), 50); // 限制在 1-50 之間

        $testNotifications = [
            [
                'title' => '✅ 正式環境部署成功',
                'message' => "free_youtube 專案已成功部署至正式環境\n分支: master\n提交: abc123def456\n部署者: GitHub Actions\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '✅ 開發環境部署成功',
                'message' => "free_youtube 專案已成功部署至開發環境\n分支: develop\n提交: def456ghi789\n部署者: GitHub Actions\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '❌ 正式環境部署失敗',
                'message' => "free_youtube 專案部署至正式環境時發生錯誤\n分支: master\n提交: ghi789jkl012\n部署者: GitHub Actions\n錯誤: Docker build failed\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '🔄 系統更新通知',
                'message' => "free_youtube 專案進行系統維護更新\n更新項目: 資料庫遷移\n預計時間: 30 分鐘\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '⚠️ 效能警告',
                'message' => "free_youtube 專案效能監控警告\nCPU 使用率: 85%\n記憶體使用: 78%\n建議: 檢查背景任務\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '📦 依賴套件更新',
                'message' => "free_youtube 專案依賴套件已更新\n更新套件數: 12\n安全性更新: 3\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '🔒 安全性掃描完成',
                'message' => "free_youtube 專案安全性掃描已完成\n發現問題: 0\n掃描項目: 150\n狀態: 通過\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '💾 資料庫備份完成',
                'message' => "free_youtube 專案資料庫備份成功\n備份大小: 1.2 GB\n備份位置: /backup/2025-11-05\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ],
            [
                'title' => '🚀 新功能上線',
                'message' => "free_youtube 專案新功能已上線\n功能: 播放清單分享\n版本: v2.1.0\n時間: " . date('Y-m-d H:i:s'),
                'status' => 0
            ],
            [
                'title' => '🐛 錯誤修復',
                'message' => "free_youtube 專案錯誤已修復\n問題: 播放器初始化失敗\n影響範圍: 浮動播放器\n修復版本: v2.0.5\n時間: " . date('Y-m-d H:i:s'),
                'status' => 1
            ]
        ];

        $createdNotifications = [];
        $errors = [];

        // 建立指定數量的測試通知
        for ($i = 0; $i < $count; $i++) {
            $testData = $testNotifications[$i % count($testNotifications)];
            
            $notificationData = [
                'project' => 'free_youtube',
                'title' => $testData['title'],
                'message' => $testData['message'],
                'status' => $testData['status']
            ];

            if ($model->insert($notificationData)) {
                $notificationId = $model->getInsertID();
                $notification = $model->find($notificationId);
                $createdNotifications[] = $notification;
            } else {
                $errors[] = [
                    'index' => $i,
                    'errors' => $model->errors()
                ];
            }
        }

        if (count($createdNotifications) === 0) {
            return $this->fail([
                'success' => false,
                'message' => '測試資料建立失敗',
                'errors' => $errors
            ], 500);
        }

        return $this->respondCreated([
            'success' => true,
            'message' => "成功建立 {count} 筆測試通知資料",
            'data' => [
                'created_count' => count($createdNotifications),
                'notifications' => $createdNotifications
            ],
            'errors' => count($errors) > 0 ? $errors : null
        ]);
    }
}
