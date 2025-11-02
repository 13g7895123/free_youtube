<?php

namespace App\Models;

use CodeIgniter\Model;

class PlaylistItemModel extends Model
{
    protected $table      = 'playlist_items';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType       = \App\Entities\PlaylistItem::class;
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'playlist_id',
        'video_id',
        'position',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * 取得播放清單中的所有項目
     */
    public function getPlaylistItems(int $playlistId)
    {
        return $this->where('playlist_id', $playlistId)
                    ->orderBy('position', 'ASC')
                    ->findAll();
    }

    /**
     * 取得播放清單中的影片及其資訊
     */
    public function getPlaylistVideos(int $playlistId)
    {
        $builder = $this->db->table('playlist_items pi');
        $builder->select('v.*, pi.position')
                ->join('videos v', 'v.id = pi.video_id')
                ->where('pi.playlist_id', $playlistId)
                ->where('v.deleted_at', null)
                ->orderBy('pi.position', 'ASC');

        return $builder->get()->getResultObject(\App\Entities\Video::class);
    }

    /**
     * 將影片新增到播放清單
     */
    public function addVideo(int $playlistId, int $videoId)
    {
        // 取得最大位置
        $maxPosition = $this->where('playlist_id', $playlistId)
                            ->selectMax('position')
                            ->asArray()
                            ->first();

        // 修正邏輯：正確處理 position 為 0 的情況
        // 使用 !== null 判斷而不是 truthy 判斷，因為 0 也是有效的 position
        $position = ($maxPosition && $maxPosition['position'] !== null)
            ? $maxPosition['position'] + 1
            : 0;

        return $this->insert([
            'playlist_id' => $playlistId,
            'video_id' => $videoId,
            'position' => $position,
        ]);
    }

    /**
     * 更新項目位置
     */
    public function reorderItems(int $playlistId, array $positions)
    {
        // $positions 應為 [['video_id' => 1, 'position' => 0], ...]
        foreach ($positions as $item) {
            $this->where('playlist_id', $playlistId)
                 ->where('video_id', $item['video_id'])
                 ->update(['position' => $item['position']]);
        }
        return true;
    }

    /**
     * 移除播放清單中的影片
     */
    public function removeVideo(int $playlistId, int $videoId)
    {
        return $this->where('playlist_id', $playlistId)
                    ->where('video_id', $videoId)
                    ->delete();
    }

    /**
     * 檢查影片是否已在播放清單中
     */
    public function isVideoInPlaylist(int $playlistId, int $videoId): bool
    {
        return $this->where('playlist_id', $playlistId)
                    ->where('video_id', $videoId)
                    ->first() !== null;
    }

    /**
     * 取得播放清單中的下一個影片
     * T061: Helper to get next video in playlist
     */
    public function getNextVideo(int $playlistId, int $currentPosition)
    {
        $item = $this->where('playlist_id', $playlistId)
                     ->where('position >', $currentPosition)
                     ->orderBy('position', 'ASC')
                     ->first();
        
        if (!$item) {
            // 如果沒有下一個，回圈回到第一個
            return $this->where('playlist_id', $playlistId)
                        ->orderBy('position', 'ASC')
                        ->first();
        }
        
        return $item;
    }

    /**
     * 取得播放清單中的前一個影片
     * T061: Helper to get previous video in playlist
     */
    public function getPreviousVideo(int $playlistId, int $currentPosition)
    {
        $item = $this->where('playlist_id', $playlistId)
                     ->where('position <', $currentPosition)
                     ->orderBy('position', 'DESC')
                     ->first();
        
        if (!$item) {
            // 如果沒有前一個，回圈到最後一個
            return $this->where('playlist_id', $playlistId)
                        ->orderBy('position', 'DESC')
                        ->first();
        }
        
        return $item;
    }

    /**
     * 取得播放清單中指定位置的影片
     * T061: Helper to get video at specific position
     */
    public function getVideoAtPosition(int $playlistId, int $position)
    {
        return $this->where('playlist_id', $playlistId)
                    ->where('position', $position)
                    ->first();
    }

    /**
     * 取得播放清單的總影片數
     * T061: Helper to count videos in playlist
     */
    public function getPlaylistItemCount(int $playlistId): int
    {
        return $this->where('playlist_id', $playlistId)
                    ->countAllResults();
    }
}
