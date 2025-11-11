<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTokenModel extends Model
{
    protected $table = 'user_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
        'device_id',
        'ip_address',
        'user_agent'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_not_unique[users.id]',
        'access_token' => 'required|max_length[512]',
        'expires_at' => 'required|valid_date'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 根據 Access Token 查找記錄
     */
    public function findByAccessToken(string $accessToken)
    {
        return $this->where('access_token', $accessToken)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * 清理過期 Token
     */
    public function cleanupExpired()
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * 撤銷使用者所有 Token (登出所有裝置)
     */
    public function revokeAllUserTokens(int $userId)
    {
        return $this->where('user_id', $userId)->delete();
    }
}
