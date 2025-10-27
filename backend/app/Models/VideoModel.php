<?php

namespace App\Models;

use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table      = 'videos';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType       = \App\Entities\Video::class;
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'video_id',
        'title',
        'description',
        'duration',
        'thumbnail_url',
        'youtube_url',
        'channel_id',
        'channel_name',
        'published_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * 根據 YouTube 影片 ID 取得影片
     */
    public function findByYoutubeId(string $videoId)
    {
        return $this->where('video_id', $videoId)->first();
    }

    /**
     * 搜尋影片
     */
    public function search(string $query)
    {
        return $this->like('title', $query)
                    ->orLike('description', $query)
                    ->orLike('channel_name', $query)
                    ->findAll();
    }

    /**
     * 取得最近新增的影片
     */
    public function getRecentVideos(int $limit = 10)
    {
        return $this->orderBy('created_at', 'DESC')->limit($limit)->findAll();
    }

    /**
     * 取得指定頻道的影片
     */
    public function getVideosByChannel(string $channelId)
    {
        return $this->where('channel_id', $channelId)->orderBy('published_at', 'DESC')->findAll();
    }

    /**
     * 檢查影片是否存在
     */
    public function exists(string $videoId): bool
    {
        return $this->where('video_id', $videoId)->first() !== null;
    }
}
