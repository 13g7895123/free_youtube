#!/bin/bash
# ========================================
# 完整清理並重新部署
# ========================================
# 用途：清理所有混亂的容器，重新部署正確的架構
# 使用：./scripts/cleanup-and-redeploy.sh

set -e

echo "========================================"
echo "完整清理並重新部署"
echo "時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"
echo ""

# 確認操作
read -p "⚠️ 這將停止所有 free_youtube 相關容器並重新部署。確定繼續？(y/N) " confirm
if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "已取消"
    exit 0
fi

echo ""
echo "【1】停止所有相關的 Docker Compose 專案"
echo "----------------------------------------"

# 停止 app-green 專案 (舊的)
echo "停止 app-green 專案..."
docker compose -p app-green -f docker-compose.app.yml down 2>/dev/null || echo "app-green 專案不存在或已停止"

# 停止 app-blue 專案 (舊的)
echo "停止 app-blue 專案..."
docker compose -p app-blue -f docker-compose.app.yml down 2>/dev/null || echo "app-blue 專案不存在或已停止"

# 停止使用新配置的專案
echo "停止 free_youtube 專案的應用服務..."
docker compose --env-file .env.prod -f docker-compose.app-blue.yml down 2>/dev/null || echo "blue 配置不存在或已停止"
docker compose --env-file .env.prod -f docker-compose.app-green.yml down 2>/dev/null || echo "green 配置不存在或已停止"

echo "✅ 應用服務已停止"
echo ""

echo "【2】清理殘留的容器"
echo "----------------------------------------"
# 強制移除可能殘留的容器
for container in app-green-frontend-1 app-green-backend-1 app-blue-frontend-1 app-blue-backend-1 \
                 free_youtube_frontend_blue free_youtube_backend_blue \
                 free_youtube_frontend_green free_youtube_backend_green; do
    if docker ps -a --format '{{.Names}}' | grep -q "^${container}$"; then
        echo "移除容器: $container"
        docker rm -f "$container" 2>/dev/null || true
    fi
done
echo "✅ 殘留容器已清理"
echo ""

echo "【3】確認 Gateway 和 DB 仍在運行"
echo "----------------------------------------"
echo -n "free_youtube_gateway: "
docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway" && echo "✅ 運行中" || echo "❌ 未運行"
echo -n "free_youtube_db_prod: "
docker ps --format "{{.Names}}" | grep -q "free_youtube_db_prod" && echo "✅ 運行中" || echo "❌ 未運行"
echo ""

echo "【4】使用正確的配置啟動 Blue 環境"
echo "----------------------------------------"
echo "啟動 docker-compose.app-blue.yml..."
docker compose --env-file .env.prod -f docker-compose.app-blue.yml up -d --build

echo ""
echo "等待容器啟動..."
sleep 10

# 檢查容器狀態
echo ""
echo "容器狀態:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "(free_youtube_frontend_blue|free_youtube_backend_blue|NAME)"
echo ""

echo "【5】等待健康檢查通過"
echo "----------------------------------------"
MAX_WAIT=120
WAITED=0
while [ $WAITED -lt $MAX_WAIT ]; do
    BACKEND_HEALTH=$(docker inspect free_youtube_backend_blue --format '{{.State.Health.Status}}' 2>/dev/null || echo "not_found")
    FRONTEND_HEALTH=$(docker inspect free_youtube_frontend_blue --format '{{.State.Health.Status}}' 2>/dev/null || echo "not_found")
    
    echo "Backend: $BACKEND_HEALTH, Frontend: $FRONTEND_HEALTH (${WAITED}s/${MAX_WAIT}s)"
    
    if [ "$BACKEND_HEALTH" = "healthy" ] && [ "$FRONTEND_HEALTH" = "healthy" ]; then
        echo "✅ 所有容器健康檢查通過"
        break
    fi
    
    sleep 5
    WAITED=$((WAITED + 5))
done

if [ $WAITED -ge $MAX_WAIT ]; then
    echo "⚠️ 健康檢查超時，繼續執行..."
fi
echo ""

echo "【6】更新 upstream 配置指向 Blue 環境"
echo "----------------------------------------"
cat > nginx.upstream.conf << 'EOF'
# ========================================
# Upstream 配置檔 - 藍綠部署切換
# ========================================
# 目前活躍環境: blue

map $host $app_frontend_host {
    default "free_youtube_frontend_blue:80";
}

map $host $app_backend_host {
    default "free_youtube_backend_blue:8000";
}
EOF
echo "✅ nginx.upstream.conf 已更新"

# 複製到 Gateway
docker cp nginx.upstream.conf free_youtube_gateway:/etc/nginx/conf.d/upstream.conf
echo "✅ 配置已複製到 Gateway"
echo ""

echo "【7】重載 Gateway"
echo "----------------------------------------"
docker exec free_youtube_gateway nginx -t && \
docker exec free_youtube_gateway nginx -s reload && \
echo "✅ Gateway 已重載" || echo "❌ Gateway 重載失敗"
echo ""

echo "【8】建立部署狀態檔案"
echo "----------------------------------------"
echo "blue" > .deploy-state
echo "✅ .deploy-state 已設定為 blue"
echo ""

echo "【9】驗證"
echo "----------------------------------------"
echo "運行中的容器:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "(free_youtube|NAME)"
echo ""

echo "Gateway upstream 配置:"
docker exec free_youtube_gateway cat /etc/nginx/conf.d/upstream.conf | grep "default"
echo ""

echo "測試 Backend API (從 Gateway):"
docker exec free_youtube_gateway wget -q -O - --timeout=5 http://free_youtube_backend_blue:8000/api/health 2>&1 | head -5
echo ""

echo "測試從 Gateway 直接訪問 /api/health:"
docker exec free_youtube_gateway wget -q -O - --timeout=5 http://localhost/api/health 2>&1 | head -5
echo ""

echo "========================================"
echo "完成！"
echo "========================================"
echo ""
echo "請測試: curl https://free.youtube.mercylife.cc/api/health"
