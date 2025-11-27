#!/bin/bash

# ========================================
# API Routing 診斷腳本
# ----------------------------------------
# 目的：
# 1. 自動檢查 Gateway 是否使用最新的 /api 路由設定
# 2. 對 /api 系列端點發送請求並記錄完整的 Header/Body
# 3. 將結果輸出至終端機並保存至 logs/api-routing
# ========================================

set -euo pipefail

SCRIPT_DIR="$( cd -- "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_ROOT/logs/api-routing"
mkdir -p "$LOG_DIR"

TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
LOG_FILE="$LOG_DIR/api-routing-$TIMESTAMP.log"

# 將 stdout/stderr 同時輸出到螢幕與 log 檔
exec > >(tee -a "$LOG_FILE") 2>&1

# 前置參數
GATEWAY_HOST=${GATEWAY_HOST:-localhost}
GATEWAY_PORT=${GATEWAY_PORT:-8090}
BASE_URL=${BASE_URL:-"http://$GATEWAY_HOST:$GATEWAY_PORT"}

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info() { echo -e "${YELLOW}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
error() { echo -e "${RED}[ERR]${NC} $1"; }

echo "========================================"
echo "API Routing 診斷" 
echo "時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Base URL: $BASE_URL"
echo "Log 檔: $LOG_FILE"
echo "========================================"
echo

# -------------------------------------------------
# Step 1. 檢查 gateway Nginx 組態是否為最新
# -------------------------------------------------
info "檢查 Gateway Nginx 組態..."
if docker ps --format '{{.Names}}' | grep -q '^free_youtube_gateway$'; then
  docker exec free_youtube_gateway nginx -T > /tmp/nginx-full.conf 2>/dev/null || true
  API_BLOCK=$(grep -n "location \^~ /api" -n /tmp/nginx-full.conf || true)
  if [[ -n "$API_BLOCK" ]]; then
    success "已找到 location ^~ /api block"
    grep -n "location \^~ /api" -n /tmp/nginx-full.conf | head -n1
    sed -n '/location \^~ \/api/,/location/p' /tmp/nginx-full.conf | head -n20
  else
    error "沒找到 location ^~ /api，請確認 Gateway 是否已重啟載入最新設定"
  fi
  rm -f /tmp/nginx-full.conf
else
  error "找不到 free_youtube_gateway 容器，略過組態檢查"
fi

echo
# -------------------------------------------------
# Step 2. 發送測試請求並記錄
# -------------------------------------------------
make_request() {
  local path="$1"
  local url="${BASE_URL}${path}"
  local headers_file body_file status
  headers_file=$(mktemp)
  body_file=$(mktemp)

  echo "----------------------------------------"
  info "GET $url"
  status=$(curl -s -o "$body_file" -D "$headers_file" -w "%{http_code}" "$url" || true)
  echo "HTTP Status: $status"
  echo "-- Response Headers --"
  cat "$headers_file"
  echo "-- Response Body --"
  cat "$body_file"

  if [[ "$status" =~ ^30[0-9]$ ]]; then
    info "偵測到重新導向 (HTTP $status)，請檢查 Location Header"
  fi

  rm -f "$headers_file" "$body_file"
}

REQUEST_PATHS=(
  "/api"
  "/api/health"
  "/api/auth/line/login"
)

info "開始發送測試請求..."
for path in "${REQUEST_PATHS[@]}"; do
  make_request "$path"
done

echo "----------------------------------------"
success "測試完成，詳細輸出已記錄於 $LOG_FILE"
echo "下一步建議："
echo "1. 若 /api 仍發生錯誤，附上此 log 檔給開發人員分析"
echo "2. 確保執行完測試後有重新部署或重啟 Gateway 以載入新設定"
echo "========================================"
