<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'line_user_id',
        'display_name',
        'avatar_url',
        'email',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'line_user_id' => 'required|max_length[255]|is_unique[users.line_user_id,id,{id}]',
        'display_name' => 'required|max_length[255]',
        'avatar_url' => 'permit_empty|valid_url',
        'status' => 'required|in_list[active,soft_deleted]'
    ];

    protected $validationMessages = [
        'line_user_id' => [
            'required' => 'LINE User ID 為必填',
            'is_unique' => 'LINE User ID 已存在'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 根據 LINE User ID 查找會員
     */
    public function findByLineUserId(string $lineUserId)
    {
        return $this->where('line_user_id', $lineUserId)->first();
    }

    /**
     * 恢復軟刪除的會員
     *
     * @param int $userId
     * @return bool
     */
    public function restoreUser(int $userId): bool
    {
        // 記錄恢復日誌
        log_message('info', "Restoring deleted user: {$userId}");

        $result = $this->update($userId, [
            'deleted_at' => null,
            'status' => 'active'
        ]);

        if ($result) {
            log_message('info', "User {$userId} successfully restored");
        } else {
            log_message('error', "Failed to restore user {$userId}");
        }

        return $result;
    }
}
