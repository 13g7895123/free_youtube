<?php

namespace App\Models;

use CodeIgniter\Model;

class LineLoginLogModel extends Model
{
    protected $table = 'line_login_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'session_id',
        'step',
        'status',
        'line_user_id',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'user_agent'
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    protected $validationRules = [
        'step' => 'required|max_length[50]',
        'status' => 'required|in_list[success,error,warning]'
    ];

    protected $skipValidation = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * 確保表格存在，若不存在則建立
     */
    protected function ensureTableExists(): void
    {
        $db = \Config\Database::connect();

        // 檢查表格是否存在
        if (!$db->tableExists($this->table)) {
            $forge = \Config\Database::forge();

            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'session_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => false,
                ],
                'step' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['success', 'error', 'warning'],
                    'null' => false,
                ],
                'line_user_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'request_data' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'response_data' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ],
                'user_agent' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $forge->addKey('id', true);
            $forge->addKey('session_id');
            $forge->addKey('status');
            $forge->addKey('created_at');
            $forge->createTable($this->table);
        }
    }
    
    /**
     * 記錄 LINE 登入步驟
     *
     * @param string $sessionId
     * @param string $step
     * @param string $status
     * @param array $data
     * @return bool
     */
    public function logStep(string $sessionId, string $step, string $status = 'success', array $data = []): bool
    {
        $logData = [
            'session_id' => $sessionId,
            'step' => $step,
            'status' => $status,
            'line_user_id' => $data['line_user_id'] ?? null,
            'request_data' => isset($data['request']) ? json_encode($data['request'], JSON_UNESCAPED_UNICODE) : null,
            'response_data' => isset($data['response']) ? json_encode($data['response'], JSON_UNESCAPED_UNICODE) : null,
            'error_message' => $data['error'] ?? null,
            'ip_address' => $data['ip'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($logData) !== false;
    }
    
    /**
     * 獲取特定 session 的所有 log
     *
     * @param string $sessionId
     * @return array
     */
    public function getSessionLogs(string $sessionId): array
    {
        return $this->where('session_id', $sessionId)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }
    
    /**
     * 獲取特定 LINE User ID 的所有 log
     *
     * @param string $lineUserId
     * @param int $limit
     * @return array
     */
    public function getUserLogs(string $lineUserId, int $limit = 50): array
    {
        return $this->where('line_user_id', $lineUserId)
                    ->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * 獲取最近的錯誤 log
     *
     * @param int $limit
     * @return array
     */
    public function getRecentErrors(int $limit = 50): array
    {
        return $this->where('status', 'error')
                    ->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * 清理舊的 log (超過指定天數)
     *
     * @param int $days
     * @return int 刪除的筆數
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('created_at <', $date)->delete();
    }
}
