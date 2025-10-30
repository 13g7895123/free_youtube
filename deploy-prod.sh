#!/bin/bash

# ========================================
# YouTube Loop Player - 正式環境部署腳本
# ========================================
# 此腳本專門用於正式環境的部署
# 使用 docker-compose.prod.yml 和相關的生產環境配置
#
# 使用方式:
#   ./deploy-prod.sh           - 快速部署（使用緩存，適合代碼更新）
#   ./deploy-prod.sh --full    - 完全重建（不使用緩存，適合依賴更新）
#   ./deploy-prod.sh --restart - 僅重啟（不重建，適合配置更新）
#   ./deploy-prod.sh --help    - 顯示幫助信息

set -e  # 遇到錯誤時立即退出

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 部署模式（默認為快速部署）
DEPLOY_MODE="quick"

# 使用說明
show_usage() {
    echo "使用方式: $0 [選項]"
    echo ""
    echo "選項:"
    echo "  (無參數)    快速部署模式（默認）- 使用 Docker 緩存，適合代碼更新"
    echo "  --full      完全重建模式 - 不使用緩存，適合依賴更新或環境變更"
    echo "  --restart   僅重啟模式 - 不重建鏡像，僅重啟容器，適合配置更新"
    echo "  --help      顯示此幫助信息"
    echo ""
    echo "範例:"
    echo "  $0              # 快速部署（1-3分鐘）"
    echo "  $0 --full       # 完全重建（5-15分鐘）"
    echo "  $0 --restart    # 僅重啟（10-30秒）"
    echo ""
    exit 0
}

# 解析參數
while [[ $# -gt 0 ]]; do
    case $1 in
        --full)
            DEPLOY_MODE="full"
            shift
            ;;
        --restart)
            DEPLOY_MODE="restart"
            shift
            ;;
        --help|-h)
            show_usage
            ;;
        *)
            echo -e "${RED}❌ 未知參數: $1${NC}"
            show_usage
            ;;
    esac
done

# 獲取腳本目錄並切換到專案根目錄
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# 顯示標題
echo ""
echo "========================================================"
echo -e "${BLUE}YouTube Loop Player - 正式環境部署${NC}"
echo "========================================================"
echo "Working directory: $(pwd)"
echo "Script location: ${BASH_SOURCE[0]}"
echo "Deployment time: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Deployment mode: ${DEPLOY_MODE}"
echo ""

# ========================================
# 1. 前置檢查
# ========================================
echo -e "${BLUE}📋 Step 1: 前置環境檢查${NC}"

# 檢查 Docker 是否運行
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Docker is not running${NC}"
    echo "Please start Docker and try again"
    exit 1
fi
echo -e "${GREEN}✅ Docker is running${NC}"

# 檢查 Docker Compose 版本
DOCKER_COMPOSE_VERSION=$(docker compose version --short 2>/dev/null || echo "0")
echo "Docker Compose version: ${DOCKER_COMPOSE_VERSION}"

# 驗證專案結構
if [ ! -d "frontend" ]; then
    echo -e "${RED}❌ Error: frontend directory not found${NC}"
    exit 1
fi

if [ ! -d "backend" ]; then
    echo -e "${RED}❌ Error: backend directory not found${NC}"
    exit 1
fi

if [ ! -f "docker-compose.prod.yml" ]; then
    echo -e "${RED}❌ Error: docker-compose.prod.yml not found${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Project structure verified${NC}"

# ========================================
# 2. 環境配置檢查
# ========================================
echo ""
echo -e "${BLUE}📋 Step 2: 環境配置檢查${NC}"

# 檢查 .env.prod 文件
if [ ! -f ".env.prod" ]; then
    echo -e "${YELLOW}⚠️  .env.prod file not found.${NC}"
    if [ -f ".env.example" ]; then
        echo "Creating .env.prod from .env.example..."
        cp .env.example .env.prod
        echo -e "${RED}❌ Please configure .env.prod file with production settings before deploying!${NC}"
        echo "Required settings:"
        echo "  - MYSQL_ROOT_PASSWORD (strong password)"
        echo "  - MYSQL_PASSWORD (strong password)"
        echo "  - BACKEND_PORT, FRONTEND_PORT, etc."
        exit 1
    else
        echo -e "${RED}❌ Error: .env.example not found${NC}"
        exit 1
    fi
fi

# 載入環境變數
source .env.prod

# 驗證必要的環境變數
REQUIRED_VARS=("MYSQL_ROOT_PASSWORD" "MYSQL_PASSWORD")
for VAR in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!VAR}" ]; then
        echo -e "${RED}❌ Error: ${VAR} is not set in .env.prod${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✅ Environment configuration validated${NC}"

# 檢查後端 .env 文件
if [ ! -f "backend/.env" ]; then
    echo -e "${YELLOW}⚠️  backend/.env not found.${NC}"
    if [ -f "backend/.env.example" ]; then
        echo "Creating backend/.env from backend/.env.example..."
        cp backend/.env.example backend/.env
        echo -e "${GREEN}✅ Created backend/.env${NC}"
    fi
fi

# ========================================
# 3. 備份現有數據 (可選)
# ========================================
echo ""
echo -e "${BLUE}📋 Step 3: 數據備份檢查${NC}"

if docker volume ls | grep -q "free_youtube.*mariadb_prod_data"; then
    echo -e "${YELLOW}⚠️  Found existing production database volume${NC}"
    read -p "Do you want to backup the database before deployment? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        BACKUP_DIR="./backups"
        mkdir -p "$BACKUP_DIR"
        BACKUP_FILE="${BACKUP_DIR}/db_backup_$(date +%Y%m%d_%H%M%S).sql"

        echo "Creating database backup..."
        docker compose -f docker-compose.prod.yml exec -T mariadb \
            mysqldump -u root -p"${MYSQL_ROOT_PASSWORD}" \
            --all-databases > "$BACKUP_FILE" 2>/dev/null || echo "Backup skipped (database not running)"

        if [ -f "$BACKUP_FILE" ]; then
            echo -e "${GREEN}✅ Backup created: ${BACKUP_FILE}${NC}"
        fi
    fi
