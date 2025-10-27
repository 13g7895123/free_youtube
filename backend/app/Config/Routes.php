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

    // 播放清單路由
    $routes->group('playlists', static function ($routes) {
        $routes->get('/', 'Api\PlaylistController::index');        // 列表
        $routes->post('/', 'Api\PlaylistController::create');      // 建立
        $routes->get('(:num)', 'Api\PlaylistController::show/$1'); // 詳情
        $routes->put('(:num)', 'Api\PlaylistController::update/$1'); // 更新
        $routes->delete('(:num)', 'Api\PlaylistController::delete/$1'); // 刪除

        // 播放清單項目路由
        $routes->get('(:num)/items', 'Api\PlaylistItemController::getItems/$1');
        $routes->post('(:num)/items', 'Api\PlaylistItemController::addItem/$1');
        $routes->post('(:num)/items/reorder', 'Api\PlaylistItemController::reorder/$1');
        $routes->delete('(:num)/items/(:num)', 'Api\PlaylistItemController::removeItem/$1/$2');
    });
});

// 前端 SPA 路由 - 所有未匹配的路由重導到 index.html
$routes->get('/', 'Home::index');
$routes->get('(:any)', 'Home::index');
