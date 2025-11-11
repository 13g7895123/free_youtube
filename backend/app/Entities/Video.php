<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Video extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'published_at'];
    protected $casts   = [];

    /**
     * 取得影片的完整 URL
     */
    public function getFullUrl(): string
    {
        return $this->youtube_url;
    }

    /**
     * 取得影片的簡短描述
     */
    public function getShortDescription(int $length = 100): string
    {
        if (strlen($this->description) <= $length) {
            return $this->description;
        }
        return substr($this->description, 0, $length) . '...';
    }

    /**
     * 取得影片的時長格式化字串
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration) {
            return 'Unknown';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * 檢查影片是否已被刪除
     */
    public function isSoftDeleted(): bool
    {
        return !is_null($this->deleted_at);
    }
}