else
    echo "No existing database found, skipping backup."
fi

# ========================================
# 4. 停止現有服務
# ========================================
echo ""
echo -e "${BLUE}📋 Step 4: 停止現有服務${NC}"

if docker compose -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo "Stopping running containers..."
    docker compose -f docker-compose.prod.yml down
    echo -e "${GREEN}✅ Containers stopped${NC}"
else
    echo "No running containers found."
fi

# ========================================
# 5. 構建 Docker 鏡像
# ========================================
echo ""
echo -e "${BLUE}📋 Step 5: 構建 Docker 鏡像${NC}"

if [ "$DEPLOY_MODE" = "restart" ]; then
    echo -e "${YELLOW}ℹ️  重啟模式：跳過鏡像構建${NC}"
elif [ "$DEPLOY_MODE" = "full" ]; then
    echo "完全重建模式：構建鏡像（不使用緩存，這可能需要較長時間）..."
    if docker compose -f docker-compose.prod.yml build --no-cache; then
        echo -e "${GREEN}✅ Docker images built successfully${NC}"
    else
        echo -e "${RED}❌ Error: Failed to build Docker images${NC}"
        exit 1
    fi
else
    echo "快速部署模式：構建鏡像（使用緩存）..."
    if docker compose -f docker-compose.prod.yml build; then
        echo -e "${GREEN}✅ Docker images built successfully${NC}"
    else
        echo -e "${RED}❌ Error: Failed to build Docker images${NC}"
        exit 1
    fi
fi

# ========================================
# 6. 啟動服務
# ========================================
echo ""
echo -e "${BLUE}📋 Step 6: 啟動生產環境服務${NC}"

echo "Starting production services..."
if docker compose -f docker-compose.prod.yml up -d; then
    echo -e "${GREEN}✅ Services started${NC}"
else
    echo -e "${RED}❌ Error: Failed to start services${NC}"
    exit 1
fi

# ========================================
# 7. 健康檢查
# ========================================
echo ""
echo -e "${BLUE}📋 Step 7: 健康檢查${NC}"

echo "Waiting for services to be healthy..."
WAIT_TIME=60
ELAPSED=0

while [ $ELAPSED -lt $WAIT_TIME ]; do
    HEALTHY_COUNT=$(docker compose -f docker-compose.prod.yml ps | grep -c "healthy" || echo "0")
    RUNNING_COUNT=$(docker compose -f docker-compose.prod.yml ps | grep -c "Up" || echo "0")

    echo "Services running: ${RUNNING_COUNT}, Healthy: ${HEALTHY_COUNT}"

    if [ "$RUNNING_COUNT" -ge 3 ]; then
        echo -e "${GREEN}✅ All services are running${NC}"
        break
    fi

    sleep 5
    ELAPSED=$((ELAPSED + 5))
done

# 顯示容器狀態
echo ""
echo "Current container status:"
docker compose -f docker-compose.prod.yml ps

# ========================================
# 8. 驗證部署
# ========================================
echo ""
echo -e "${BLUE}📋 Step 8: 驗證部署${NC}"

# 檢查前端
FRONTEND_PORT=${FRONTEND_PORT:-80}
if curl -f -s -o /dev/null "http://localhost:${FRONTEND_PORT}"; then
    echo -e "${GREEN}✅ Frontend is accessible${NC}"
else
    echo -e "${YELLOW}⚠️  Frontend may not be ready yet${NC}"
fi

# 檢查後端
BACKEND_PORT=${BACKEND_PORT:-8080}
if curl -f -s -o /dev/null "http://localhost:${BACKEND_PORT}/health"; then
    echo -e "${GREEN}✅ Backend API is accessible${NC}"
else
    echo -e "${YELLOW}⚠️  Backend API may not be ready yet${NC}"
fi

# ========================================
# 9. 部署完成
# ========================================
echo ""
echo "========================================================"
echo -e "${GREEN}✅ 部署完成！${NC}"
echo "========================================================"
echo ""
echo "🌐 Application URLs:"
echo "  - Frontend:    http://localhost:${FRONTEND_PORT}"
echo "  - Backend API: http://localhost:${BACKEND_PORT}"
echo "  - phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-8081}"
echo ""
echo "📊 Useful Commands:"
echo "  - View logs:           docker compose -f docker-compose.prod.yml logs -f"
echo "  - View backend logs:   docker compose -f docker-compose.prod.yml logs -f backend"
echo "  - View frontend logs:  docker compose -f docker-compose.prod.yml logs -f frontend"
echo "  - Check status:        docker compose -f docker-compose.prod.yml ps"
echo "  - Stop services:       docker compose -f docker-compose.prod.yml down"
echo "  - Restart services:    docker compose -f docker-compose.prod.yml restart"
echo ""
echo "🚀 Deployment Modes:"
echo "  - Quick deploy:        ./deploy-prod.sh           (uses cache, 1-3 min)"
echo "  - Full rebuild:        ./deploy-prod.sh --full    (no cache, 5-15 min)"
echo "  - Restart only:        ./deploy-prod.sh --restart (fastest, 10-30 sec)"
echo ""
echo "🔒 Security Reminders:"
echo "  - Change default passwords in production"
echo "  - Enable HTTPS with SSL certificates"
echo "  - Configure firewall rules"
echo "  - Set up regular backups"
echo "  - Monitor logs and resource usage"
echo ""
