<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class Home extends BaseController
{
    use ResponseTrait;

    /**
     * GET /api/health
     * 健康檢查端點
     */
    public function health()
    {
        return $this->respond(api_success([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
        ], '服務正常運作'), 200);
    }

    /**
     * GET /
     * 主頁 - 用於 SPA 應用
     */
    public function index()
    {
        // 返回簡單的歡迎訊息或靜態 HTML
        return $this->respond(api_success([
            'message' => '播放清單管理系統 API',
            'version' => '1.0.0',
            'endpoints' => [
                'videos' => '/api/videos',
                'playlists' => '/api/playlists',
                'health' => '/api/health',
            ],
        ]), 200);
    }
}
