<?php
/**
 * PHP Built-in Server Router
 * 
 * 此檔案讓 PHP 內建伺服器正確處理 CodeIgniter 4 的路由
 * 
 * 使用方式:
 *   php -S 0.0.0.0:8000 router.php
 */

// 取得請求的 URI
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// 如果是實際存在的檔案，直接提供服務
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    // 檢查是否為 PHP 檔案
    if (pathinfo($uri, PATHINFO_EXTENSION) === 'php') {
        // 不允許直接訪問 PHP 檔案（除了 index.php）
        if (basename($uri) !== 'index.php') {
            http_response_code(403);
            echo 'Forbidden';
            return true;
        }
    }
    // 讓 PHP 內建伺服器處理靜態檔案
    return false;
}

// 所有其他請求都導向 index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/index.php';
