<?php

namespace App\Models;

use CodeIgniter\Model;

class VideoLibraryModel extends Model
{
    protected $table = 'video_library';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'video_id',
        'title',
        'thumbnail_url',
        'duration',
        'channel_name'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_not_unique[users.id]',
        'video_id' => 'required|max_length[20]',
        'title' => 'required|max_length[255]'
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 取得會員的影片庫
     */
    public function getUserLibrary(int $userId, int $limit = 100, int $offset = 0)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit, $offset);
    }

    /**
     * 檢查影片是否已在影片庫
     */
    public function isVideoInLibrary(int $userId, string $videoId): bool
    {
        return $this->where(['user_id' => $userId, 'video_id' => $videoId])->countAllResults() > 0;
    }
}
