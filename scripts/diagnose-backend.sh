#!/bin/bash
# ========================================
# Backend 容器深度診斷
# ========================================

echo "========================================"
echo "Backend 容器深度診斷"
echo "時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"
echo ""

# 1. 確認 backend 容器的映像
echo "【1】Backend 容器資訊"
echo "----------------------------------------"
docker inspect free_youtube_backend_blue --format '
容器名稱: {{.Name}}
映像: {{.Config.Image}}
建立時間: {{.Created}}
暴露端口: {{range $p, $conf := .Config.ExposedPorts}}{{$p}} {{end}}
' 2>/dev/null || echo "❌ 容器不存在"
echo ""

# 2. 檢查 backend 容器內部運行的程序
echo "【2】Backend 容器內部程序"
echo "----------------------------------------"
docker exec free_youtube_backend_blue ps aux 2>/dev/null || echo "❌ 無法執行"
echo ""

# 3. 檢查 backend 容器的 port 8000 監聽
echo "【3】Backend Port 8000 監聽狀態"
echo "----------------------------------------"
docker exec free_youtube_backend_blue netstat -tlnp 2>/dev/null || \
docker exec free_youtube_backend_blue ss -tlnp 2>/dev/null || \
echo "❌ 無法檢查端口"
echo ""

# 4. 直接在 backend 容器內測試 localhost:8000
echo "【4】Backend 內部 localhost:8000 測試"
echo "----------------------------------------"
echo "curl localhost:8000/api/health:"
docker exec free_youtube_backend_blue curl -s localhost:8000/api/health 2>/dev/null | head -20 || echo "❌ 連線失敗"
echo ""

# 5. 檢查 backend 的 nginx/php-fpm 配置
echo "【5】Backend Nginx 配置 (如果有)"
echo "----------------------------------------"
docker exec free_youtube_backend_blue cat /etc/nginx/nginx.conf 2>/dev/null | head -50 || \
docker exec free_youtube_backend_blue cat /etc/nginx/conf.d/default.conf 2>/dev/null | head -50 || \
echo "沒有 Nginx 配置或無法讀取"
echo ""

# 6. 檢查 backend 是否有 PHP-FPM
echo "【6】Backend PHP-FPM 狀態"
echo "----------------------------------------"
docker exec free_youtube_backend_blue ls -la /var/www/html/public/ 2>/dev/null | head -20 || echo "無法列出目錄"
echo ""

# 7. 檢查 backend 的 entrypoint/command
echo "【7】Backend 容器啟動命令"
echo "----------------------------------------"
docker inspect free_youtube_backend_blue --format '
Entrypoint: {{.Config.Entrypoint}}
Cmd: {{.Config.Cmd}}
WorkingDir: {{.Config.WorkingDir}}
' 2>/dev/null || echo "❌ 無法取得"
echo ""

# 8. 對比 frontend 容器
echo "【8】Frontend 容器資訊 (對比用)"
echo "----------------------------------------"
docker inspect free_youtube_frontend_blue --format '
容器名稱: {{.Name}}
映像: {{.Config.Image}}
暴露端口: {{range $p, $conf := .Config.ExposedPorts}}{{$p}} {{end}}
' 2>/dev/null || echo "❌ 容器不存在"
echo ""

# 9. 檢查兩個容器的映像是否相同
echo "【9】映像比較"
echo "----------------------------------------"
BACKEND_IMAGE=$(docker inspect free_youtube_backend_blue --format '{{.Image}}' 2>/dev/null)
FRONTEND_IMAGE=$(docker inspect free_youtube_frontend_blue --format '{{.Image}}' 2>/dev/null)
echo "Backend 映像 ID:  $BACKEND_IMAGE"
echo "Frontend 映像 ID: $FRONTEND_IMAGE"
if [ "$BACKEND_IMAGE" = "$FRONTEND_IMAGE" ]; then
    echo "⚠️ 警告: Backend 和 Frontend 使用相同的映像！這是問題所在！"
else
    echo "✅ 映像不同"
fi
echo ""

# 10. 檢查 docker-compose 使用的檔案
echo "【10】檢查 Docker Compose 專案"
echo "----------------------------------------"
echo "運行中的 compose 專案:"
docker compose ls 2>/dev/null || echo "無法列出"
echo ""

echo "========================================"
echo "診斷完成"
echo "========================================"
