<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $origin = $request->getServer('HTTP_ORIGIN') ?? '*';

        // 從環境變數讀取允許的 origins
        $frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:5173';

        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'http://localhost:8080',
            'http://localhost',
            $frontendUrl,  // 支援生產環境
        ];

        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Cookie');
            header('Access-Control-Expose-Headers: Content-Type, X-Total-Count');
            header('Access-Control-Max-Age: 7200');
            header('Access-Control-Allow-Credentials: true');  // ✅ 允許發送 credentials (cookies)
        }

        // 處理 preflight 請求
        if ($request->getMethod() === 'OPTIONS') {
            return \Config\Services::response()->setStatusCode(204);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
