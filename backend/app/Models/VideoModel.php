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
        'user_id',
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
     * 取得使用者的影片
     */
    public function getUserVideos(int $userId)
    {
        return $this->builder()
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->get()
                    ->getResult($this->returnType);
    }

    /**
     * 根據 YouTube 影片 ID 取得使用者的影片
     */
    public function findByYoutubeId(string $videoId, ?int $userId = null)
    {
        $builder = $this->where('video_id', $videoId);
        
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->first();
    }

    /**
     * 搜尋使用者的影片
     */
    public function search(string $query, ?int $userId = null)
    {
        $builder = $this->builder();
        
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->groupStart()
                    ->like('title', $query)
                    ->orLike('description', $query)
                    ->orLike('channel_name', $query)
                    ->groupEnd()
                    ->get()
                    ->getResult($this->returnType);
    }

    /**
     * 取得最近新增的影片
     */
    public function getRecentVideos(int $limit = 10, ?int $userId = null)
    {
        $builder = $this->builder();
        
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->get()
                    ->getResult($this->returnType);
    }

    /**
     * 取得指定頻道的影片
     */
    public function getVideosByChannel(string $channelId, ?int $userId = null)
    {
        $builder = $this->where('channel_id', $channelId);
        
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->orderBy('published_at', 'DESC')->findAll();
    }

    /**
     * 檢查影片是否存在
     */
    public function exists(string $videoId, ?int $userId = null): bool
    {
        $builder = $this->where('video_id', $videoId);
        
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->first() !== null;
    }
}
