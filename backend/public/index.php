<?php
// 暫時簡易 API 服務器用於測試
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 解析請求路徑
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

// 讀取請求 body
$input = json_decode(file_get_contents('php://input'), true);

// 簡單路由
if ($path === '/health') {
    echo json_encode(['status' => 'ok', 'message' => 'Backend is running']);
    exit;
}

// Videos API
if ($path === '/videos' && $method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'data' => [],
        'pagination' => [
            'total' => 0,
            'page' => 1,
            'per_page' => 20
        ]
    ]);
    exit;
}

if ($path === '/videos' && $method === 'POST') {
    // 創建影片
    $video = [
        'id' => rand(1, 1000),
        'video_id' => $input['video_id'] ?? '',
        'title' => $input['title'] ?? 'New Video',
        'description' => $input['description'] ?? '',
        'thumbnail_url' => $input['thumbnail_url'] ?? '',
        'duration' => $input['duration'] ?? 0,
        'channel_name' => $input['channel_name'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Video created successfully',
        'data' => $video
    ]);
    exit;
}

if ($path === '/videos/search' && $method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'data' => []
    ]);
    exit;
}

if ($path === '/videos/check' && $method === 'POST') {
    echo json_encode([
        'status' => 'success',
        'data' => ['exists' => false]
    ]);
    exit;
}

// Playlists API
if ($path === '/playlists' && $method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'data' => [],
        'pagination' => [
            'total' => 0,
            'page' => 1,
            'per_page' => 20
        ]
    ]);
    exit;
}

if ($path === '/playlists' && $method === 'POST') {
    // 創建播放清單
    $playlist = [
        'id' => rand(1, 1000),
        'name' => $input['name'] ?? 'New Playlist',
        'description' => $input['description'] ?? '',
        'is_active' => $input['is_active'] ?? true,
        'item_count' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Playlist created successfully',
        'data' => $playlist
    ]);
    exit;
}

// Playlist detail
if (preg_match('#^/playlists/(\d+)$#', $path, $matches) && $method === 'GET') {
    $id = $matches[1];
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $id,
            'name' => 'Sample Playlist',
            'description' => 'Sample description',
            'is_active' => true,
            'item_count' => 0,
            'items' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

if (preg_match('#^/playlists/(\d+)$#', $path, $matches) && $method === 'PUT') {
    $id = $matches[1];
    $playlist = [
        'id' => $id,
        'name' => $input['name'] ?? 'Updated Playlist',
        'description' => $input['description'] ?? '',
        'is_active' => $input['is_active'] ?? true,
        'item_count' => 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Playlist updated successfully',
        'data' => $playlist
    ]);
    exit;
}

if (preg_match('#^/playlists/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $id = $matches[1];
    echo json_encode([
        'status' => 'success',
        'message' => 'Playlist deleted successfully'
    ]);
    exit;
}

// Playlist items
if (preg_match('#^/playlists/(\d+)/items$#', $path, $matches) && $method === 'GET') {
    $id = $matches[1];
    echo json_encode([
        'status' => 'success',
        'data' => []
    ]);
    exit;
}

if (preg_match('#^/playlists/(\d+)/items$#', $path, $matches) && $method === 'POST') {
    $id = $matches[1];
    echo json_encode([
        'status' => 'success',
        'message' => 'Item added to playlist successfully',
        'data' => [
            'id' => rand(1, 1000),
            'playlist_id' => $id,
            'video_id' => $input['video_id'] ?? 0,
            'position' => 1
        ]
    ]);
    exit;
}

if (preg_match('#^/playlists/(\d+)/items/reorder$#', $path, $matches) && $method === 'POST') {
    $id = $matches[1];
    echo json_encode([
        'status' => 'success',
        'message' => 'Items reordered successfully'
    ]);
    exit;
}

if (preg_match('#^/playlists/(\d+)/items/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from playlist successfully'
    ]);
    exit;
}

// 404
http_response_code(404);
echo json_encode(['status' => 'error', 'message' => 'Not found: ' . $method . ' ' . $path]);
