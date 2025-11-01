<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use App\Models\GuestSessionModel;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    protected $userModel;
    protected $tokenModel;
    protected $guestSessionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->tokenModel = new UserTokenModel();
        $this->guestSessionModel = new GuestSessionModel();
    }

    /**
     * LINE Login 重定向
     *
     * @return ResponseInterface
     */
    public function lineLogin()
    {
        $channelId = env('LINE_LOGIN_CHANNEL_ID');
        $callbackUrl = env('LINE_LOGIN_CALLBACK_URL');

        if (!$channelId || !$callbackUrl) {
            return $this->fail('LINE Login 設定錯誤', 500);
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
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        // 檢查是否有錯誤參數（使用者取消授權）
        $error = $this->request->getGet('error');
        if ($error) {
            $errorDescription = $this->request->getGet('error_description') ?? '授權已取消';
            log_message('info', "LINE OAuth error: {$error} - {$errorDescription}");

            return redirect()->to($frontendUrl . '/?login=cancelled&message=' . urlencode('您已取消 LINE 登入'));
        }

        // 驗證 state 參數（CSRF 防護）
        $state = $this->request->getGet('state');
        $sessionState = session()->get('line_oauth_state');

        if (!$state || $state !== $sessionState) {
            log_message('warning', 'LINE OAuth state mismatch');
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('登入請求無效，請重試'));
        }

        // 清除 session state
        session()->remove('line_oauth_state');

        // 取得授權碼
        $code = $this->request->getGet('code');
        if (!$code) {
            log_message('error', 'LINE OAuth callback missing code');
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('授權失敗，請重試'));
        }

        // 使用授權碼換取 access token
        $tokenData = $this->getLineAccessToken($code);
        if (!$tokenData) {
            log_message('error', 'Failed to get LINE access token');
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法取得 LINE 授權，請檢查網路連線後重試'));
        }

        // 使用 access token 取得用戶資料
        $lineUserData = $this->getLineUserProfile($tokenData['access_token']);
        if (!$lineUserData) {
            log_message('error', 'Failed to get LINE user profile');
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法取得用戶資料，請重試'));
        }

        // 建立或更新用戶
        $user = $this->createOrUpdateUser($lineUserData);
        if (!$user) {
            log_message('error', 'Failed to create/update user');
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
            return redirect()->to($frontendUrl . '/?login=error&message=' . urlencode('無法生成認證憑證，請重試'));
        }

        // 設置 HTTP-only cookie
        $this->setAuthCookie($appToken['access_token']);

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

        // 清除 cookie
        delete_cookie('access_token');

        return $this->respond([
            'success' => true,
            'message' => '登出成功'
        ]);
    }

    /**
     * 刷新 Token
     *
     * @return ResponseInterface
     */
    public function refresh()
    {
        $refreshToken = get_cookie('refresh_token');

        if (!$refreshToken) {
            return $this->fail('未提供 refresh token', 401);
        }

        // 驗證 refresh token
        $tokenData = $this->tokenModel->where('refresh_token', $refreshToken)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();

        if (!$tokenData) {
            return $this->fail('refresh token 無效或已過期', 401);
        }

        // 撤銷舊 token
        $this->tokenModel->delete($tokenData['id']);

        // 生成新 token
        $newToken = $this->generateUserToken($tokenData['user_id']);
        if (!$newToken) {
            return $this->fail('無法生成新 token', 500);
        }

        // 設置新 cookie
        $this->setAuthCookie($newToken['access_token']);

        return $this->respond([
            'success' => true,
            'message' => 'Token 已更新'
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
     * @return array|null
     */
    private function getLineAccessToken(string $code): ?array
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

        $ch = curl_init('https://api.line.me/oauth2/v2.1/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'LINE token API error: ' . $response);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * 使用 access token 取得 LINE 用戶資料
     *
     * @param string $accessToken
     * @return array|null
     */
    private function getLineUserProfile(string $accessToken): ?array
    {
        $ch = curl_init('https://api.line.me/v2/profile');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'LINE profile API error: ' . $response);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * 建立或更新用戶
     *
     * @param array $lineUserData
     * @return array|null
     */
    private function createOrUpdateUser(array $lineUserData): ?array
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
            'email' => $lineUserData['email'] ?? null
        ];

        if ($existingUser) {
            // 更新現有用戶
            $this->userModel->update($existingUser['id'], $userData);
            return $this->userModel->withDeleted()->find($existingUser['id']);
        } else {
            // 建立新用戶
            $userId = $this->userModel->insert($userData);
            return $this->userModel->find($userId);
        }
    }

    /**
     * 生成應用 token 並儲存到資料庫
     *
     * @param int $userId
     * @return array|null
     */
    private function generateUserToken(int $userId): ?array
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));
        $expiresSeconds = (int) env('TOKEN_EXPIRE_SECONDS', 2592000); // 預設 30 天

        $tokenData = [
            'user_id' => $userId,
            'access_token' => hash('sha256', $accessToken),
            'refresh_token' => hash('sha256', $refreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $expiresSeconds),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'ip_address' => $this->request->getIPAddress()
        ];

        $inserted = $this->tokenModel->insert($tokenData);
        if (!$inserted) {
            return null;
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresSeconds
        ];
    }

    /**
     * 設置認證 cookie
     *
     * @param string $accessToken
     * @return void
     */
    private function setAuthCookie(string $accessToken): void
    {
        $expiresSeconds = (int) env('TOKEN_EXPIRE_SECONDS', 2592000);

        set_cookie([
            'name' => 'access_token',
            'value' => $accessToken,
            'expire' => $expiresSeconds,
            'path' => '/',
            'secure' => true, // HTTPS only
            'httponly' => true, // 防止 JavaScript 存取
            'samesite' => 'Lax' // CSRF 防護
        ]);
    }
}
