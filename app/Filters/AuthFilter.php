<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserTokenModel;

class AuthFilter implements FilterInterface
{
    /**
     * 驗證 HTTP-only cookie 中的 access_token
     *
     * @param RequestInterface $request
     * @param mixed|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // 從 cookie 取得 access_token
        $accessToken = get_cookie('access_token');

        if (!$accessToken) {
            return $this->unauthorizedResponse('未提供認證 Token');
        }

        // 驗證 token
        $tokenModel = new UserTokenModel();
        $tokenData = $tokenModel->findByAccessToken($accessToken);

        if (!$tokenData) {
            return $this->unauthorizedResponse('Token 無效或已過期');
        }

        // 將 user_id 注入到 request 中,供 Controller 使用
        $request->userId = $tokenData['user_id'];
        $request->tokenData = $tokenData;

        return $request;
    }

    /**
     * After filter (不需處理)
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param mixed|null $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }

    /**
     * 返回 401 未授權回應
     *
     * @param string $message
     * @return ResponseInterface
     */
    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = service('response');
        $response->setStatusCode(401);
        $response->setJSON([
            'success' => false,
            'message' => $message,
            'data' => null
        ]);

        return $response;
    }
}
