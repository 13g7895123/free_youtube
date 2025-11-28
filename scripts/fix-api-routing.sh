#!/bin/bash
# ========================================
# 快速修復 API 路由問題
# ========================================
# 用途：修正 upstream 配置並重載 Gateway
# 使用：./scripts/fix-api-routing.sh

set -e

echo "========================================"
echo "修復 API 路由"
echo "========================================"
echo ""

# 1. 更新 upstream 配置指向正確的 green 容器
echo "【1】更新 nginx.upstream.conf..."
cat > nginx.upstream.conf << 'EOF'
# ========================================
# Upstream 配置檔 - 藍綠部署切換
# ========================================
# 自動生成於: $(date '+%Y-%m-%d %H:%M:%S')
# 目前活躍環境: green
#
# 使用變數方式，讓 Nginx 可以動態解析 DNS
# 即使容器不存在也不會導致啟動失敗

# 應用程式前端主機
map $host $app_frontend_host {
    default "free_youtube_frontend_green:80";
}

# 應用程式後端主機
map $host $app_backend_host {
    default "free_youtube_backend_green:8000";
}
EOF
echo "✅ nginx.upstream.conf 已更新"

# 2. 複製到 Gateway 容器
echo ""
echo "【2】複製配置到 Gateway 容器..."
docker cp nginx.upstream.conf free_youtube_gateway:/etc/nginx/conf.d/upstream.conf
echo "✅ 配置已複製"

# 3. 檢查 Nginx 配置語法
echo ""
echo "【3】檢查 Nginx 配置語法..."
docker exec free_youtube_gateway nginx -t
echo "✅ 配置語法正確"

# 4. 重載 Gateway
echo ""
echo "【4】重載 Gateway Nginx..."
docker exec free_youtube_gateway nginx -s reload
echo "✅ Gateway 已重載"

# 5. 清理舊的 app-green 容器
echo ""
echo "【5】清理舊的容器..."
docker rm -f app-green-frontend-1 2>/dev/null && echo "已移除 app-green-frontend-1" || echo "app-green-frontend-1 不存在或無法移除"
docker rm -f app-green-backend-1 2>/dev/null && echo "已移除 app-green-backend-1" || echo "app-green-backend-1 不存在或無法移除"

# 6. 建立部署狀態檔案
echo ""
echo "【6】建立部署狀態檔案..."
echo "green" > .deploy-state
echo "✅ .deploy-state 已建立"

# 7. 驗證
echo ""
echo "【7】驗證修復結果..."
echo ""
echo "Gateway upstream 配置:"
docker exec free_youtube_gateway cat /etc/nginx/conf.d/upstream.conf | grep -E "default|map"
echo ""
echo "測試 API 連線:"
docker exec free_youtube_gateway wget -q -O - --timeout=5 http://free_youtube_backend_green:8000/api/health 2>&1 || echo "❌ 連線失敗"

echo ""
echo "========================================"
echo "修復完成！"
echo "========================================"
echo ""
echo "請測試: curl -I https://free.youtube.mercylife.cc/api/health"
