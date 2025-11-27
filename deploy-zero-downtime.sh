#!/bin/bash

# ========================================
# YouTube Loop Player - Zero-Downtime 藍綠部署腳本
# ========================================
# 此腳本實現零停機的藍綠部署策略
#
# 使用方式:
#   ./deploy-prod.sh           - 執行藍綠部署
#   ./deploy-prod.sh --status  - 查看目前環境狀態
#   ./deploy-prod.sh --rollback - 回滾到上一個環境
#   ./deploy-prod.sh --init    - 首次初始化 (建立網路、Volume、啟動資料庫)
#   ./deploy-prod.sh --help    - 顯示幫助信息

set -e  # 遇到錯誤時立即退出

# ========================================
# 配置區
# ========================================
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 專案名稱前綴
PROJECT_BLUE="app-blue"
PROJECT_GREEN="app-green"

# 環境檔案
ENV_FILE=".env.prod"

# Docker Compose 檔案
COMPOSE_DB="docker-compose.db.yml"
COMPOSE_APP="docker-compose.app.yml"
COMPOSE_GATEWAY="docker-compose.gateway.yml"

# 網路和 Volume 名稱
NETWORK_NAME="free_youtube_app_network_prod"
VOLUME_NAME="free_youtube_mariadb_prod_data"

# 狀態檔案 (記錄目前活躍的環境)
STATE_FILE=".deploy-state"

# ========================================
# 輔助函數
# ========================================

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_step() {
    echo ""
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}📋 $1${NC}"
    echo -e "${CYAN}========================================${NC}"
}

# 取得目前活躍的環境 (blue 或 green)
get_active_env() {
    if [ -f "$STATE_FILE" ]; then
        cat "$STATE_FILE"
    else
        echo "none"
    fi
}

# 設定活躍的環境
set_active_env() {
    echo "$1" > "$STATE_FILE"
}

# 取得待部署的環境 (與目前相反)
get_target_env() {
    local active=$(get_active_env)
    if [ "$active" = "blue" ]; then
        echo "green"
    else
        echo "blue"
    fi
}

# 取得 project name
get_project_name() {
    if [ "$1" = "blue" ]; then
        echo "$PROJECT_BLUE"
    else
        echo "$PROJECT_GREEN"
    fi
}

# 取得 frontend 容器名稱
get_frontend_container() {
    local project=$(get_project_name "$1")
    echo "${project}-frontend-1"
}

# 檢查容器是否健康
check_container_health() {
    local container_name="$1"
    local max_attempts=30
    local attempt=0
    
    log_info "等待容器 $container_name 健康檢查..."
    
    while [ $attempt -lt $max_attempts ]; do
        local health=$(docker inspect --format='{{.State.Health.Status}}' "$container_name" 2>/dev/null || echo "not_found")
        
        if [ "$health" = "healthy" ]; then
            log_success "容器 $container_name 健康檢查通過"
            return 0
        elif [ "$health" = "not_found" ]; then
            log_error "容器 $container_name 不存在"
            return 1
        fi
        
        attempt=$((attempt + 1))
        echo "  嘗試 $attempt/$max_attempts - 狀態: $health"
        sleep 2
    done
    
    log_error "容器 $container_name 健康檢查超時"
    return 1
}

# 更新 upstream 配置
update_upstream() {
    local target_env="$1"
    local frontend_container=$(get_frontend_container "$target_env")
    
    log_info "更新 upstream 配置指向 $target_env 環境..."
    
    cat > nginx.upstream.conf << EOF
# ========================================
# Upstream 配置檔 - 藍綠部署切換
# ========================================
# 自動生成於: $(date '+%Y-%m-%d %H:%M:%S')
# 目前活躍環境: $target_env

upstream app_frontend {
    server ${frontend_container}:80;
    keepalive 32;
}
EOF
    
    log_success "upstream 配置已更新"
}

# 重載 Gateway Nginx
reload_gateway() {
    log_info "重載 Gateway Nginx 配置..."
    
    if docker exec free_youtube_gateway nginx -t > /dev/null 2>&1; then
        docker exec free_youtube_gateway nginx -s reload
        log_success "Gateway Nginx 配置重載成功"
    else
        log_error "Gateway Nginx 配置語法錯誤"
        return 1
    fi
}

