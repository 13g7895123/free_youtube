<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'project',
        'title',
        'message',
        'status',
        'notified_at'
    ];

    // 日期欄位
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';

    // 驗證規則
    protected $validationRules = [
        'project' => [
            'rules' => 'required|max_length[100]',
            'errors' => [
                'required' => '專案名稱為必填欄位',
                'max_length' => '專案名稱不能超過 100 個字元'
            ]
        ],
        'title' => [
            'rules' => 'required|max_length[100]',
            'errors' => [
                'required' => '通知標題為必填欄位',
                'max_length' => '通知標題不能超過 100 個字元'
            ]
        ],
        'message' => [
            'rules' => 'required',
            'errors' => [
                'required' => '通知內容為必填欄位'
            ]
        ],
        'status' => [
            'rules' => 'permit_empty|in_list[0,1]',
            'errors' => [
                'in_list' => '狀態值必須為 0 或 1'
            ]
        ]
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 更新通知狀態
     *
     * @param int $id 通知 ID
     * @param int $status 狀態 (0 或 1)
     * @return bool
     */
    public function updateNotificationStatus(int $id, int $status): bool
    {
        $data = [
            'status' => $status
        ];

        // 如果狀態設為已通知 (1)，記錄通知時間
        if ($status === 1) {
            $data['notified_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data);
    }

    /**
     * 取得特定專案的通知列表
     *
     * @param string $project 專案名稱
     * @param int|null $status 狀態篩選 (0, 1 或 null 表示全部)
     * @param int $limit 限制筆數
     * @return array
     */
    public function getNotificationsByProject(string $project, ?int $status = null, int $limit = 50): array
    {
        $builder = $this->where('project', $project);

        if ($status !== null) {
            $builder->where('status', $status);
        }

        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit)
                      ->findAll();
    }
}
