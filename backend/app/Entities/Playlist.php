<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Playlist extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];

    /**
     * 取得播放清單的簡短描述
     */
    public function getShortDescription(int $length = 100): string
    {
        if (!$this->description || strlen($this->description) <= $length) {
            return $this->description ?? '';
        }
        return substr($this->description, 0, $length) . '...';
    }

    /**
     * 檢查播放清單是否為空
     */
    public function isEmpty(): bool
    {
        return $this->item_count === 0 || is_null($this->item_count);
    }

    /**
     * 檢查播放清單是否已被刪除
     */
    public function isSoftDeleted(): bool
    {
        return !is_null($this->deleted_at);
    }

    /**
     * 取得播放清單的狀態
     */
    public function getStatus(): string
    {
        if ($this->isSoftDeleted()) {
            return 'deleted';
        }
        return $this->is_active ? 'active' : 'inactive';
    }
}
