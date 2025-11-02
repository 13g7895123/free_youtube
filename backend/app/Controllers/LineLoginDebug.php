<?php

namespace App\Controllers;

use App\Models\LineLoginLogModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * LINE Login Debug Controller
 * 提供完整的日誌查詢和系統診斷 API
 */
class LineLoginDebug extends ResourceController
{
    protected $lineLoginLogModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->lineLoginLogModel = new LineLoginLogModel();
    }

    /**
     * 系統狀態總覽
     * GET /api/debug/line-login/status
     */
    public function status()
    {
        try {
            $db = \Config\Database::connect();

            // 統計資訊
            $stats = [
                // 今日統計
                'today' => [
                    'total_attempts' => $this->getTodayCount(),
                    'successful' => $this->getTodayCount('success'),
                    'errors' => $this->getTodayCount('error'),
                    'warnings' => $this->getTodayCount('warning'),
                    'completed_logins' => $this->getCompletedLoginsCount('today'),
                ],
                // 最近 1 小時
                'last_hour' => [
                    'total_attempts' => $this->getRecentCount(1),
                    'errors' => $this->getRecentCount(1, 'error'),
                    'completed_logins' => $this->getCompletedLoginsCount('hour'),
                ],
                // 最近 24 小時
                'last_24h' => [
                    'total_attempts' => $this->getRecentCount(24),
                    'errors' => $this->getRecentCount(24, 'error'),
                    'completed_logins' => $this->getCompletedLoginsCount('24h'),
                ],
                // 資料庫狀態
                'database' => [
                    'total_logs' => $db->table('line_login_logs')->countAll(),
                    'oldest_log' => $this->getOldestLogDate(),
                    'newest_log' => $this->getNewestLogDate(),
                ],
            ];

            // 環境配置（不包含敏感資訊）
            $config = [
                'line_login_callback_url' => env('LINE_LOGIN_CALLBACK_URL'),
                'frontend_url' => env('FRONTEND_URL'),
                'has_channel_id' => !empty(env('LINE_LOGIN_CHANNEL_ID')),
                'has_channel_secret' => !empty(env('LINE_LOGIN_CHANNEL_SECRET')),
                'auth_mode' => env('AUTH_MODE', 'line'),
                'ci_environment' => env('CI_ENVIRONMENT', 'production'),
            ];

            // 最近的錯誤
            $recentErrors = $this->lineLoginLogModel
                ->where('status', 'error')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->find();

            return $this->respond([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'config' => $config,
                    'recent_errors' => $recentErrors,
                    'timestamp' => date('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug status error: ' . $e->getMessage());
            return $this->fail('無法取得狀態資訊', 500);
        }
    }

    /**
     * 取得最近的日誌（包含成功和失敗）
     * GET /api/debug/line-login/recent?limit=20&status=all
     */
    public function recent()
    {
        try {
            $limit = min((int) ($this->request->getGet('limit') ?? 50), 200);
            $status = $this->request->getGet('status'); // success, error, warning, all

            $builder = $this->lineLoginLogModel->builder();

            if ($status && $status !== 'all') {
                $builder->where('status', $status);
            }

            $logs = $builder
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            return $this->respond([
                'success' => true,
                'data' => $logs,
                'count' => count($logs),
                'filters' => [
                    'limit' => $limit,
                    'status' => $status ?? 'all',
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug recent error: ' . $e->getMessage());
            return $this->fail('無法取得日誌', 500);
        }
    }

    /**
     * 取得錯誤日誌（詳細）
     * GET /api/debug/line-login/errors?limit=20&hours=24
     */
    public function errors()
    {
        try {
            $limit = min((int) ($this->request->getGet('limit') ?? 50), 200);
            $hours = (int) ($this->request->getGet('hours') ?? 24);

            $builder = $this->lineLoginLogModel->builder();
            $logs = $builder
                ->where('status', 'error')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$hours} hours")))
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            // 錯誤分類統計
            $errorTypes = [];
            foreach ($logs as $log) {
                $step = $log['step'];
                if (!isset($errorTypes[$step])) {
                    $errorTypes[$step] = 0;
                }
                $errorTypes[$step]++;
            }

            return $this->respond([
                'success' => true,
                'data' => $logs,
                'count' => count($logs),
                'error_types' => $errorTypes,
                'filters' => [
                    'limit' => $limit,
                    'hours' => $hours,
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug errors error: ' . $e->getMessage());
            return $this->fail('無法取得錯誤日誌', 500);
        }
    }

    /**
     * 取得 Session 完整流程
     * GET /api/debug/line-login/session/{sessionId}
     */
    public function session($sessionId = null)
    {
        if (!$sessionId) {
            return $this->fail('請提供 session_id', 400);
        }

        try {
            $logs = $this->lineLoginLogModel
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'ASC')
                ->find();

            if (empty($logs)) {
                return $this->fail('找不到該 session 的日誌', 404);
            }

            // 分析流程
            $analysis = [
                'total_steps' => count($logs),
                'has_errors' => false,
                'has_warnings' => false,
                'completed' => false,
                'failed_at_step' => null,
                'duration_seconds' => null,
            ];

            foreach ($logs as $log) {
                if ($log['status'] === 'error') {
                    $analysis['has_errors'] = true;
                    if (!$analysis['failed_at_step']) {
                        $analysis['failed_at_step'] = $log['step'];
                    }
                }
                if ($log['status'] === 'warning') {
                    $analysis['has_warnings'] = true;
                }
                if ($log['step'] === 'complete' && $log['status'] === 'success') {
                    $analysis['completed'] = true;
                }
            }

            // 計算持續時間
            if (count($logs) > 1) {
                $firstTime = strtotime($logs[0]['created_at']);
                $lastTime = strtotime($logs[count($logs) - 1]['created_at']);
                $analysis['duration_seconds'] = $lastTime - $firstTime;
            }

            return $this->respond([
                'success' => true,
                'session_id' => $sessionId,
                'data' => $logs,
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug session error: ' . $e->getMessage());
            return $this->fail('無法取得 session 日誌', 500);
        }
    }

    /**
     * 取得所有 Sessions（最近的）
     * GET /api/debug/line-login/sessions?limit=20
     */
    public function sessions()
    {
        try {
            $limit = min((int) ($this->request->getGet('limit') ?? 20), 100);

            $db = \Config\Database::connect();
            $query = $db->query("
                SELECT
                    session_id,
                    MIN(created_at) as started_at,
                    MAX(created_at) as ended_at,
                    COUNT(*) as steps_count,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
                    SUM(CASE WHEN step = 'complete' AND status = 'success' THEN 1 ELSE 0 END) as completed,
                    MAX(CASE WHEN line_user_id IS NOT NULL THEN line_user_id ELSE NULL END) as line_user_id,
                    MAX(ip_address) as ip_address
                FROM line_login_logs
                GROUP BY session_id
                ORDER BY MIN(created_at) DESC
                LIMIT ?
            ", [$limit]);

            $sessions = $query->getResultArray();

            return $this->respond([
                'success' => true,
                'data' => $sessions,
                'count' => count($sessions),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug sessions error: ' . $e->getMessage());
            return $this->fail('無法取得 sessions', 500);
        }
    }

    /**
     * 系統診斷資訊
     * GET /api/debug/line-login/diagnostic
     */
    public function diagnostic()
    {
        try {
            $db = \Config\Database::connect();

            $diagnostic = [
                // PHP 資訊
                'php' => [
                    'version' => PHP_VERSION,
                    'extensions' => [
                        'curl' => extension_loaded('curl'),
                        'json' => extension_loaded('json'),
                        'mysqli' => extension_loaded('mysqli'),
                        'openssl' => extension_loaded('openssl'),
                    ],
                ],
                // 資料庫連接
                'database' => [
                    'connected' => $db->connID !== false,
                    'database' => $db->getDatabase(),
                    'platform' => $db->getPlatform(),
                ],
                // 資料表存在性
                'tables' => [
                    'line_login_logs' => $db->tableExists('line_login_logs'),
                    'users' => $db->tableExists('users'),
                    'user_tokens' => $db->tableExists('user_tokens'),
                ],
                // 環境變數（不含敏感資訊）
                'environment' => [
                    'CI_ENVIRONMENT' => env('CI_ENVIRONMENT'),
                    'AUTH_MODE' => env('AUTH_MODE'),
                    'LINE_LOGIN_CALLBACK_URL' => env('LINE_LOGIN_CALLBACK_URL'),
                    'FRONTEND_URL' => env('FRONTEND_URL'),
                    'has_channel_id' => !empty(env('LINE_LOGIN_CHANNEL_ID')),
                    'has_channel_secret' => !empty(env('LINE_LOGIN_CHANNEL_SECRET')),
                    'channel_id_length' => strlen(env('LINE_LOGIN_CHANNEL_ID') ?? ''),
                    'secret_length' => strlen(env('LINE_LOGIN_CHANNEL_SECRET') ?? ''),
                ],
                // JWT 配置
                'jwt' => [
                    'has_secret' => !empty(env('JWT_SECRET_KEY')),
                    'secret_length' => strlen(env('JWT_SECRET_KEY') ?? ''),
                    'access_expire' => env('JWT_ACCESS_TOKEN_EXPIRE', 900),
                    'refresh_expire' => env('JWT_REFRESH_TOKEN_EXPIRE', 2592000),
                ],
                // 伺服器資訊
                'server' => [
                    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
                    'time' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get(),
                ],
            ];

            return $this->respond([
                'success' => true,
                'data' => $diagnostic,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug diagnostic error: ' . $e->getMessage());
            return $this->fail('無法取得診斷資訊', 500);
        }
    }

    /**
     * 取得最常見的錯誤
     * GET /api/debug/line-login/error-summary?days=7
     */
    public function errorSummary()
    {
        try {
            $days = min((int) ($this->request->getGet('days') ?? 7), 30);

            $db = \Config\Database::connect();
            $query = $db->query("
                SELECT
                    step,
                    LEFT(error_message, 100) as error_message,
                    COUNT(*) as count,
                    MAX(created_at) as last_occurred
                FROM line_login_logs
                WHERE status = 'error'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY step, error_message
                ORDER BY count DESC
                LIMIT 20
            ", [$days]);

            $summary = $query->getResultArray();

            return $this->respond([
                'success' => true,
                'data' => $summary,
                'count' => count($summary),
                'period_days' => $days,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Debug error summary error: ' . $e->getMessage());
            return $this->fail('無法取得錯誤摘要', 500);
        }
    }

    /**
     * 測試 LINE API 連接
     * GET /api/debug/line-login/test-connection
     */
    public function testConnection()
    {
        $tests = [
            'channel_id' => [
                'status' => !empty(env('LINE_LOGIN_CHANNEL_ID')),
                'message' => !empty(env('LINE_LOGIN_CHANNEL_ID')) ? 'Channel ID 已設定' : 'Channel ID 未設定',
            ],
            'channel_secret' => [
                'status' => !empty(env('LINE_LOGIN_CHANNEL_SECRET')),
                'message' => !empty(env('LINE_LOGIN_CHANNEL_SECRET')) ? 'Channel Secret 已設定' : 'Channel Secret 未設定',
            ],
            'callback_url' => [
                'status' => !empty(env('LINE_LOGIN_CALLBACK_URL')),
                'message' => env('LINE_LOGIN_CALLBACK_URL') ?? '未設定',
                'is_https' => strpos(env('LINE_LOGIN_CALLBACK_URL') ?? '', 'https://') === 0,
            ],
            'frontend_url' => [
                'status' => !empty(env('FRONTEND_URL')),
                'message' => env('FRONTEND_URL') ?? '未設定',
            ],
        ];

        $allPassed = true;
        foreach ($tests as $test) {
            if (!$test['status']) {
                $allPassed = false;
                break;
            }
        }

        return $this->respond([
            'success' => $allPassed,
            'data' => $tests,
            'summary' => $allPassed ? '所有配置正確' : '部分配置缺失',
        ]);
    }

    // ========== 輔助方法 ==========

    private function getTodayCount($status = null)
    {
        $builder = $this->lineLoginLogModel->builder();
        $builder->where('DATE(created_at)', date('Y-m-d'));

        if ($status) {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    private function getRecentCount($hours, $status = null)
    {
        $builder = $this->lineLoginLogModel->builder();
        $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$hours} hours")));

        if ($status) {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    private function getCompletedLoginsCount($period)
    {
        $builder = $this->lineLoginLogModel->builder();
        $builder->where('step', 'complete')
                ->where('status', 'success');

        switch ($period) {
            case 'today':
                $builder->where('DATE(created_at)', date('Y-m-d'));
                break;
            case 'hour':
                $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')));
                break;
            case '24h':
                $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
                break;
        }

        return $builder->countAllResults();
    }

    private function getOldestLogDate()
    {
        $log = $this->lineLoginLogModel
            ->orderBy('created_at', 'ASC')
            ->first();

        return $log ? $log['created_at'] : null;
    }

    private function getNewestLogDate()
    {
        $log = $this->lineLoginLogModel
            ->orderBy('created_at', 'DESC')
            ->first();

        return $log ? $log['created_at'] : null;
    }
}
