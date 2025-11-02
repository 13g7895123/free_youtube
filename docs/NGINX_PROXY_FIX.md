# Nginx Proxy Pass 配置修正

## 問題描述

當訪問 `/api/auth/line/logs` 和 `/api/auth/line/errors` 時，返回的是 "播放清單管理系統 API" 訊息，而不是預期的 LINE 登入 debug API。

## 根本原因

**原始 nginx 配置：**
```nginx
location /api/ {
    proxy_pass http://$backend_upstream/;
}
```

這個配置會**移除** `/api/` 前綴：
- 請求：`/api/auth/line/logs`
- 實際傳給後端：`/auth/line/logs`

但 CodeIgniter 的路由是在 `api` group 裡面：
```php
$routes->group('api', static function ($routes) {
    $routes->group('auth', static function ($routes) {
        $routes->get('line/logs', 'Auth::getLineLoginLogs');
    });
});
```

所以 CodeIgniter 期望的路徑是：`/api/auth/line/logs`

當路徑不匹配時，CodeIgniter 使用預設的 fallback 路由 `$routes->get('(:any)', 'Home::index');`，返回 "播放清單管理系統 API" 訊息。

## 解決方案

### 修正 nginx 配置

**修改前：**
```nginx
location /api/ {
    proxy_pass http://$backend_upstream/;  # 末尾有 /，會移除 /api/
}
```

**修改後：**
```nginx
location /api/ {
    proxy_pass http://$backend_upstream;   # 末尾沒有 /，保留 /api/
}
```

### Nginx proxy_pass 的行為差異

| 配置 | 請求 | 傳給後端 |
|------|------|----------|
| `proxy_pass http://backend/;` | `/api/auth/line/logs` | `/auth/line/logs` |
| `proxy_pass http://backend;` | `/api/auth/line/logs` | `/api/auth/line/logs` |
| `proxy_pass http://backend/v1/;` | `/api/auth/line/logs` | `/v1/auth/line/logs` |

**規則：**
- 如果 `proxy_pass` URL **有** URI 部分（即使只是 `/`），nginx 會**替換**匹配的 location 部分
- 如果 `proxy_pass` URL **沒有** URI 部分，nginx 會**保留**完整的原始 URI

## 影響的文件

1. **nginx.prod.conf** - 生產環境配置
2. **deploy-line-debug.sh** - 部署腳本（現在會重建 frontend 以應用新的 nginx 配置）

## 部署步驟

1. **確認修正已應用**
   ```bash
   cat nginx.prod.conf | grep -A 2 "location /api/"
   ```
   
   應該看到：
   ```nginx
   location /api/ {
       ...
       proxy_pass http://$backend_upstream;
   }
   ```

2. **重新部署**
   ```bash
   ./deploy-line-debug.sh
   ```
   
   或手動：
   ```bash
   docker-compose -f docker-compose.prod.yml up -d --build frontend backend
   ```

3. **驗證修正**
   ```bash
   # 測試 API 是否正常工作
   curl -X GET "https://your-domain.com/api/auth/line/logs?limit=1" \
     -H "X-Debug-Key: your-debug-key"
   ```
   
   應該回傳：
   ```json
   {
     "success": true,
     "data": [...],
     "count": ...
   }
   ```

## 其他受影響的 API

這個修正會影響**所有** `/api/` 開頭的請求，確保它們都能正確路由到 CodeIgniter：

- `/api/health`
- `/api/auth/*`
- `/api/videos/*`
- `/api/playlists/*`
- `/api/video-library/*`

建議在修正後測試所有主要 API 端點。

## 測試清單

- [ ] `/api/health` - 健康檢查
- [ ] `/api/auth/line/logs` - LINE 登入 logs
- [ ] `/api/auth/line/errors` - LINE 登入錯誤
- [ ] `/api/auth/user` - 取得當前用戶（需認證）
- [ ] `/api/videos` - 影片列表
- [ ] `/api/playlists` - 播放清單列表（需認證）

## 預防措施

在未來配置 nginx proxy 時：
1. 明確了解是否需要保留或移除 URI 前綴
2. 測試實際傳給後端的 URL
3. 使用 nginx debug log 來檢查 proxy_pass 行為：
   ```nginx
   error_log /var/log/nginx/error.log debug;
   ```
