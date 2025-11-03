#!/bin/bash

# Cookie 問題排查腳本
# 用於診斷為什麼 Set-Cookie header 沒有出現在 Response 中

set -e

echo "======================================"
echo "🔍 Cookie 問題排查開始"
echo "======================================"
echo ""

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 步驟 1: 檢查當前容器狀態
echo -e "${BLUE}步驟 1: 檢查容器狀態${NC}"
echo "======================================"
docker ps --filter "name=free_youtube" --format "table {{.Names}}\t{{.Status}}\t{{.CreatedAt}}"
echo ""

# 步驟 2: 重新構建並啟動後端容器
echo -e "${BLUE}步驟 2: 重新部署後端（載入最新代碼）${NC}"
echo "======================================"
read -p "是否要重新部署後端容器？這將重新構建映像並重啟容器。(y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "正在停止容器..."
    docker-compose -f docker-compose.prod.yml stop backend

    echo "正在重新構建後端..."
    docker-compose -f docker-compose.prod.yml build --no-cache backend

    echo "正在啟動後端..."
    docker-compose -f docker-compose.prod.yml up -d backend

    echo "等待後端啟動..."
    sleep 5

    echo -e "${GREEN}✅ 後端已重新部署${NC}"
else
    echo -e "${YELLOW}⚠️  跳過重新部署${NC}"
fi
echo ""

# 步驟 3: 檢查環境變數
echo -e "${BLUE}步驟 3: 檢查後端環境變數${NC}"
echo "======================================"
echo "CI_ENVIRONMENT:"
docker exec free_youtube_backend_prod printenv CI_ENVIRONMENT || echo "未設置"
echo ""
echo "FRONTEND_URL:"
docker exec free_youtube_backend_prod printenv FRONTEND_URL || echo "未設置"
echo ""
echo "app.baseURL:"
docker exec free_youtube_backend_prod printenv app.baseURL || echo "未設置"
echo ""
echo "COOKIE_DOMAIN:"
docker exec free_youtube_backend_prod printenv COOKIE_DOMAIN || echo "未設置（這是正常的）"
echo ""

# 步驟 4: 檢查 CorsFilter 文件
echo -e "${BLUE}步驟 4: 檢查 CorsFilter 是否有調試日誌${NC}"
echo "======================================"
if docker exec free_youtube_backend_prod grep -q "CorsFilter after()" /var/www/html/app/Filters/CorsFilter.php; then
    echo -e "${GREEN}✅ CorsFilter 包含調試日誌${NC}"
else
    echo -e "${RED}❌ CorsFilter 沒有調試日誌（代碼未更新）${NC}"
fi
echo ""

# 步驟 5: 清除日誌並準備監控
echo -e "${BLUE}步驟 5: 準備監控日誌${NC}"
echo "======================================"
echo "正在清空舊日誌..."
docker exec free_youtube_backend_prod sh -c "echo '' > /var/www/html/writable/logs/log-$(date +%Y-%m-%d).log" || true
echo -e "${GREEN}✅ 日誌已清空${NC}"
echo ""

# 步驟 6: 測試健康檢查
echo -e "${BLUE}步驟 6: 測試後端健康狀態${NC}"
echo "======================================"
HEALTH_RESPONSE=$(docker exec free_youtube_backend_prod curl -s http://localhost:8000/api/health || echo "FAILED")
if [[ $HEALTH_RESPONSE == *"ok"* ]] || [[ $HEALTH_RESPONSE == *"success"* ]]; then
    echo -e "${GREEN}✅ 後端健康檢查通過${NC}"
else
    echo -e "${RED}❌ 後端健康檢查失敗: $HEALTH_RESPONSE${NC}"
fi
echo ""

# 步驟 7: 提供測試指引
echo -e "${BLUE}步驟 7: 測試步驟${NC}"
echo "======================================"
echo "現在請執行以下操作："
echo ""
echo "1. 在瀏覽器中清除所有 Cookies (DevTools -> Application -> Cookies -> Clear)"
echo "2. 重新登入（LINE Login）"
echo "3. 在 DevTools -> Network 中檢查登入回調請求的 Response Headers"
echo ""
echo "同時，在另一個終端執行以下命令來監控日誌："
echo ""
echo -e "${YELLOW}docker logs -f free_youtube_backend_prod${NC}"
echo ""
echo "或查看詳細日誌文件："
echo ""
echo -e "${YELLOW}docker exec free_youtube_backend_prod tail -f /var/www/html/writable/logs/log-$(date +%Y-%m-%d).log${NC}"
echo ""

# 步驟 8: 提供 curl 測試命令
echo -e "${BLUE}步驟 8: 直接測試後端（繞過 Nginx）${NC}"
echo "======================================"
echo "您可以使用以下命令直接測試後端的 Set-Cookie："
echo ""
echo -e "${YELLOW}docker exec free_youtube_backend_prod curl -v http://localhost:8000/api/health 2>&1 | grep -i 'set-cookie'${NC}"
echo ""
echo "如果這個命令沒有輸出，表示後端沒有設置 cookie（正常，因為 health endpoint 不設置 cookie）"
echo ""

# 步驟 9: 檢查 Nginx 配置
echo -e "${BLUE}步驟 9: 檢查 Nginx 是否正確配置${NC}"
echo "======================================"
echo "檢查 nginx.prod.conf 中的關鍵配置："
echo ""
if grep -q "proxy_pass_header Set-Cookie" nginx.prod.conf; then
    echo -e "${GREEN}✅ proxy_pass_header Set-Cookie 存在${NC}"
else
    echo -e "${RED}❌ proxy_pass_header Set-Cookie 缺失${NC}"
fi
echo ""
if grep -q "X-Forwarded-Host" nginx.prod.conf; then
    echo -e "${GREEN}✅ X-Forwarded-Host 存在${NC}"
else
    echo -e "${RED}❌ X-Forwarded-Host 缺失${NC}"
fi
echo ""

# 步驟 10: 總結
echo "======================================"
echo -e "${GREEN}✅ 排查準備完成${NC}"
echo "======================================"
echo ""
echo "下一步："
echo "1. 執行登入流程"
echo "2. 在瀏覽器 DevTools 查看 Network -> Response Headers"
echo "3. 同時查看後端日誌，尋找包含以下標記的日誌："
echo "   - 🍪 Setting access_token cookie"
echo "   - 🍪 Setting refresh_token cookie"
echo "   - 🔍 CorsFilter after()"
echo "   - 📤 CorsFilter after() - All response headers"
echo ""
echo "如果看到這些日誌，請複製完整內容以便進一步分析。"
echo ""
