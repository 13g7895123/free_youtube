<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use App\Models\GuestSessionModel;
use App\Models\LineLoginLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    protected $userModel;
    protected $tokenModel;
    protected $guestSessionModel;
    protected $lineLoginLogModel;

    public function __construct()
    {
        helper('cookie');
        $this->userModel = new UserModel();
        $this->tokenModel = new UserTokenModel();
        $this->guestSessionModel = new GuestSessionModel();
        $this->lineLoginLogModel = new LineLoginLogModel();
    }

    /**
     * LINE Login 重定向
     *
     * @return ResponseInterface
     */
    public function lineLogin()
    {
        // 檢查是否為 Mock 模式
        if (env('AUTH_MODE') === 'mock') {
            return $this->fail('目前使用 Mock 認證模式，請點擊「登入」按鈕使用 Mock 登入', 403);
        }

        $channelId = env('LINE_LOGIN_CHANNEL_ID');
        $callbackUrl = env('LINE_LOGIN_CALLBACK_URL');

        if (!$channelId || !$callbackUrl) {
            return $this->fail('LINE Login 設定錯誤：請設定 LINE_LOGIN_CHANNEL_ID 和 LINE_LOGIN_CALLBACK_URL 環境變數', 500);
        }

        // 生成隨機 state 用於 CSRF 防護
        $state = bin2hex(random_bytes(16));
        session()->set('line_oauth_state', $state);

        // 構建 LINE OAuth 授權 URL
        $params = [
            'response_type' => 'code',
            'client_id' => $channelId,
            'redirect_uri' => $callbackUrl,
            'state' => $state,
            'scope' => 'profile openid email'
        ];

        $authUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);

        // 重定向到 LINE 授權頁面
        return redirect()->to($authUrl);
    }

    /**
     * LINE OAuth 回調處理
     *
     * @return ResponseInterface
     */
    public function lineCallback()
    {
        // 生成本次登入的 session ID
        $sessionId = uniqid('line_login_', true);
        $ip = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();
        
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        // 記錄開始
        $this->lineLoginLogModel->logStep($sessionId, 'callback_start', 'success', [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'request' => [
                'query_params' => $this->request->getGet()
            ]
        ]);

        // 檢查是否有錯誤參數（使用者取消授權）
        $error = $this->request->getGet('error');
        if ($error) {
            $errorDescription = $this->request->getGet('error_description') ?? '授權已取消';
            log_message('info', "LINE OAuth error: {$error} - {$errorDescription}");

            $this->lineLoginLogModel->logStep($sessionId, 'callback_start', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'error' => "User cancelled: {$error} - {$errorDescription}"
            ]);

            return redirect()->to($frontendUrl . '/?login=cancelled&message=' . urlencode('您已取消 LINE 登入'));
        }

        // 驗證 state 參數（CSRF 防護）
        $state = $this->request->getGet('state');
        $sessionState = session()->get('line_oauth_state');

        if (!$state || $state !== $sessionState) {
            log_message('warning', 'LINE OAuth state mismatch');
            
            $this->lineLoginLogModel->logStep($sessionId, 'validate_state', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'error' => 'State mismatch - CSRF validation failed',
                'request' => [
                    'state' => $state,
                    'session_state' => $sessionState
                ]
            ]);
            
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('登入請求無效，請重試'));
        }

        // 清除 session state
        session()->remove('line_oauth_state');

        // 取得授權碼
        $code = $this->request->getGet('code');
        if (!$code) {
            log_message('error', 'LINE OAuth callback missing code');
            
            $this->lineLoginLogModel->logStep($sessionId, 'get_code', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'error' => 'Authorization code missing from callback'
            ]);
            
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('授權失敗，請重試'));
        }

        // 使用授權碼換取 access token
        $tokenData = $this->getLineAccessToken($code, $sessionId, $ip, $userAgent);
        if (!$tokenData) {
            log_message('error', 'Failed to get LINE access token');
            
            $this->lineLoginLogModel->logStep($sessionId, 'get_token', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'error' => 'Failed to exchange code for access token'
            ]);
            
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法取得 LINE 授權，請檢查網路連線後重試'));
        }

        // 使用 access token 取得用戶資料
        $lineUserData = $this->getLineUserProfile($tokenData['access_token'], $sessionId, $ip, $userAgent);
        if (!$lineUserData) {
            log_message('error', 'Failed to get LINE user profile');
            
            $this->lineLoginLogModel->logStep($sessionId, 'get_profile', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'error' => 'Failed to get user profile from LINE API'
            ]);
            
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法取得用戶資料，請重試'));
        }

        // 建立或更新用戶
        $user = $this->createOrUpdateUser($lineUserData, $sessionId, $ip, $userAgent);
        if (!$user) {
            log_message('error', 'Failed to create/update user');
            
            $this->lineLoginLogModel->logStep($sessionId, 'create_user', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'line_user_id' => $lineUserData['userId'] ?? null,
                'error' => 'Failed to create or update user in database'
            ]);
            
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法建立用戶帳號，請稍後再試'));
        }

        // 檢查是否為 soft deleted 用戶
        $wasRestored = false;
        if ($user['deleted_at'] !== null) {
            // 帳號已刪除，執行恢復流程
            $restored = $this->userModel->restoreUser($user['id']);
            if ($restored) {
                $wasRestored = true;
                // 重新取得用戶資料
                $user = $this->userModel->find($user['id']);
            }
        }

        // 生成應用 token
        $appToken = $this->generateUserToken($user['id']);
        if (!$appToken) {
            log_message('error', 'Failed to generate user token');
            
            $this->lineLoginLogModel->logStep($sessionId, 'create_token', 'error', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'line_user_id' => $lineUserData['userId'] ?? null,
                'error' => 'Failed to generate authentication token'
            ]);
            
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法生成認證憑證，請重試'));
        }

        // 記錄完成
        $this->lineLoginLogModel->logStep($sessionId, 'complete', 'success', [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'line_user_id' => $lineUserData['userId'] ?? null,
            'response' => [
                'user_id' => $user['id'],
                'was_restored' => $wasRestored
            ]
        ]);

        // 設置 HTTP-only cookie（包含 access_token 和 refresh_token）
        $this->setAuthCookie($appToken['access_token'], $appToken['refresh_token']);

        // 重定向到前端首頁（登入成功）
        $redirectUrl = $frontendUrl . '/?login=success';
        if ($wasRestored) {
            $redirectUrl .= '&restored=1';
        }
        return redirect()->to($redirectUrl);
    }

    /**
     * 取得當前登入用戶資訊
     *
     * @return ResponseInterface
     */
    public function user()
    {
        // 從 AuthFilter 注入的 userId 取得用戶
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        $user = $this->userModel->find($userId);
        if (!$user || $user['deleted_at'] !== null) {
            return $this->fail('用戶不存在', 404);
        }

        // 移除敏感資訊
        unset($user['deleted_at']);

        return $this->respond([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * 測試使用者建立功能（僅開發環境）
     *
     * @return ResponseInterface
     */
    public function testUserCreation()
    {
        // 安全檢查：僅允許開發環境
        if (env('CI_ENVIRONMENT') === 'production') {
            return $this->fail('此功能僅在開發環境可用', 403);
        }

        $results = [];

        // 測試資料
        $testLineUserData = [
            'userId' => 'test_user_' . time(),
            'displayName' => '測試使用者（驗證 status 修復）',
            'pictureUrl' => 'https://example.com/test-avatar.jpg',
            'email' => 'test-status-fix@example.com'
        ];

        $sessionId = 'test_creation_' . time();
        $ip = '127.0.0.1';
        $userAgent = 'Test Agent';

        // 嘗試建立使用者
        try {
            $user = $this->createOrUpdateUser($testLineUserData, $sessionId, $ip, $userAgent);

            if ($user) {
                $results['success'] = true;
                $results['message'] = '使用者建立成功！status 欄位修復有效';
                $results['user'] = [
                    'id' => $user['id'],
                    'line_user_id' => $user['line_user_id'],
                    'display_name' => $user['display_name'],
                    'status' => $user['status'],
                    'created_at' => $user['created_at']
                ];

                // 清理測試資料
                $this->userModel->delete($user['id']);
                $results['cleanup'] = '測試資料已清理';
            } else {
                $results['success'] = false;
                $results['message'] = '使用者建立失敗';
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['message'] = '建立使用者時發生例外';
            $results['error'] = $e->getMessage();
        }

        // 檢查 log 中是否有 status 相關錯誤
        $recentLogs = $this->lineLoginLogModel
            ->where('session_id', $sessionId)
            ->findAll();

        $results['logs'] = $recentLogs;

        return $this->respond($results);
    }

    /**
     * 插入測試資料（僅開發環境）
     *
     * @return ResponseInterface
     */
    public function seedTestLogs()
    {
        // 安全檢查：僅允許開發環境
        if (env('CI_ENVIRONMENT') === 'production') {
            return $this->fail('此功能僅在開發環境可用', 403);
        }

        $testData = [
            [
                'session_id' => 'test_session_001',
                'step' => 'callback_start',
                'status' => 'success',
                'line_user_id' => 'U1234567890abcdef',
                'request_data' => json_encode(['query_params' => ['code' => 'abc123', 'state' => 'xyz789']]),
                'response_data' => null,
                'error_message' => null,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'session_id' => 'test_session_001',
                'step' => 'get_token',
                'status' => 'success',
                'line_user_id' => 'U1234567890abcdef',
                'request_data' => json_encode(['grant_type' => 'authorization_code']),
                'response_data' => json_encode(['has_access_token' => true, 'token_type' => 'Bearer']),
                'error_message' => null,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours +5 seconds'))
            ],
            [
                'session_id' => 'test_session_002',
                'step' => 'callback_start',
                'status' => 'error',
                'line_user_id' => null,
                'request_data' => json_encode(['query_params' => ['error' => 'access_denied']]),
                'response_data' => null,
                'error_message' => 'User cancelled: access_denied - 使用者取消授權',
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'session_id' => 'test_session_003',
                'step' => 'get_profile',
                'status' => 'warning',
                'line_user_id' => 'U9876543210fedcba',
                'request_data' => null,
                'response_data' => json_encode(['user_id' => 'U9876543210fedcba', 'display_name' => '測試使用者']),
                'error_message' => null,
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
            ]
        ];

        $db = \Config\Database::connect();
        $inserted = 0;

        foreach ($testData as $data) {
            if ($db->table('line_login_logs')->insert($data)) {
                $inserted++;
            }
        }

        return $this->respond([
            'success' => true,
            'message' => "成功插入 {$inserted} 筆測試資料",
            'data' => ['inserted' => $inserted]
        ]);
    }

    /**
     * 查詢 LINE 登入 logs（開發用）
     *
     * @return ResponseInterface
     */
    public function getLineLoginLogs()
    {
        $sessionId = $this->request->getGet('session_id');
        $lineUserId = $this->request->getGet('line_user_id');
        $status = $this->request->getGet('status');
        $limit = (int) ($this->request->getGet('limit') ?: 50);

        $builder = $this->lineLoginLogModel->builder();

        if ($sessionId) {
            $builder->where('session_id', $sessionId);
        }

        if ($lineUserId) {
            $builder->where('line_user_id', $lineUserId);
        }

        if ($status) {
            $builder->where('status', $status);
        }

        $logs = $builder->orderBy('id', 'DESC')
                       ->limit($limit)
                       ->get()
                       ->getResultArray();

        // 格式化輸出
        foreach ($logs as &$log) {
            if ($log['request_data']) {
                $log['request_data'] = json_decode($log['request_data'], true);
            }
            if ($log['response_data']) {
                $log['response_data'] = json_decode($log['response_data'], true);
            }
        }

        return $this->respond([
            'success' => true,
            'data' => $logs,
            'count' => count($logs)
        ]);
    }

    /**
     * 取得最近的 LINE 登入錯誤（開發用）
     *
     * @return ResponseInterface
     */
    public function getLineLoginErrors()
    {
        $limit = (int) ($this->request->getGet('limit') ?: 50);
        $errors = $this->lineLoginLogModel->getRecentErrors($limit);

        // 格式化輸出
        foreach ($errors as &$error) {
            if ($error['request_data']) {
                $error['request_data'] = json_decode($error['request_data'], true);
            }
            if ($error['response_data']) {
                $error['response_data'] = json_decode($error['response_data'], true);
            }
        }

        return $this->respond([
            'success' => true,
            'data' => $errors,
            'count' => count($errors)
        ]);
    }

    /**
     * 登出
     *
     * @return ResponseInterface
     */
    public function logout()
    {
        $userId = $this->request->userId ?? null;

        if ($userId) {
            // 撤銷所有用戶 token
            $this->tokenModel->revokeAllUserTokens($userId);
        }

        // 清除 cookie（需要與 set_cookie 時使用相同的參數）
        $isProduction = env('CI_ENVIRONMENT') === 'production';
        delete_cookie('access_token', '', '/', '', $isProduction);

        return $this->respond([
            'success' => true,
            'message' => '登出成功'
        ]);
    }

    /**
     * 刷新 Token（使用 JWT）
     *
     * @return ResponseInterface
     */
    public function refresh()
    {
        $refreshToken = get_cookie('refresh_token');

        if (!$refreshToken) {
            return $this->fail('未提供 refresh token', 401);
        }

        // 使用 JWT 驗證 refresh token
        $decoded = JwtHelper::verifyToken($refreshToken, 'refresh');

        if (!$decoded) {
            return $this->fail('refresh token 無效或已過期', 401);
        }

        $userId = $decoded->sub;
        $jti = $decoded->jti ?? null;

        // 檢查 refresh token 是否已被撤銷（從資料庫檢查 jti）
        if ($jti) {
            $tokenData = $this->tokenModel
                ->where('refresh_token', $jti)
                ->where('user_id', $userId)
                ->first();

            if (!$tokenData) {
                log_message('warning', "Refresh token jti={$jti} not found in database or already revoked");
                return $this->fail('refresh token 已被撤銷', 401);
            }

            // 撤銷舊 refresh token（標記為已使用）
            $this->tokenModel->delete($tokenData['id']);
        }

        // 生成新的 token pair
        $newToken = $this->generateUserToken($userId);
        if (!$newToken) {
            return $this->fail('無法生成新 token', 500);
        }

        // 設置新 cookie（包含新的 access_token 和 refresh_token）
        $this->setAuthCookie($newToken['access_token'], $newToken['refresh_token']);

        log_message('info', "Token refreshed for user_id={$userId}");

        return $this->respond([
            'success' => true,
            'message' => 'Token 已更新',
            'data' => [
                'access_expires_in' => $newToken['access_expires_in'],
                'refresh_expires_in' => $newToken['refresh_expires_in']
            ]
        ]);
    }

    /**
     * 遷移訪客資料到會員帳號
     *
     * @return ResponseInterface
     */
    public function migrateGuestData()
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        // 接收前端傳來的訪客歷史記錄
        $guestHistory = $this->request->getJSON(true)['history'] ?? [];

        if (empty($guestHistory) || !is_array($guestHistory)) {
            return $this->respond([
                'success' => true,
                'message' => '無訪客資料需要遷移',
                'data' => ['migrated_count' => 0]
            ]);
        }

        // 使用 VideoLibraryModel 批次寫入
        $videoLibraryModel = new \App\Models\VideoLibraryModel();
        $migratedCount = 0;
        $skippedCount = 0;

        // 檢查會員影片總數（包含即將遷移的）
        $currentCount = $videoLibraryModel->where('user_id', $userId)->countAllResults();
        $maxVideos = 10000;

        foreach ($guestHistory as $item) {
            // 驗證必要欄位
            if (empty($item['videoId'])) {
                $skippedCount++;
                continue;
            }

            // 檢查是否超過上限
            if ($currentCount + $migratedCount >= $maxVideos) {
                log_message('warning', "User {$userId} reached video limit during migration");
                break;
            }

            // 檢查是否已存在
            $exists = $videoLibraryModel->isVideoInLibrary($userId, $item['videoId']);
            if ($exists) {
                $skippedCount++;
                continue;
            }

            // 插入影片到影片庫
            $videoData = [
                'user_id' => $userId,
                'video_id' => $item['videoId'],
                'title' => $item['title'] ?? '未知標題',
                'thumbnail_url' => $item['thumbnail'] ?? '',
                'added_at' => date('Y-m-d H:i:s', $item['playedAt'] ?? time())
            ];

            try {
                $videoLibraryModel->insert($videoData);
                $migratedCount++;
            } catch (\Exception $e) {
                log_message('error', "Failed to migrate video {$item['videoId']}: " . $e->getMessage());
                $skippedCount++;
            }
        }

        return $this->respond([
            'success' => true,
            'message' => '訪客資料遷移完成',
            'data' => [
                'migrated_count' => $migratedCount,
                'skipped_count' => $skippedCount,
                'total_processed' => count($guestHistory)
            ]
        ]);
    }

    // ========== 私有輔助方法 ==========

    /**
     * 使用授權碼換取 LINE access token
     *
     * @param string $code
     * @param string $sessionId
     * @param string $ip
     * @param string $userAgent
     * @return array|null
     */
    private function getLineAccessToken(string $code, string $sessionId = '', string $ip = '', string $userAgent = ''): ?array
    {
        $channelId = env('LINE_LOGIN_CHANNEL_ID');
        $channelSecret = env('LINE_LOGIN_CHANNEL_SECRET');
        $callbackUrl = env('LINE_LOGIN_CALLBACK_URL');

        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $callbackUrl,
            'client_id' => $channelId,
            'client_secret' => $channelSecret
        ];

        // 記錄請求
        if ($sessionId) {
            $this->lineLoginLogModel->logStep($sessionId, 'get_token', 'success', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'request' => [
                    'url' => 'https://api.line.me/oauth2/v2.1/token',
                    'grant_type' => 'authorization_code',
                    'has_code' => !empty($code),
                    'has_client_id' => !empty($channelId),
                    'has_client_secret' => !empty($channelSecret)
                ]
            ]);
        }

        $ch = curl_init('https://api.line.me/oauth2/v2.1/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'LINE token API error: ' . $response);
            
            // 記錄錯誤
            if ($sessionId) {
                $this->lineLoginLogModel->logStep($sessionId, 'get_token', 'error', [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'error' => "HTTP {$httpCode}: " . ($curlError ?: $response),
                    'response' => [
                        'http_code' => $httpCode,
                        'response_body' => $response,
                        'curl_error' => $curlError
                    ]
                ]);
            }
            
            return null;
        }

        $tokenData = json_decode($response, true);
        
        // 記錄成功回應（隱藏敏感資訊）
        if ($sessionId && $tokenData) {
            $this->lineLoginLogModel->logStep($sessionId, 'get_token', 'success', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'response' => [
                    'has_access_token' => !empty($tokenData['access_token']),
                    'token_type' => $tokenData['token_type'] ?? null,
                    'expires_in' => $tokenData['expires_in'] ?? null,
                    'scope' => $tokenData['scope'] ?? null
                ]
            ]);
        }

        return $tokenData;
    }

    /**
     * 使用 access token 取得 LINE 用戶資料
     *
     * @param string $accessToken
     * @param string $sessionId
     * @param string $ip
     * @param string $userAgent
     * @return array|null
     */
    private function getLineUserProfile(string $accessToken, string $sessionId = '', string $ip = '', string $userAgent = ''): ?array
    {
        $ch = curl_init('https://api.line.me/v2/profile');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'LINE profile API error: ' . $response);
            
            // 記錄錯誤
            if ($sessionId) {
                $this->lineLoginLogModel->logStep($sessionId, 'get_profile', 'error', [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'error' => "HTTP {$httpCode}: " . ($curlError ?: $response),
                    'response' => [
                        'http_code' => $httpCode,
                        'response_body' => $response,
                        'curl_error' => $curlError
                    ]
                ]);
            }
            
            return null;
        }

        $userData = json_decode($response, true);
        
        // 記錄成功回應
        if ($sessionId && $userData) {
            $this->lineLoginLogModel->logStep($sessionId, 'get_profile', 'success', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'line_user_id' => $userData['userId'] ?? null,
                'response' => [
                    'user_id' => $userData['userId'] ?? null,
                    'display_name' => $userData['displayName'] ?? null,
                    'has_picture' => !empty($userData['pictureUrl']),
                    'has_email' => !empty($userData['email'])
                ]
            ]);
        }
        
        return $userData;
    }

    /**
     * 建立或更新用戶
     *
     * @param array $lineUserData
     * @param string $sessionId
     * @param string $ip
     * @param string $userAgent
     * @return array|null
     */
    private function createOrUpdateUser(array $lineUserData, string $sessionId = '', string $ip = '', string $userAgent = ''): ?array
    {
        $lineUserId = $lineUserData['userId'];

        // 查找是否已存在（包含 soft deleted）
        $existingUser = $this->userModel
            ->where('line_user_id', $lineUserId)
            ->withDeleted()
            ->first();

        $userData = [
            'line_user_id' => $lineUserId,
            'display_name' => $lineUserData['displayName'] ?? '',
            'avatar_url' => $lineUserData['pictureUrl'] ?? '',
            'email' => $lineUserData['email'] ?? null,
            'status' => 'active'
        ];

        try {
            if ($existingUser) {
                // 更新現有用戶 - 動態設置驗證規則以排除當前使用者 ID
                $this->userModel->setValidationRules([
                    'line_user_id' => "required|max_length[255]|is_unique[users.line_user_id,id,{$existingUser['id']}]",
                    'display_name' => 'required|max_length[255]',
                    'avatar_url' => 'permit_empty|valid_url',
                    'status' => 'required|in_list[active,soft_deleted]'
                ]);

                $updateResult = $this->userModel->update($existingUser['id'], $userData);
                
                if (!$updateResult) {
                    $errors = $this->userModel->errors();
                    log_message('error', 'User update failed: ' . json_encode($errors));
                    
                    if ($sessionId) {
                        $this->lineLoginLogModel->logStep($sessionId, 'create_user', 'error', [
                            'ip' => $ip,
                            'user_agent' => $userAgent,
                            'line_user_id' => $lineUserId,
                            'error' => 'Update user failed: ' . json_encode($errors),
                            'request' => $userData
                        ]);
                    }
                    
                    return null;
                }
                
                $user = $this->userModel->withDeleted()->find($existingUser['id']);
                
                if ($sessionId) {
                    $this->lineLoginLogModel->logStep($sessionId, 'create_user', 'success', [
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'line_user_id' => $lineUserId,
                        'response' => [
                            'user_id' => $existingUser['id'],
                            'action' => 'updated'
                        ]
                    ]);
                }
                
                return $user;
            } else {
                // 建立新用戶
                $userId = $this->userModel->insert($userData);
                
                if (!$userId) {
                    $errors = $this->userModel->errors();
                    log_message('error', 'User insert failed: ' . json_encode($errors));
                    log_message('error', 'Last query: ' . $this->userModel->db->getLastQuery());
                    
                    if ($sessionId) {
                        $this->lineLoginLogModel->logStep($sessionId, 'create_user', 'error', [
                            'ip' => $ip,
                            'user_agent' => $userAgent,
                            'line_user_id' => $lineUserId,
                            'error' => 'Insert user failed: ' . json_encode($errors),
                            'request' => $userData,
                            'response' => [
                                'last_query' => $this->userModel->db->getLastQuery()
                            ]
                        ]);
                    }
                    
                    return null;
                }
                
                $user = $this->userModel->find($userId);
                
                if ($sessionId) {
                    $this->lineLoginLogModel->logStep($sessionId, 'create_user', 'success', [
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'line_user_id' => $lineUserId,
                        'response' => [
                            'user_id' => $userId,
                            'action' => 'created'
                        ]
                    ]);
                }
                
                return $user;
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception in createOrUpdateUser: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            if ($sessionId) {
                $this->lineLoginLogModel->logStep($sessionId, 'create_user', 'error', [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'line_user_id' => $lineUserId,
                    'error' => 'Exception: ' . $e->getMessage(),
                    'request' => $userData,
                    'response' => [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ]);
            }
            
            return null;
        }
    }

    /**
     * 生成應用 token（使用 JWT）
     *
     * @param int $userId
     * @return array|null
     */
    private function generateUserToken(int $userId): ?array
    {
        try {
            // 生成 JWT access token
            $accessToken = JwtHelper::generateAccessToken($userId);

            // 生成 JWT refresh token（包含 device_id 用於多裝置管理）
            $deviceId = md5($this->request->getUserAgent()->getAgentString() . $this->request->getIPAddress());
            $refreshToken = JwtHelper::generateRefreshToken($userId, $deviceId);

            // 解碼 refresh token 取得 jti（用於撤銷機制）
            $refreshDecoded = JwtHelper::decode($refreshToken);
            $jti = $refreshDecoded->jti ?? null;

            // 將 refresh token 資訊存入資料庫（用於撤銷檢查）
            $refreshExpireSeconds = (int) env('JWT_REFRESH_TOKEN_EXPIRE', 2592000);
            $tokenData = [
                'user_id' => $userId,
                'access_token' => hash('sha256', $accessToken), // 保留用於相容性
                'refresh_token' => hash('sha256', $refreshToken),
                'token_type' => 'jwt',
                'expires_at' => date('Y-m-d H:i:s', time() + $refreshExpireSeconds),
                'device_id' => $deviceId,
                'user_agent' => $this->request->getUserAgent()->getAgentString(),
                'ip_address' => $this->request->getIPAddress()
            ];

            // 如果有 jti，儲存到資料庫
            if ($jti) {
                $tokenData['refresh_token'] = $jti; // 使用 jti 作為識別
            }

            $inserted = $this->tokenModel->insert($tokenData);
            if (!$inserted) {
                log_message('error', 'Failed to insert token data to database');
                return null;
            }

            $accessExpireSeconds = (int) env('JWT_ACCESS_TOKEN_EXPIRE', 900);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_expires_in' => $accessExpireSeconds,
                'refresh_expires_in' => $refreshExpireSeconds
            ];
        } catch (\Exception $e) {
            log_message('error', 'Failed to generate JWT token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 設置認證 cookie（Access Token 和 Refresh Token）
     *
     * @param string $accessToken
     * @param string|null $refreshToken
     * @return void
     */
    private function setAuthCookie(string $accessToken, ?string $refreshToken = null): void
    {
        $isProduction = env('CI_ENVIRONMENT') === 'production';
        $cookieDomain = env('COOKIE_DOMAIN', '');

        // Access Token Cookie（短期有效）
        $accessExpireSeconds = (int) env('JWT_ACCESS_TOKEN_EXPIRE', 900); // 預設 15 分鐘
        $accessCookieConfig = [
            'name' => 'access_token',
            'value' => $accessToken,
            'expire' => $accessExpireSeconds,
            'path' => '/',
            'secure' => $isProduction,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        if (!empty($cookieDomain)) {
            $accessCookieConfig['domain'] = $cookieDomain;
        }

        set_cookie($accessCookieConfig);
        log_message('debug', 'Access token cookie set: expires=' . $accessExpireSeconds . 's');

        // Refresh Token Cookie（長期有效）
        if ($refreshToken !== null) {
            $refreshExpireSeconds = (int) env('JWT_REFRESH_TOKEN_EXPIRE', 2592000); // 預設 30 天
            $refreshCookieConfig = [
                'name' => 'refresh_token',
                'value' => $refreshToken,
                'expire' => $refreshExpireSeconds,
                'path' => '/',
                'secure' => $isProduction,
                'httponly' => true,
                'samesite' => 'Lax'
            ];

            if (!empty($cookieDomain)) {
                $refreshCookieConfig['domain'] = $cookieDomain;
            }

            set_cookie($refreshCookieConfig);
            log_message('debug', 'Refresh token cookie set: expires=' . $refreshExpireSeconds . 's');
        }
    }

    /**
     * Mock 登入 (僅開發環境)
     *
     * @return ResponseInterface
     */
    public function mockLogin()
    {
        // 安全檢查：僅允許開發環境
        if (env('CI_ENVIRONMENT') === 'production') {
            return $this->fail('Mock 登入僅在開發環境可用', 403);
        }

        if (env('AUTH_MODE') !== 'mock') {
            return $this->fail('Mock 模式未啟用', 403);
        }

        // 從環境變數讀取 Mock 使用者 ID
        $mockUserId = (int) env('MOCK_USER_ID', 1);

        try {
            // 檢查 Mock 使用者是否存在
            $user = $this->userModel->find($mockUserId);
            if (!$user) {
                return $this->fail("Mock 使用者不存在 (ID: {$mockUserId})，請先執行: php spark db:seed MockUserSeeder", 404);
            }

            // 檢查使用者是否為 soft deleted
            if ($user['deleted_at'] !== null) {
                return $this->fail('Mock 使用者已被刪除', 404);
            }

            // 生成 token (與 LINE Login 流程相同)
            $appToken = $this->generateUserToken($mockUserId);
            if (!$appToken) {
                return $this->fail('無法生成認證憑證', 500);
            }

            // 設置 cookie（包含 access_token 和 refresh_token）
            $this->setAuthCookie($appToken['access_token'], $appToken['refresh_token']);

            log_message('info', "Mock login successful for user_id: {$mockUserId}");

            // 返回 JSON (而非重定向，方便前端處理)
            return $this->respond([
                'success' => true,
                'message' => 'Mock 登入成功',
                'data' => [
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            // 資料庫連線失敗時，返回假的 Mock 使用者資料（僅開發環境）
            log_message('warning', "Mock login fallback (database unavailable): {$e->getMessage()}");

            // 創建假的使用者資料
            $mockUser = [
                'id' => $mockUserId,
                'line_user_id' => 'mock_line_user_001',
                'display_name' => 'Mock 測試使用者',
                'avatar_url' => 'https://via.placeholder.com/150/667eea/ffffff?text=MOCK',
                'email' => 'mock@example.com',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // 生成 JWT token（簡化模式，不儲存到資料庫）
            try {
                $fakeAccessToken = JwtHelper::generateAccessToken($mockUserId);
                $fakeRefreshToken = JwtHelper::generateRefreshToken($mockUserId);
                $this->setAuthCookie($fakeAccessToken, $fakeRefreshToken);
            } catch (\Exception $e) {
                log_message('error', 'Failed to generate mock JWT: ' . $e->getMessage());
                return $this->fail('無法生成認證憑證', 500);
            }

            return $this->respond([
                'success' => true,
                'message' => 'Mock 登入成功（簡化模式，資料庫未連線）',
                'data' => [
                    'user' => $mockUser
                ]
            ]);
        }
    }
}
