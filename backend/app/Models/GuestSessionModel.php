<?php

namespace App\Models;

use CodeIgniter\Model;

class GuestSessionModel extends Model
{
    protected $table = 'guest_sessions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'session_id',
        'history_data',
        'expires_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    // Validation
    protected $validationRules = [
        'session_id' => 'required|max_length[128]|is_unique[guest_sessions.session_id,id,{id}]',
        'expires_at' => 'required|valid_date'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 根據 Session ID 查找
     */
    public function findBySessionId(string $sessionId)
    {
        return $this->where('session_id', $sessionId)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * 清理過期 Session
     */
    public function cleanupExpired()
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * 儲存訪客歷史記錄
     */
    public function saveHistory(string $sessionId, array $historyData)
    {
        $existing = $this->findBySessionId($sessionId);

        $data = [
            'session_id' => $sessionId,
            'history_data' => json_encode($historyData),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data);
        }
    }
}
