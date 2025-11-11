<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PlaylistItem extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [];

    /**
     * 取得項目的排序資訊
     */
    public function getPositionInfo(): string
    {
        return "Position: {$this->position}";
    }
}
