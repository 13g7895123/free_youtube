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
                            ->first();

        $position = ($maxPosition && $maxPosition['position']) ? $maxPosition['position'] + 1 : 0;

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
}
