<?php

namespace App\Controllers;

use App\Models\VideoLibraryModel;
use CodeIgniter\HTTP\ResponseInterface;

class VideoLibrary extends BaseController
{
    protected $videoLibraryModel;

    public function __construct()
    {
        $this->videoLibraryModel = new VideoLibraryModel();
    }

    /**
     * 取得會員影片庫（支援分頁）
     *
     * @return ResponseInterface
     */
    public function index()
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        // 取得分頁參數
        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = (int) ($this->request->getGet('per_page') ?? 20);

        // 限制分頁大小
        $perPage = min($perPage, 100);
        $page = max($page, 1);

        // 計算 offset
        $offset = ($page - 1) * $perPage;

        // 取得影片庫資料
        $videos = $this->videoLibraryModel->getUserLibrary($userId, $perPage, $offset);

        // 取得總數 - 使用 countAllResults(false) 避免重置查詢建構器
        $total = $this->videoLibraryModel->where('user_id', $userId)->countAllResults(false);

        return $this->respond([
            'success' => true,
            'data' => [
                'videos' => $videos,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]
        ]);
    }

    /**
     * 新增影片到影片庫
     *
     * @return ResponseInterface
     */
    public function add()
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        // 取得請求資料
        $videoId = $this->request->getJSON(true)['video_id'] ?? null;
        $title = $this->request->getJSON(true)['title'] ?? '';
        $thumbnailUrl = $this->request->getJSON(true)['thumbnail_url'] ?? '';

        if (!$videoId) {
            return $this->fail('缺少 video_id 參數', 400);
        }

        // 檢查會員影片總數上限（10000）- 使用 countAllResults(false) 避免重置查詢建構器
        $currentCount = $this->videoLibraryModel->where('user_id', $userId)->countAllResults(false);
        if ($currentCount >= 10000) {
            return $this->fail('影片庫已達上限（10000 部）', 400);
        }

        // 檢查是否已存在
        $exists = $this->videoLibraryModel->isVideoInLibrary($userId, $videoId);
        if ($exists) {
            return $this->fail('影片已在影片庫中', 409);
        }

        // 如果沒有提供標題或縮圖，嘗試從 YouTube 抓取
        if (!$title || !$thumbnailUrl) {
            $videoInfo = $this->fetchYouTubeVideoInfo($videoId);
            $title = $title ?: ($videoInfo['title'] ?? '未知標題');
            $thumbnailUrl = $thumbnailUrl ?: ($videoInfo['thumbnail'] ?? '');
        }

        // 新增影片
        $data = [
            'user_id' => $userId,
            'video_id' => $videoId,
            'title' => $title,
            'thumbnail_url' => $thumbnailUrl
        ];

        $inserted = $this->videoLibraryModel->insert($data);

        if (!$inserted) {
            return $this->fail('新增影片失敗', 500);
        }

        // 取得剛新增的影片
        $video = $this->videoLibraryModel->find($inserted);

        return $this->respondCreated([
            'success' => true,
            'message' => '影片已新增到影片庫',
            'data' => $video
        ]);
    }

    /**
     * 從影片庫移除影片
     *
     * @param string $videoId
     * @return ResponseInterface
     */
    public function remove($videoId = null)
    {
        $userId = $this->request->userId ?? null;

        if (!$userId) {
            return $this->fail('未登入', 401);
        }

        if (!$videoId) {
            return $this->fail('缺少 video_id 參數', 400);
        }

        // 驗證影片是否屬於該會員
        $video = $this->videoLibraryModel
            ->where('user_id', $userId)
            ->where('video_id', $videoId)
            ->first();

        if (!$video) {
            return $this->fail('找不到該影片或無權限移除', 404);
        }

        // 刪除影片
        $deleted = $this->videoLibraryModel->delete($video['id']);

        if (!$deleted) {
            return $this->fail('移除影片失敗', 500);
        }

        return $this->respond([
            'success' => true,
            'message' => '影片已從影片庫移除'
        ]);
    }

    // ========== 私有輔助方法 ==========

    /**
     * 從 YouTube 抓取影片資訊
     *
     * @param string $videoId
     * @return array
     */
    private function fetchYouTubeVideoInfo(string $videoId): array
    {
        // 使用 oEmbed API 抓取影片資訊
        $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json";

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                return [
                    'title' => $data['title'] ?? '未知標題',
                    'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg"
                ];
            }
        } catch (\Exception $e) {
            log_message('error', "Failed to fetch YouTube video info: {$e->getMessage()}");
        }

        // 如果抓取失敗，返回預設值
        return [
            'title' => '未知標題',
            'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg"
        ];
    }
}
