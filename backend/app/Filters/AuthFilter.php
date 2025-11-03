<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserTokenModel;
use App\Helpers\JwtHelper;
use Config\Services;

class AuthFilter implements FilterInterface
{
    /**
     * 驗證請求是否已認證（使用 JWT）
     *
     * @param RequestInterface $request
     * @param null             $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('cookie');

        // 詳細調試：記錄所有 cookies 和相關 headers
        $allCookies = $_COOKIE ?? [];
        $cookieHeader = $request->getServer('HTTP_COOKIE') ?? '(none)';
        $origin = $request->getServer('HTTP_ORIGIN') ?? '(none)';

        log_message('debug', '🔍 AuthFilter - Request Details: ' . json_encode([
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'origin' => $origin,
            'cookie_header' => $cookieHeader,
            'cookies_available' => array_keys($allCookies),
            'has_access_token' => isset($allCookies['access_token'])
        ]));

        // 檢查 access_token cookie 是否存在
        $accessToken = get_cookie('access_token');

        if (!$accessToken) {
            log_message('warning', '⚠️ AuthFilter: No access_token cookie found - ' . json_encode([
                'all_cookies' => array_keys($allCookies),
                'cookie_header' => substr($cookieHeader, 0, 100)
            ]));
            return Services::response()->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => '未登入'
            ]);
        }

        log_message('info', '✅ AuthFilter: access_token cookie found, length=' . strlen($accessToken));

        // Mock 模式：允許簡化的 JWT 驗證（跳過資料庫檢查）
        if (env('AUTH_MODE') === 'mock') {
            // 驗證 JWT（即使在 mock 模式也要驗證簽名）
            $decoded = JwtHelper::verifyToken($accessToken, 'access');

            if ($decoded && isset($decoded->sub)) {
                $request->userId = (int) $decoded->sub;
                log_message('debug', "AuthFilter: Mock mode JWT verified, user_id = {$decoded->sub}");
                return $request;
            }

            // 如果 JWT 驗證失敗，使用環境變數的 MOCK_USER_ID
            $mockUserId = (int) env('MOCK_USER_ID', 1);
            $request->userId = $mockUserId;
            log_message('debug', "AuthFilter: Mock mode fallback, user_id = {$mockUserId}");
            return $request;
        }

        // JWT 驗證模式
        try {
            // 驗證 JWT access token
            $decoded = JwtHelper::verifyToken($accessToken, 'access');

            if (!$decoded) {
                log_message('warning', 'AuthFilter: Invalid or expired JWT');
                return Services::response()->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Token 無效或已過期'
                ]);
            }

            // 從 JWT payload 取得 user_id
            if (!isset($decoded->sub)) {
                log_message('error', 'AuthFilter: JWT missing sub claim');
                return Services::response()->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Token 格式錯誤'
                ]);
            }

            $userId = (int) $decoded->sub;

            // 可選：檢查資料庫是否有相關的 token 記錄（用於撤銷機制）
            // 這裡可以加入額外的黑名單檢查
            $tokenModel = new UserTokenModel();
            $tokenExists = $tokenModel
                ->where('user_id', $userId)
                ->where('token_type', 'jwt')
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->countAllResults() > 0;

            if (!$tokenExists) {
                log_message('info', "AuthFilter: No active JWT session found for user_id={$userId}");
                // 注意：這裡可以選擇允許通過或拒絕
                // 如果要嚴格驗證，取消下面的註解
                // return Services::response()->setStatusCode(401)->setJSON([
                //     'success' => false,
                //     'message' => 'Token 已被撤銷'
                // ]);
            }

            // 注入 userId 到 request 物件
            $request->userId = $userId;
            log_message('debug', "AuthFilter: JWT authenticated user_id = {$userId}");

            return $request;
        } catch (\Exception $e) {
            // JWT 驗證失敗
            log_message('error', "AuthFilter: JWT verification error - {$e->getMessage()}");
            return Services::response()->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Token 驗證失敗'
            ]);
        }
    }

    /**
     * 請求後處理（無需處理）
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param null              $arguments
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
