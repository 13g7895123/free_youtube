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

    /**
     * 測試 JWT 生成功能
     * GET /api/debug/line-login/test-jwt
     */
    public function testJwtGeneration()
    {
        try {
            $testResults = [];

            // 1. 測試 JWT_SECRET_KEY 讀取
            $secretKeyTest = [
                'getenv' => getenv('JWT_SECRET_KEY') ? 'has value' : 'empty',
                '$_ENV' => isset($_ENV['JWT_SECRET_KEY']) ? 'has value' : 'not set',
                'env_file' => file_exists(APPPATH . '../.env') ? 'exists' : 'not found',
            ];

            // 2. 測試 JWT 生成
            $jwtTestResult = [];
            try {
                helper('jwt');
                $testUserId = 999999; // 測試用戶 ID

                // 測試 Access Token 生成
                $accessTokenStart = microtime(true);
                $accessToken = \App\Helpers\JwtHelper::generateAccessToken($testUserId);
                $accessTokenTime = (microtime(true) - $accessTokenStart) * 1000;

                $jwtTestResult['access_token'] = [
                    'success' => true,
                    'length' => strlen($accessToken),
                    'parts' => count(explode('.', $accessToken)),
                    'generation_time_ms' => round($accessTokenTime, 2),
                    'sample' => substr($accessToken, 0, 50) . '...',
                ];

                // 測試 Refresh Token 生成
                $refreshTokenStart = microtime(true);
                $deviceId = md5('test-device-' . time());
                $refreshToken = \App\Helpers\JwtHelper::generateRefreshToken($testUserId, $deviceId);
                $refreshTokenTime = (microtime(true) - $refreshTokenStart) * 1000;

                $jwtTestResult['refresh_token'] = [
                    'success' => true,
                    'length' => strlen($refreshToken),
                    'parts' => count(explode('.', $refreshToken)),
                    'generation_time_ms' => round($refreshTokenTime, 2),
                    'sample' => substr($refreshToken, 0, 50) . '...',
                ];

                // 測試 Token 解碼
                $decodedAccess = \App\Helpers\JwtHelper::decode($accessToken);
                $decodedRefresh = \App\Helpers\JwtHelper::decode($refreshToken);

                $jwtTestResult['decode'] = [
                    'access_token' => [
                        'success' => $decodedAccess !== null,
                        'user_id' => $decodedAccess->sub ?? null,
                        'type' => $decodedAccess->type ?? null,
                        'exp' => isset($decodedAccess->exp) ? date('Y-m-d H:i:s', $decodedAccess->exp) : null,
                    ],
                    'refresh_token' => [
                        'success' => $decodedRefresh !== null,
                        'user_id' => $decodedRefresh->sub ?? null,
                        'type' => $decodedRefresh->type ?? null,
                        'jti' => $decodedRefresh->jti ?? null,
                        'device_id' => $decodedRefresh->device_id ?? null,
                        'exp' => isset($decodedRefresh->exp) ? date('Y-m-d H:i:s', $decodedRefresh->exp) : null,
                    ],
                ];

                // 測試 Token 驗證
                $verifyResult = \App\Helpers\JwtHelper::verifyToken($accessToken, 'access');
                $jwtTestResult['verify'] = [
                    'success' => $verifyResult !== null,
                    'verified_user_id' => $verifyResult ? $verifyResult->sub : null,
                ];

            } catch (\Exception $e) {
                $jwtTestResult['error'] = [
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }

            // 3. 測試環境變數
            $envTest = [
                'JWT_ACCESS_TOKEN_EXPIRE' => getenv('JWT_ACCESS_TOKEN_EXPIRE') ?: '未設定 (預設: 900)',
                'JWT_REFRESH_TOKEN_EXPIRE' => getenv('JWT_REFRESH_TOKEN_EXPIRE') ?: '未設定 (預設: 2592000)',
            ];

            return $this->respond([
                'success' => true,
                'data' => [
                    'secret_key_test' => $secretKeyTest,
                    'jwt_generation_test' => $jwtTestResult,
                    'environment_variables' => $envTest,
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->fail([
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * 測試資料庫連接和寫入
     * GET /api/debug/line-login/test-database
     */
    public function testDatabaseConnection()
    {
        try {
            $testResults = [];
            $db = \Config\Database::connect();

            // 1. 測試資料庫連接
            $connectionTest = [
                'connected' => $db->connect(),
                'database' => $db->getDatabase(),
                'driver' => $db->getPlatform(),
                'version' => $db->getVersion(),
            ];

            // 2. 檢查 users 表
            $usersTableTest = [];
            try {
                $userCount = $db->table('users')->countAll();
                $usersTableTest = [
                    'exists' => true,
                    'count' => $userCount,
                    'sample_user' => null,
                ];

                // 取得一個範例用戶
                $sampleUser = $db->table('users')->limit(1)->get()->getRowArray();
                if ($sampleUser) {
                    $usersTableTest['sample_user'] = [
                        'id' => $sampleUser['id'],
                        'display_name' => $sampleUser['display_name'] ?? 'N/A',
                        'created_at' => $sampleUser['created_at'],
                    ];
                }
            } catch (\Exception $e) {
                $usersTableTest = [
                    'exists' => false,
                    'error' => $e->getMessage(),
                ];
            }

            // 3. 檢查 user_tokens 表
            $tokensTableTest = [];
            try {
                // 檢查表結構
                $fields = $db->getFieldData('user_tokens');
                $fieldNames = array_map(function($field) {
                    return $field->name;
                }, $fields);

                $tokenCount = $db->table('user_tokens')->countAll();
                $tokensTableTest = [
                    'exists' => true,
                    'count' => $tokenCount,
                    'fields' => $fieldNames,
                    'recent_tokens' => [],
                ];

                // 取得最近的 token 記錄
                $recentTokens = $db->table('user_tokens')
                    ->orderBy('created_at', 'DESC')
                    ->limit(3)
                    ->get()
                    ->getResultArray();

                foreach ($recentTokens as $token) {
                    $tokensTableTest['recent_tokens'][] = [
                        'id' => $token['id'],
                        'user_id' => $token['user_id'],
                        'token_type' => $token['token_type'],
                        'expires_at' => $token['expires_at'],
                        'created_at' => $token['created_at'],
                    ];
                }
            } catch (\Exception $e) {
                $tokensTableTest = [
                    'exists' => false,
                    'error' => $e->getMessage(),
                ];
            }

            // 4. 測試寫入 line_login_logs
            $writeTest = [];
            try {
                $testData = [
                    'session_id' => 'test-' . uniqid(),
                    'step' => 'test_database_write',
                    'status' => 'success',
                    'response_data' => json_encode(['test' => true]),
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $insertId = $db->table('line_login_logs')->insert($testData);
                $writeTest = [
                    'success' => true,
                    'insert_id' => $db->insertID(),
                    'affected_rows' => $db->affectedRows(),
                ];

                // 清理測試資料
                $db->table('line_login_logs')->where('session_id', $testData['session_id'])->delete();

            } catch (\Exception $e) {
                $writeTest = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }

            return $this->respond([
                'success' => true,
                'data' => [
                    'connection' => $connectionTest,
                    'users_table' => $usersTableTest,
                    'user_tokens_table' => $tokensTableTest,
                    'write_test' => $writeTest,
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->fail([
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * 完整診斷 Token 生成流程
     * GET /api/debug/line-login/diagnose-token/{userId?}
     */
    public function diagnoseTokenGeneration($userId = null)
    {
        try {
            // 如果沒有提供 userId，嘗試取得最近的用戶
            if (!$userId) {
                $db = \Config\Database::connect();
                $recentUser = $db->table('users')
                    ->orderBy('created_at', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRowArray();

                if ($recentUser) {
                    $userId = $recentUser['id'];
                } else {
                    return $this->fail('沒有找到任何用戶，請提供用戶 ID', 400);
                }
            }

            $diagnosis = [];

            // 1. 檢查用戶是否存在
            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);

            $diagnosis['user'] = [
                'exists' => $user !== null,
                'id' => $user ? $user['id'] : null,
                'display_name' => $user ? $user['display_name'] : null,
                'status' => $user ? ($user['deleted_at'] ? 'deleted' : 'active') : 'not found',
            ];

            if (!$user) {
                return $this->respond([
                    'success' => false,
                    'diagnosis' => $diagnosis,
                    'error' => '用戶不存在',
                ]);
            }

            // 2. 模擬 generateUserToken 流程
            $tokenGeneration = [];

            try {
                // 初始化 helper
                helper('jwt');

                // 測試 Access Token 生成
                $accessToken = \App\Helpers\JwtHelper::generateAccessToken($userId);
                $tokenGeneration['access_token'] = [
                    'success' => true,
                    'length' => strlen($accessToken),
                ];

                // 測試 Refresh Token 生成
                $request = service('request');
                $userAgent = $request->getUserAgent()->getAgentString();
                $ipAddress = $request->getIPAddress();
                $deviceId = md5($userAgent . $ipAddress);

                $refreshToken = \App\Helpers\JwtHelper::generateRefreshToken($userId, $deviceId);
                $tokenGeneration['refresh_token'] = [
                    'success' => true,
                    'length' => strlen($refreshToken),
                    'device_id' => $deviceId,
                ];

                // 測試 Token 解碼
                $refreshDecoded = \App\Helpers\JwtHelper::decode($refreshToken);
                $jti = $refreshDecoded->jti ?? null;

                $tokenGeneration['decode'] = [
                    'success' => $refreshDecoded !== null,
                    'has_jti' => !empty($jti),
                    'jti' => $jti,
                ];

                // 準備資料庫資料
                $refreshExpireSeconds = (int) getenv('JWT_REFRESH_TOKEN_EXPIRE') ?: 2592000;
                $tokenData = [
                    'user_id' => $userId,
                    'access_token' => hash('sha256', $accessToken),
                    'refresh_token' => $jti ?: hash('sha256', $refreshToken),
                    'token_type' => 'jwt',
                    'expires_at' => date('Y-m-d H:i:s', time() + $refreshExpireSeconds),
                    'device_id' => $deviceId,
                    'user_agent' => $userAgent,
                    'ip_address' => $ipAddress,
                ];

                $tokenGeneration['token_data'] = [
                    'user_id' => $tokenData['user_id'],
                    'token_type' => $tokenData['token_type'],
                    'expires_at' => $tokenData['expires_at'],
                    'device_id' => substr($tokenData['device_id'], 0, 10) . '...',
                ];

                // 測試資料庫插入（但不實際插入）
                $tokenModel = new \App\Models\UserTokenModel();
                $validation = $tokenModel->validate($tokenData);

                $tokenGeneration['database_validation'] = [
                    'valid' => $validation,
                    'errors' => !$validation ? $tokenModel->errors() : null,
                ];

                // 檢查現有 token 數量
                $existingTokens = $tokenModel->where('user_id', $userId)->countAllResults();
                $tokenGeneration['existing_tokens'] = $existingTokens;

            } catch (\Exception $e) {
                $tokenGeneration['error'] = [
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => array_slice($e->getTrace(), 0, 3),
                ];
            }

            // 3. 檢查最近的錯誤日誌
            $recentErrors = $this->lineLoginLogModel
                ->where('step LIKE', '%generate_token%')
                ->where('status', 'error')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->find();

            $diagnosis['token_generation'] = $tokenGeneration;
            $diagnosis['recent_token_errors'] = array_map(function($error) {
                return [
                    'id' => $error['id'],
                    'session_id' => $error['session_id'],
                    'step' => $error['step'],
                    'error_message' => $error['error_message'],
                    'created_at' => $error['created_at'],
                    'response_data' => json_decode($error['response_data'], true),
                ];
            }, $recentErrors);

            return $this->respond([
                'success' => true,
                'user_id' => $userId,
                'diagnosis' => $diagnosis,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            return $this->fail([
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

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
