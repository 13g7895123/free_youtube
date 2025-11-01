<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// API 路由前綴
$routes->group('api', static function ($routes) {
    // 健康檢查
    $routes->get('health', 'Home::health');

    // 認證路由
    $routes->group('auth', static function ($routes) {
        // LINE Login（公開）
        $routes->get('line/login', 'Auth::lineLogin');
        $routes->get('line/callback', 'Auth::lineCallback');

        // Mock Login（公開，僅開發環境）
        $routes->post('mock/login', 'Auth::mockLogin');

        // Debug API（需要 X-Debug-Key header）
        $routes->get('line/logs', 'Auth::getLineLoginLogs');       // 查詢 LINE 登入 logs
        $routes->get('line/errors', 'Auth::getLineLoginErrors');   // 查詢 LINE 登入錯誤

        // 需要認證的路由
        $routes->group('', ['filter' => 'auth'], static function ($routes) {
            $routes->get('user', 'Auth::user');                    // 取得當前用戶
            $routes->post('logout', 'Auth::logout');               // 登出
            $routes->post('refresh', 'Auth::refresh');             // 刷新 token
            $routes->post('migrate-guest-data', 'Auth::migrateGuestData'); // 遷移訪客資料
        });
    });

    // 影片庫路由（需認證）
    $routes->group('video-library', ['filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'VideoLibrary::index');                   // 取得影片庫
        $routes->post('/', 'VideoLibrary::add');                    // 新增影片
        $routes->delete('(:segment)', 'VideoLibrary::remove/$1');   // 移除影片
    });

    // 影片路由
    $routes->group('videos', static function ($routes) {
        $routes->get('/', 'Api\VideoController::index');           // 列表
        $routes->get('search', 'Api\VideoController::search');     // 搜尋
        $routes->post('/', 'Api\VideoController::create');         // 建立
        $routes->get('(:num)', 'Api\VideoController::show/$1');    // 詳情
        $routes->put('(:num)', 'Api\VideoController::update/$1');  // 更新
        $routes->delete('(:num)', 'Api\VideoController::delete/$1'); // 刪除
        $routes->post('check', 'Api\VideoController::check');      // 檢查存在
    });

    // 播放清單路由（需認證）
    $routes->group('playlists', ['filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'Playlists::index');                     // 列表
        $routes->post('/', 'Playlists::create');                   // 建立
        $routes->get('(:num)', 'Playlists::show/$1');              // 詳情
        $routes->put('(:num)', 'Playlists::update/$1');            // 更新
        $routes->delete('(:num)', 'Playlists::delete/$1');         // 刪除

        // 播放清單項目路由
        $routes->post('(:num)/items', 'Playlists::addItem/$1');                      // 新增項目
        $routes->delete('(:num)/items/(:num)', 'Playlists::removeItem/$1/$2');      // 移除項目
        $routes->put('(:num)/reorder', 'Playlists::reorder/$1');                    // 重新排序
    });
});

// 前端 SPA 路由 - 所有未匹配的路由重導到 index.html
$routes->get('/', 'Home::index');
$routes->get('(:any)', 'Home::index');
