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
        // 使用新的查詢建構器實例，避免查詢狀態衝突
        return $this->builder()
                    ->where('user_id', $userId)
                    ->where('deleted_at', null)
                    ->orderBy('created_at', 'DESC')
                    ->get()
                    ->getResult($this->returnType);
    }

    /**
     * 取得所有活躍的播放清單
     */
    public function getActive()
    {
        // 使用新的查詢建構器實例，避免查詢狀態衝突
        return $this->builder()
                    ->where('is_active', true)
                    ->where('deleted_at', null)
                    ->orderBy('created_at', 'DESC')
                    ->get()
                    ->getResult($this->returnType);
    }

    /**
     * 搜尋播放清單
     */
    public function search(string $query)
    {
        // 使用新的查詢建構器實例，避免查詢狀態衝突
        return $this->builder()
                    ->where('deleted_at', null)
                    ->groupStart()
                        ->like('name', $query)
                        ->orLike('description', $query)
                    ->groupEnd()
                    ->get()
                    ->getResult($this->returnType);
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
            // 確保 item_count 是最新的實際數量
            $playlist->item_count = count($playlist->items);
        }

        return $playlist;
    }

    /**
     * 更新播放清單項目計數
     */
    public function updateItemCount(int $playlistId)
    {
        $itemModel = new PlaylistItemModel();
        // 使用 countAllResults(false) 避免重置查詢建構器
        $count = $itemModel->where('playlist_id', $playlistId)->countAllResults(false);

        return $this->update($playlistId, ['item_count' => $count]);
    }

    /**
     * 取得最近建立的播放清單
     */
    public function getRecent(int $limit = 10)
    {
        // 使用新的查詢建構器實例，避免查詢狀態衝突
        return $this->builder()
                    ->where('deleted_at', null)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->get()
                    ->getResult($this->returnType);
    }
}
