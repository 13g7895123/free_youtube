#!/bin/bash
# ========================================
# API 路由診斷腳本
# ========================================
# 用途：診斷 /api 請求被重導向的問題
# 使用：./scripts/diagnose-api-routing.sh

echo "========================================"
echo "API 路由診斷報告"
echo "時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"
echo ""

# 1. 檢查運行中的容器
echo "【1】運行中的容器 (free_youtube 相關)"
echo "----------------------------------------"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(free_youtube|gateway|NAME)" || echo "找不到 free_youtube 相關容器"
echo ""

# 2. 檢查容器名稱是否正確
echo "【2】檢查 Blue/Green 容器"
echo "----------------------------------------"
echo -n "free_youtube_frontend_blue: "
docker ps --format "{{.Names}}" | grep -q "free_youtube_frontend_blue" && echo "✅ 運行中" || echo "❌ 未運行"
echo -n "free_youtube_backend_blue:  "
docker ps --format "{{.Names}}" | grep -q "free_youtube_backend_blue" && echo "✅ 運行中" || echo "❌ 未運行"
echo -n "free_youtube_frontend_green: "
docker ps --format "{{.Names}}" | grep -q "free_youtube_frontend_green" && echo "✅ 運行中" || echo "❌ 未運行"
echo -n "free_youtube_backend_green:  "
docker ps --format "{{.Names}}" | grep -q "free_youtube_backend_green" && echo "✅ 運行中" || echo "❌ 未運行"
echo -n "free_youtube_gateway:       "
docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway" && echo "✅ 運行中" || echo "❌ 未運行"
echo -n "free_youtube_db_prod:       "
docker ps --format "{{.Names}}" | grep -q "free_youtube_db_prod" && echo "✅ 運行中" || echo "❌ 未運行"
echo ""

# 3. 檢查網路
echo "【3】Docker 網路狀態"
echo "----------------------------------------"
echo "網路 free_youtube_app_network_prod 中的容器:"
docker network inspect free_youtube_app_network_prod --format '{{range .Containers}}  - {{.Name}}{{"\n"}}{{end}}' 2>/dev/null || echo "❌ 網路不存在"
echo ""

# 4. 檢查 Gateway 的 upstream 配置
echo "【4】Gateway upstream 配置"
echo "----------------------------------------"
if docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway"; then
    docker exec free_youtube_gateway cat /etc/nginx/conf.d/upstream.conf 2>/dev/null || echo "❌ 無法讀取 upstream.conf"
else
    echo "❌ Gateway 容器未運行"
fi
echo ""

# 5. 從 Gateway 測試 DNS 解析
echo "【5】Gateway DNS 解析測試"
echo "----------------------------------------"
if docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway"; then
    echo "解析 free_youtube_backend_blue:"
    docker exec free_youtube_gateway nslookup free_youtube_backend_blue 127.0.0.11 2>/dev/null | head -10 || echo "  ❌ 解析失敗"
    echo ""
    echo "解析 free_youtube_frontend_blue:"
    docker exec free_youtube_gateway nslookup free_youtube_frontend_blue 127.0.0.11 2>/dev/null | head -10 || echo "  ❌ 解析失敗"
else
    echo "❌ Gateway 容器未運行"
fi
echo ""

# 6. 從 Gateway 測試後端連線
echo "【6】Gateway 到 Backend 連線測試"
echo "----------------------------------------"
if docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway"; then
    echo "測試 http://free_youtube_backend_blue:8000/api/health:"
    docker exec free_youtube_gateway wget -q -O - --timeout=5 http://free_youtube_backend_blue:8000/api/health 2>&1 || echo "  ❌ 連線失敗"
    echo ""
else
    echo "❌ Gateway 容器未運行"
fi
echo ""

# 7. 檢查 Gateway Nginx 配置語法
echo "【7】Gateway Nginx 配置檢查"
echo "----------------------------------------"
if docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway"; then
    docker exec free_youtube_gateway nginx -t 2>&1
else
    echo "❌ Gateway 容器未運行"
fi
echo ""

# 8. 檢查部署狀態檔案
echo "【8】部署狀態"
echo "----------------------------------------"
if [ -f ".deploy-state" ]; then
    echo "活躍環境: $(cat .deploy-state)"
else
    echo "❌ 找不到 .deploy-state 檔案"
fi
echo ""

# 9. 從外部測試 API
echo "【9】外部 API 測試 (curl)"
echo "----------------------------------------"
GATEWAY_PORT="${GATEWAY_PORT:-8090}"
echo "測試本機 Gateway (port $GATEWAY_PORT):"
echo ""
echo "GET /api/health 回應:"
curl -sI "http://localhost:$GATEWAY_PORT/api/health" 2>/dev/null | head -15 || echo "  ❌ 無法連線到 Gateway"
echo ""

# 10. 檢查 Nginx 錯誤日誌
echo "【10】Gateway 最近錯誤日誌 (最後 20 行)"
echo "----------------------------------------"
if docker ps --format "{{.Names}}" | grep -q "free_youtube_gateway"; then
    docker exec free_youtube_gateway tail -20 /var/log/nginx/error.log 2>/dev/null || echo "無錯誤日誌"
else
    echo "❌ Gateway 容器未運行"
fi
echo ""

# 11. 檢查本地檔案
echo "【11】本地配置檔案檢查"
echo "----------------------------------------"
echo -n "nginx.upstream.conf: "
[ -f "nginx.upstream.conf" ] && echo "✅ 存在" || echo "❌ 不存在"
echo -n "nginx.gateway.conf:  "
[ -f "nginx.gateway.conf" ] && echo "✅ 存在" || echo "❌ 不存在"
echo -n "docker-compose.app-blue.yml:  "
[ -f "docker-compose.app-blue.yml" ] && echo "✅ 存在" || echo "❌ 不存在"
echo -n "docker-compose.app-green.yml: "
[ -f "docker-compose.app-green.yml" ] && echo "✅ 存在" || echo "❌ 不存在"
echo -n "docker-compose.gateway.yml:   "
[ -f "docker-compose.gateway.yml" ] && echo "✅ 存在" || echo "❌ 不存在"
echo -n "docker-compose.db.yml:        "
[ -f "docker-compose.db.yml" ] && echo "✅ 存在" || echo "❌ 不存在"
echo ""

# 12. 顯示本地 upstream 配置內容
echo "【12】本地 nginx.upstream.conf 內容"
echo "----------------------------------------"
if [ -f "nginx.upstream.conf" ]; then
    cat nginx.upstream.conf
else
    echo "❌ 檔案不存在"
fi
echo ""

echo "========================================"
echo "診斷完成"
echo "========================================"
