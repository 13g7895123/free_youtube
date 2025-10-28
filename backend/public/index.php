<?php
/**
 * Simple API Backend with Database Persistence
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('database.default.hostname') ?: 'mariadb';
        $dbname = getenv('database.default.database') ?: 'free_youtube';
        $user = getenv('database.default.username') ?: 'root';
        $pass = getenv('database.default.password') ?: 'secret';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
            exit;
        }
    }
    return $pdo;
}

// Parse request
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Health check
if ($path === '/health') {
    echo json_encode(['status' => 'ok', 'message' => 'Backend is running']);
    exit;
}

// Videos API
if ($path === '/videos' && $method === 'GET') {
    $db = getDB();
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 20);
    $offset = ($page - 1) * $perPage;

    $total = $db->query("SELECT COUNT(*) FROM videos WHERE deleted_at IS NULL")->fetchColumn();
    $stmt = $db->prepare("SELECT * FROM videos WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $videos = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $videos,
        'pagination' => ['total' => (int)$total, 'page' => $page, 'per_page' => $perPage]
    ]);
    exit;
}

if ($path === '/videos/search' && $method === 'GET') {
    $db = getDB();
    $query = $_GET['q'] ?? '';

    if (strlen($query) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Search query too short']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM videos WHERE deleted_at IS NULL AND (title LIKE ? OR video_id = ?) LIMIT 50");
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $query]);
    $videos = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $videos]);
    exit;
}

if ($path === '/videos/check' && $method === 'POST') {
    $db = getDB();
    $videoId = $input['video_id'] ?? '';

    if (!$videoId) {
        echo json_encode(['status' => 'error', 'message' => 'Missing video_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE video_id = ? AND deleted_at IS NULL");
    $stmt->execute([$videoId]);
    $exists = (bool)$stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'data' => ['exists' => $exists]]);
    exit;
}

if ($path === '/videos' && $method === 'POST') {
    $db = getDB();

    // Check if video already exists
    $checkStmt = $db->prepare("SELECT id FROM videos WHERE video_id = ? AND deleted_at IS NULL");
    $checkStmt->execute([$input['video_id']]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Video already exists', 'code' => 409]);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO videos (video_id, title, description, thumbnail_url, duration, youtube_url, channel_id, channel_name, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $input['video_id'], $input['title'], $input['description'] ?? null,
        $input['thumbnail_url'] ?? null, $input['duration'] ?? null, $input['youtube_url'],
        $input['channel_id'] ?? null, $input['channel_name'] ?? null, $input['published_at'] ?? null
    ]);

    $video = $db->query("SELECT * FROM videos WHERE id = LAST_INSERT_ID()")->fetch();
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Video created successfully', 'data' => $video]);
    exit;
}

// Playlists API
if ($path === '/playlists' && $method === 'GET') {
    $db = getDB();
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 20);
    $offset = ($page - 1) * $perPage;

    $total = $db->query("SELECT COUNT(*) FROM playlists WHERE deleted_at IS NULL")->fetchColumn();
    $stmt = $db->prepare("SELECT * FROM playlists WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $playlists = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $playlists,
        'pagination' => ['total' => (int)$total, 'page' => $page, 'per_page' => $perPage]
    ]);
    exit;
}

if ($path === '/playlists' && $method === 'POST') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO playlists (name, description, is_active, item_count, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())");
    $stmt->execute([$input['name'], $input['description'] ?? null, $input['is_active'] ?? 1]);

    $playlist = $db->query("SELECT * FROM playlists WHERE id = LAST_INSERT_ID()")->fetch();
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Playlist created successfully', 'data' => $playlist]);
    exit;
}

// Playlist detail
if (preg_match('#^/playlists/(\d+)$#', $path, $matches) && $method === 'GET') {
    $db = getDB();
    $id = $matches[1];
    $playlist = $db->prepare("SELECT * FROM playlists WHERE id = ? AND deleted_at IS NULL");
    $playlist->execute([$id]);
    $playlist = $playlist->fetch();

    if (!$playlist) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Playlist not found']);
        exit;
    }

    $items = $db->prepare("SELECT pi.*, v.* FROM playlist_items pi JOIN videos v ON pi.video_id = v.id WHERE pi.playlist_id = ? ORDER BY pi.position");
    $items->execute([$id]);
    $playlist['items'] = $items->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $playlist]);
    exit;
}

if (preg_match('#^/playlists/(\d+)$#', $path, $matches) && $method === 'PUT') {
    $db = getDB();
    $id = $matches[1];
    $stmt = $db->prepare("UPDATE playlists SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$input['name'], $input['description'] ?? null, $input['is_active'] ?? 1, $id]);

    $playlist = $db->prepare("SELECT * FROM playlists WHERE id = ?");
    $playlist->execute([$id]);
    $playlist = $playlist->fetch();

    echo json_encode(['status' => 'success', 'message' => 'Playlist updated successfully', 'data' => $playlist]);
    exit;
}

if (preg_match('#^/playlists/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $db = getDB();
    $id = $matches[1];
    $stmt = $db->prepare("UPDATE playlists SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'Playlist deleted successfully']);
    exit;
}

// Playlist items
if (preg_match('#^/playlists/(\d+)/items$#', $path, $matches) && $method === 'GET') {
    $db = getDB();
    $id = $matches[1];
    $stmt = $db->prepare("SELECT pi.*, v.* FROM playlist_items pi JOIN videos v ON pi.video_id = v.id WHERE pi.playlist_id = ? ORDER BY pi.position");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $items]);
    exit;
}

if (preg_match('#^/playlists/(\d+)/items$#', $path, $matches) && $method === 'POST') {
    $db = getDB();
    $playlistId = $matches[1];

    $maxPos = $db->prepare("SELECT MAX(position) FROM playlist_items WHERE playlist_id = ?");
    $maxPos->execute([$playlistId]);
    $position = ((int)$maxPos->fetchColumn()) + 1;

    $stmt = $db->prepare("INSERT INTO playlist_items (playlist_id, video_id, position, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->execute([$playlistId, $input['video_id'], $position]);

    $db->prepare("UPDATE playlists SET item_count = item_count + 1, updated_at = NOW() WHERE id = ?")->execute([$playlistId]);

    $items = $db->prepare("SELECT pi.*, v.* FROM playlist_items pi JOIN videos v ON pi.video_id = v.id WHERE pi.playlist_id = ? ORDER BY pi.position");
    $items->execute([$playlistId]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Item added successfully', 'data' => $items->fetchAll()]);
    exit;
}

if (preg_match('#^/playlists/(\d+)/items/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $db = getDB();
    $playlistId = $matches[1];
    $videoId = $matches[2];

    $stmt = $db->prepare("DELETE FROM playlist_items WHERE playlist_id = ? AND video_id = ?");
    $stmt->execute([$playlistId, $videoId]);

    $db->prepare("UPDATE playlists SET item_count = item_count - 1, updated_at = NOW() WHERE id = ?")->execute([$playlistId]);

    echo json_encode(['status' => 'success', 'message' => 'Item removed successfully']);
    exit;
}

// 404
http_response_code(404);
echo json_encode(['status' => 'error', 'message' => 'Not found: ' . $method . ' ' . $path]);