# ========================================
# 主要命令函數
# ========================================

# 顯示幫助
show_help() {
    echo ""
    echo "YouTube Loop Player - Zero-Downtime 藍綠部署腳本"
    echo ""
    echo "使用方式: $0 [選項]"
    echo ""
    echo "選項:"
    echo "  (無參數)    執行藍綠部署"
    echo "  --status    查看目前環境狀態"
    echo "  --rollback  回滾到上一個環境"
    echo "  --init      首次初始化 (建立網路、Volume、啟動資料庫和 Gateway)"
    echo "  --help      顯示此幫助信息"
    echo ""
    exit 0
}

# 顯示狀態
show_status() {
    log_step "環境狀態"
    
    local active=$(get_active_env)
    echo "目前活躍環境: $active"
    echo ""
    
    echo "=== 資料庫服務 ==="
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_DB" ps 2>/dev/null || echo "未啟動"
    echo ""
    
    echo "=== Gateway 服務 ==="
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_GATEWAY" ps 2>/dev/null || echo "未啟動"
    echo ""
    
    echo "=== Blue 環境 ==="
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$PROJECT_BLUE" ps 2>/dev/null || echo "未啟動"
    echo ""
    
    echo "=== Green 環境 ==="
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$PROJECT_GREEN" ps 2>/dev/null || echo "未啟動"
    echo ""
}

# 初始化環境
init_environment() {
    log_step "初始化部署環境"
    
    # 檢查環境檔案
    if [ ! -f "$ENV_FILE" ]; then
        log_error "找不到環境檔案 $ENV_FILE"
        exit 1
    fi
    log_success "環境檔案存在"
    
    # 使用 Docker Compose 建立網路和 Volume
    log_info "使用 Docker Compose 建立基礎設施 (網路與 Volume)..."
    docker compose -f docker-compose.infra.yml up -d
    log_success "基礎設施已建立"
    
    # 驗證網路和 Volume
    if docker network inspect "$NETWORK_NAME" > /dev/null 2>&1; then
        log_success "網路 $NETWORK_NAME 已就緒"
    else
        log_error "網路 $NETWORK_NAME 建立失敗"
        exit 1
    fi
    
    if docker volume inspect "$VOLUME_NAME" > /dev/null 2>&1; then
        log_success "Volume $VOLUME_NAME 已就緒"
    else
        log_error "Volume $VOLUME_NAME 建立失敗"
        exit 1
    fi
    
    # 啟動資料庫服務
    log_step "啟動資料庫服務"
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_DB" up -d
    
    # 等待資料庫健康
    log_info "等待資料庫就緒..."
    sleep 10
    if check_container_health "free_youtube_db_prod"; then
        log_success "資料庫服務已就緒"
    else
        log_error "資料庫服務啟動失敗"
        exit 1
    fi
    
    # 啟動 Gateway
    log_step "啟動 Gateway 服務"
    
    # 先建立初始的 upstream 配置 (指向 blue，即使 blue 還沒啟動)
    update_upstream "blue"
    
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_GATEWAY" up -d
    log_success "Gateway 服務已啟動"
    
    # 設定初始狀態
    set_active_env "none"
    
    log_step "初始化完成"
    echo ""
    echo "現在可以執行 ./deploy-prod.sh 進行第一次部署"
    echo ""
}

# 回滾
rollback() {
    log_step "回滾部署"
    
    local active=$(get_active_env)
    local target=$(get_target_env)
    
    if [ "$active" = "none" ]; then
        log_error "沒有可回滾的環境"
        exit 1
    fi
    
    # 檢查目標環境是否存在
    local target_frontend=$(get_frontend_container "$target")
    if ! docker ps -a --format '{{.Names}}' | grep -q "^${target_frontend}$"; then
        log_error "回滾目標環境 $target 不存在"
        exit 1
    fi
    
    log_info "從 $active 回滾到 $target..."
    
    # 更新 upstream 並重載
    update_upstream "$target"
    reload_gateway
    
    # 更新狀態
    set_active_env "$target"
    
    log_success "已回滾到 $target 環境"
}

