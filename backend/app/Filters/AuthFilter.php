<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserTokenModel;
use Config\Services;

class AuthFilter implements FilterInterface
{
    /**
     * 驗證請求是否已認證
     *
     * @param RequestInterface $request
     * @param null             $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('cookie');

        // 檢查 access_token cookie 是否存在
        $accessToken = get_cookie('access_token');

        if (!$accessToken) {
            log_message('warning', 'AuthFilter: No access_token cookie found');
            return Services::response()->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => '未登入'
            ]);
        }

        // Mock 模式：跳過 token 驗證，直接注入測試使用者 ID
        if (env('AUTH_MODE') === 'mock') {
            $mockUserId = (int) env('MOCK_USER_ID', 1);
            $request->userId = $mockUserId;
            log_message('debug', "AuthFilter: Mock mode enabled, user_id = {$mockUserId}");
            return $request;
        }

        // LINE Login 模式：驗證 access_token
        try {
            // Hash token 並查詢資料庫
            $hashedToken = hash('sha256', $accessToken);
            $tokenModel = new UserTokenModel();
            $tokenData = $tokenModel->where('access_token', $hashedToken)
                                     ->where('expires_at >', date('Y-m-d H:i:s'))
                                     ->first();

            if (!$tokenData) {
                log_message('warning', 'AuthFilter: Invalid or expired token');
                return Services::response()->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Token 無效或已過期'
                ]);
            }

            // 注入 userId 到 request 物件
            $request->userId = $tokenData['user_id'];
            log_message('debug', "AuthFilter: Authenticated user_id = {$tokenData['user_id']}");

            return $request;
        } catch (\Exception $e) {
            // 資料庫連線失敗時的處理
            log_message('error', "AuthFilter: Database error - {$e->getMessage()}");
            return Services::response()->setStatusCode(503)->setJSON([
                'success' => false,
                'message' => '服務暫時無法使用，請稍後再試'
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
