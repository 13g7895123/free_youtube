<?php

namespace App\Models;

use CodeIgniter\Model;

class PlaylistModel extends Model
{
    protected $table      = 'playlists';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType       = \App\Entities\Playlist::class;
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'user_id',
        'name',
        'description',
        'is_active',
        'item_count',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * 取得使用者的播放清單
     */
    public function getUserPlaylists(int $userId)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * 取得所有活躍的播放清單
     */
    public function getActive()
    {
        return $this->where('is_active', true)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * 搜尋播放清單
     */
    public function search(string $query)
    {
        return $this->like('name', $query)
                    ->orLike('description', $query)
                    ->findAll();
    }

    /**
     * 取得播放清單及其項目
     */
    public function getWithItems(int $id)
    {
        $playlist = $this->find($id);

        if ($playlist) {
            $itemModel = new PlaylistItemModel();
            $playlist->items = $itemModel->getPlaylistVideos($id);
        }

        return $playlist;
    }

    /**
     * 更新播放清單項目計數
     */
    public function updateItemCount(int $playlistId)
    {
        $itemModel = new PlaylistItemModel();
        $count = $itemModel->where('playlist_id', $playlistId)->countAllResults();

        return $this->update($playlistId, ['item_count' => $count]);
    }

    /**
     * 取得最近建立的播放清單
     */
    public function getRecent(int $limit = 10)
    {
        return $this->orderBy('created_at', 'DESC')->limit($limit)->findAll();
    }
}
