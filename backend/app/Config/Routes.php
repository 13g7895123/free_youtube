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

        // 需要認證的路由
        $routes->group('', ['filter' => 'auth'], static function ($routes) {
            $routes->get('user', 'Auth::user');                    // 取得當前用戶
            $routes->post('logout', 'Auth::logout');               // 登出
            $routes->post('refresh', 'Auth::refresh');             // 刷新 token
            $routes->post('migrate-guest-data', 'Auth::migrateGuestData'); // 遷移訪客資料
        });
    });

    // LINE 登入 Log 路由（公開，不需認證）
    $routes->group('auth/line/logs', static function ($routes) {
        $routes->get('errors', 'Auth::lineLoginErrors');                    // 查詢錯誤日誌
        $routes->get('session/(:segment)', 'Auth::lineLoginSession/$1');    // 查詢特定 session 的日誌
        $routes->get('user/(:segment)', 'Auth::lineLoginUserHistory/$1');   // 查詢特定用戶的登入歷史
    });

    // LINE Login Debug API（完整診斷工具）
    $routes->group('debug/line-login', static function ($routes) {
        $routes->get('status', 'LineLoginDebug::status');                   // 系統狀態總覽
        $routes->get('recent', 'LineLoginDebug::recent');                   // 最近的日誌
        $routes->get('errors', 'LineLoginDebug::errors');                   // 錯誤日誌（詳細）
        $routes->get('session/(:segment)', 'LineLoginDebug::session/$1');   // Session 完整流程
        $routes->get('sessions', 'LineLoginDebug::sessions');               // 所有 Sessions
        $routes->get('diagnostic', 'LineLoginDebug::diagnostic');           // 系統診斷資訊
        $routes->get('error-summary', 'LineLoginDebug::errorSummary');      // 錯誤摘要統計
        $routes->get('test-connection', 'LineLoginDebug::testConnection');  // 測試連接配置
        $routes->get('test-jwt', 'LineLoginDebug::testJwtGeneration');      // 測試 JWT 生成功能
        $routes->get('test-database', 'LineLoginDebug::testDatabaseConnection'); // 測試資料庫連接
        $routes->get('diagnose-token', 'LineLoginDebug::diagnoseTokenGeneration'); // 診斷 Token 生成流程
        $routes->get('diagnose-token/(:num)', 'LineLoginDebug::diagnoseTokenGeneration/$1'); // 診斷特定用戶的 Token 生成
    });

    // 測試路由（僅開發環境）
    $routes->group('test', static function ($routes) {
        $routes->post('user-creation', 'Auth::testUserCreation');  // 測試使用者建立功能
    });

    // 影片庫路由（需認證）
    $routes->group('video-library', ['filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'VideoLibrary::index');                   // 取得影片庫
        $routes->post('/', 'VideoLibrary::add');                    // 新增影片
        $routes->delete('(:segment)', 'VideoLibrary::remove/$1');   // 移除影片
    });

    // 通知路由（公開，不需認證）
    $routes->group('notifications', static function ($routes) {
        $routes->post('/', 'Notification::create');                         // 建立通知
        $routes->patch('(:num)/status', 'Notification::updateStatus/$1');   // 更新狀態
        $routes->get('/', 'Notification::index');                           // 列表（選用）
        $routes->get('(:num)', 'Notification::show/$1');                    // 詳情（選用）
    });

    // 影片路由（需認證）
    $routes->group('videos', ['filter' => 'auth'], static function ($routes) {
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

        // 播放清單項目路由（繼承 auth filter）
        $routes->post('(:num)/items', 'Playlists::addItem/$1');                      // 新增項目
        $routes->delete('(:num)/items/(:num)', 'Playlists::removeItem/$1/$2');      // 移除項目
        $routes->put('(:num)/reorder', 'Playlists::reorder/$1');                    // 重新排序
    });
});

// 前端 SPA 路由 - 所有未匹配的路由重導到 index.html
$routes->get('/', 'Home::index');
$routes->get('(:any)', 'Home::index');