# 主要部署邏輯
deploy() {
    local start_time=$(date +%s)
    
    log_step "開始 Zero-Downtime 藍綠部署"
    echo "部署時間: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "工作目錄: $(pwd)"
    
    # 前置檢查
    log_step "Step 1: 前置檢查"
    
    # 檢查 Docker
    if ! docker info > /dev/null 2>&1; then
        log_error "Docker 未運行"
        exit 1
    fi
    log_success "Docker 運行中"
    
    # 檢查環境檔案
    if [ ! -f "$ENV_FILE" ]; then
        log_error "找不到環境檔案 $ENV_FILE"
        exit 1
    fi
    log_success "環境檔案存在"
    
    # 檢查網路
    if ! docker network inspect "$NETWORK_NAME" > /dev/null 2>&1; then
        log_error "網路 $NETWORK_NAME 不存在，請先執行 --init"
        exit 1
    fi
    log_success "網路存在"
    
    # 檢查資料庫
    if ! docker ps --format '{{.Names}}' | grep -q "free_youtube_db_prod"; then
        log_error "資料庫服務未運行，請先執行 --init"
        exit 1
    fi
    log_success "資料庫運行中"
    
    # 檢查 Gateway
    if ! docker ps --format '{{.Names}}' | grep -q "free_youtube_gateway"; then
        log_error "Gateway 服務未運行，請先執行 --init"
        exit 1
    fi
    log_success "Gateway 運行中"
    
    # 決定目標環境
    local active=$(get_active_env)
    local target=$(get_target_env)
    local target_project=$(get_project_name "$target")
    local target_frontend=$(get_frontend_container "$target")
    
    log_info "目前活躍環境: $active"
    log_info "目標部署環境: $target"
    
    # 構建新環境
    log_step "Step 2: 構建 $target 環境映像"
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$target_project" build
    log_success "映像構建完成"
    
    # 啟動新環境
    log_step "Step 3: 啟動 $target 環境"
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$target_project" up -d
    log_success "$target 環境已啟動"
    
    # 等待健康檢查
    log_step "Step 4: 健康檢查"
    
    # 等待 backend
    local target_backend="${target_project}-backend-1"
    if ! check_container_health "$target_backend"; then
        log_error "Backend 健康檢查失敗，中止部署"
        docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$target_project" down
        exit 1
    fi
    
    # 等待 frontend
    if ! check_container_health "$target_frontend"; then
        log_error "Frontend 健康檢查失敗，中止部署"
        docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$target_project" down
        exit 1
    fi
    
    log_success "所有服務健康檢查通過"
    
    # 切換流量
    log_step "Step 5: 切換流量到 $target 環境"
    update_upstream "$target"
    reload_gateway
    log_success "流量已切換到 $target 環境"
    
    # 更新狀態
    set_active_env "$target"
    
    # 清理舊環境
    if [ "$active" != "none" ]; then
        log_step "Step 6: 清理 $active 環境"
        local old_project=$(get_project_name "$active")
        
        # 等待一段時間確保沒有進行中的請求
        log_info "等待 10 秒確保舊連線完成..."
        sleep 10
        
        docker compose --env-file "$ENV_FILE" -f "$COMPOSE_APP" -p "$old_project" down
        log_success "$active 環境已清理"
    fi
    
    # 完成
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    log_step "部署完成"
    echo ""
    echo -e "${GREEN}✅ Zero-Downtime 部署成功！${NC}"
    echo ""
    echo "📊 部署摘要:"
    echo "  - 目前活躍環境: $target"
    echo "  - 部署耗時: ${duration} 秒"
    echo ""
    echo "🔧 常用命令:"
    echo "  - 查看狀態:    ./deploy-prod.sh --status"
    echo "  - 回滾:        ./deploy-prod.sh --rollback"
    echo "  - 查看日誌:    docker compose --env-file $ENV_FILE -f $COMPOSE_APP -p $target_project logs -f"
    echo ""
}

# ========================================
# 主程式
# ========================================

case "${1:-}" in
    --help|-h)
        show_help
        ;;
    --status)
        show_status
        ;;
    --init)
        init_environment
        ;;
    --rollback)
        rollback
        ;;
    *)
        deploy
        ;;
esac
